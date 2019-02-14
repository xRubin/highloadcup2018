<?php declare(strict_types=1);

/**
 * @param int $id
 * @param array $params
 * @return array
 */
function resolverAccountsRecommend(int $id, array $params): array
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

    $accountInterests = (array)explode('|', (string)$redis->get(storageKey_interests($id)));
    $accountBirth = (int)$redis->get(storageKey_birth($id));
    if (!$accountBirth)
        throw new Exception404('Account not found');

    $sets = [];
    //$temp = [];
    $sets[] = indexerKey_sex(($redis->get(storageKey_sex((int)$id)) == 'm') ? 'f' : 'm');

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
                throw new Exception400('Unsupported parameter ' . $key);
        }
    }

    $interests = array_filter(array_map(function ($interestId) {
        if (empty($interestId))
            return null;
        return indexerKey_interest(unpack_interest($interestId));
    }, $accountInterests));

    $targets = 'filtered:' . $query_id;
    if (!count($interests))
        return ['accounts' => []];

    $withSameInterestsKey = 'recu:' . $query_id;
    $redis->sUnionStore($withSameInterestsKey, ...$interests);
    if (!$redis->sCard($withSameInterestsKey)) {
        $redis->delete($withSameInterestsKey);
        return ['accounts' => []];
    }
    redisSInterStoreWithSorting($redis, $targets, $withSameInterestsKey, ...$sets);

    $i = 0;
    $recArray = [];
    while ($recId = $redis->lIndex($targets, $i++)) {
        if ($compatibility = _compatibility($redis, $accountInterests, $accountBirth, (int)$recId))
            $recArray[$recId] = $compatibility;
    }

    $redis->delete($targets, $withSameInterestsKey);


    if (!count($recArray))
        return ['accounts' => []];

    arsort($recArray, SORT_NUMERIC);

    $response = ['accounts' => array_map(function ($id) use ($redis) {
        return packerGetResultArray($redis, (int)$id, ['status', 'fname', 'sname', 'birth', 'premium']);
    }, array_slice(array_keys($recArray), 0, $limit))];

    unset($redis);

    return $response;
}

/**
 * @param \Swoole\Coroutine\Redis $redis
 * @param array $meInterestKeys
 * @param int $meBirth
 * @param int $somebodyId
 * @return float
 */
function _compatibility($redis, array $meInterestKeys, int $meBirth, int $somebodyId): float
{
    $result = 0;

    if ($redis->sismember(indexerKey_premiumNow(), $somebodyId))
        $result += 100000;

    switch ($redis->get(storageKey_status($somebodyId))) {
        case 0:
            $result += 10000;
            break;
        case 2:
            $result += 5000;
            break;
    }

    $somebodyInterestKeys = $redis->get(storageKey_interests($somebodyId));
    if (!$somebodyInterestKeys)
        return 0;

    $inter = count(
        array_intersect(
            $meInterestKeys,
            (array)explode('|', $somebodyInterestKeys)
        )
    );
    if (!$inter)
        return 0;

    $result += $inter * 500;

    $result -= abs($meBirth - $redis->get(storageKey_birth($somebodyId))) / 31556926;

    return $result;
}