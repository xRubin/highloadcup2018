<?php declare(strict_types=1);

/**
 * @param int $id
 * @param array $params
 * @return array
 */
function resolverAccountsSuggest(int $id, array $params): array
{
    global $maxId;
    if ($maxId->get() < $id)
        throw new Exception404('Account not found');

    $query_id = $params['query_id'];
    unset($params['query_id']);

    {
        $limit = $params['limit'];
        unset($params['limit']);
        if (!is_numeric($limit))
            throw new Exception400('wrong limit');
        $limit = (int)$limit;
        if ($limit < 1)
            throw new Exception400('wrong limit');
    }

    $redis = RedisQueue::getRedis();

    $liked = array_unique(
        array_column(
            @(array)unpack_likes(
                $redis->get(
                    storageKey_likes($id)
                )
            ),
            'id'
        )
    );

    $set = 'sims:' . $query_id;
    $set_sims = array_map(function ($tId) {
        return indexerKey_accountLiked($tId);
    }, $liked);
    if (empty($set_sims)) {
        unset($redis);
        return ['accounts' => []];
    }

    $redis->sUnionStore($set, ...$set_sims);
    $redis->sRemove($set, $id);

    $sets = [$set];

    foreach ($params as $key => $value) {
        switch ($key) {
            case 'country':
                if (!$value) {
                    unset($redis);
                    throw new Exception400();
                }
                $sets[] = indexerKey_country($value);
                break;
            case 'city':
                if (!$value) {
                    unset($redis);
                    throw new Exception400();
                }
                $sets[] = indexerKey_city($value);
                break;
            default:
                $redis->delete($set);
                unset($redis);
                throw new Exception400('Unsupported parameter ' . $key);
        }
    }

    $sims = $redis->sInter(...$sets);
    $redis->delete($set);

    if (empty($sims)) {
        unset($redis);
        return ['accounts' => []];
    }

    $simArray = [];
    foreach ($sims as $simId) {
        if ($similarity = _similarity($redis, (int)$id, (int)$simId))
            $simArray[$simId] = $similarity;
    }

    if (!count($simArray)) {
        unset($redis);
        return ['accounts' => []];
    }

    arsort($simArray, SORT_NUMERIC);

    $response = [];

    $alr = array_column(@(array)unpack_likes($redis->get(storageKey_likes((int)$id))), 'id');
    foreach ($simArray as $simId => $similarity) {
        if (count($response) >= $limit)
            break;

        $likes = array_column(@(array)unpack_likes($redis->get(storageKey_likes((int)$simId))), 'id');
        arsort($likes);
        foreach ($likes as $uid) {
            if (count($response) >= $limit)
                break;

            if (in_array($uid, $alr)) continue;

            $response[] = packerGetResultArray($redis, (int)$uid, ['status', 'fname', 'sname']);

            $alr[] = $uid;
        }
    }
    unset($redis);

    return ['accounts' => $response];
}

/**
 * @param \Swoole\Coroutine\Redis $redis
 * @param int $meId
 * @param int $somebodyId
 * @return float
 */
function _similarity($redis, int $meId, int $somebodyId): float
{
    $meLikes = @(array)unpack_likes($redis->get(storageKey_likes($meId)));
    $somebodyLikes = @(array)unpack_likes($redis->get(storageKey_likes($somebodyId)));

    $common = array_intersect(
        array_column($meLikes, 'id'),
        array_column($somebodyLikes, 'id')
    );

    if (empty($common))
        return 0;

    $result = 0;

    foreach ($common as $tId) {
        $meTsSum = 0;
        $meTsCount = 0;
        foreach ($meLikes as $like) {
            if ($like['id'] == $tId) {
                $meTsSum += $like['ts'];
                $meTsCount++;
            }
        }

        $somebodyTsSum = 0;
        $somebodyTsCount = 0;
        foreach ($somebodyLikes as $like) {
            if ($like['id'] == $tId) {
                $somebodyTsSum += $like['ts'];
                $somebodyTsCount++;
            }
        }

        $und = abs($somebodyTsSum / $somebodyTsCount - $meTsSum / $meTsCount);
        if ($und == 0) {
            $result += 1;
        } else {
            $result += 1 / $und;
        }
    }

    return $result;
}