<?php declare(strict_types=1);

/**
 * @param array $params
 * @return array
 */
function resolverAccountFilter(array $params): array
{
    global $maxId;
    //var_dump($params);
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

    if (array_key_exists('birth_gt', $params) || array_key_exists('birth_lt', $params)
        || array_key_exists('email_gt', $params)|| array_key_exists('email_lt', $params))
        return ['accounts' => []];

    $sets = [];
    $temp = [];
    $mask = [];

    $redis = RedisQueue::getRedis();

    foreach ($params as $key => $value) {
        switch ($key) {
            case 'sex_eq':
                $sets[] = indexerKey_sex($value);
                $mask[] = 'sex';
                break;

            case 'email_domain':
                $sets[] = indexerKey_emailDomain($value);
                //$mask[] = 'email';
                break;

            case 'city_eq':
                $sets[] = indexerKey_city($value);
                $mask[] = 'city';
                break;
            case 'city_any':
                $union = array_map(function ($city) {
                    return indexerKey_city($city);
                }, explode(',', $value));
                $set = 'city_any:' . $query_id;
                $redis->sUnionStore($set, ...$union);
                $temp[] = $set;
                $sets[] = $set;
                $mask[] = 'city';
                break;
            case 'city_null':
                $sets[] = $value ? indexerKey_cityNotExists() : indexerKey_cityExists();
                $mask[] = 'city';
                break;

            case 'country_eq':
                $sets[] = indexerKey_country($value);
                $mask[] = 'country';
                break;
            case 'country_null':
                $sets[] = $value ? indexerKey_countryNotExists() : indexerKey_countryExists();
                $mask[] = 'country';
                break;

            case 'phone_code':
                $sets[] = indexerKey_phoneWithCode($value);
                $mask[] = 'phone';
                break;
            case 'phone_null':
                $sets[] = $value ? indexerKey_phoneNotExists() : indexerKey_phoneExists();
                $mask[] = 'phone';
                break;

            case 'status_eq':
                $sets[] = indexerKey_status($value);
                $mask[] = 'status';
                break;
            case 'status_neq':
                $set = 'status_neq:' . $query_id;

                switch ($value) {
                    case STATUS_1:
                        $redis->sUnionStore($set, indexerKey_status(STATUS_2), indexerKey_status(STATUS_3));
                        break;
                    case STATUS_2:
                        $redis->sUnionStore($set, indexerKey_status(STATUS_1), indexerKey_status(STATUS_3));
                        break;
                    case STATUS_3:
                        $redis->sUnionStore($set, indexerKey_status(STATUS_1), indexerKey_status(STATUS_2));
                        break;
                    default:
                        throw new Exception400('Unknown value "' . $value . '"');
                }
                $temp[] = $set;
                $sets[] = $set;
                $mask[] = 'status';
                break;

            case 'interests_any':
                $union = array_map(function (string $interest) {
                    return indexerKey_interest($interest);
                }, explode(',', $value));
                $set = 'interests_any:' . $query_id;
                $redis->sUnionStore($set, ...$union);
                $temp[] = $set;
                $sets[] = $set;
                //$mask[] = 'interests';
                break;
            case 'interests_contains':
                foreach (explode(',', $value) as $interest)
                    $sets[] = indexerKey_interest($interest);
                //$mask[] = 'interests';
                break;

            case 'fname_any':
                $union = array_map(function (string $fname) {
                    return indexerKey_fname($fname);
                }, explode(',', $value));
                $set = 'fname_any:' . $query_id;
                $redis->sUnionStore($set, ...$union);
                $temp[] = $set;
                $sets[] = $set;
                $mask[] = 'fname';
                break;
            case 'fname_null':
                $sets[] = $value ? indexerKey_fnameNotExists() : indexerKey_fnameExists();
                $mask[] = 'fname';
                break;

            case 'sname_starts':
                $sets[] = indexerKey_snameBeginsWith(extractIndexFromSname($value));
                $mask[] = 'sname';
                break;
            case 'sname_null':
                $sets[] = $value ? indexerKey_snameNotExists() : indexerKey_snameExists();
                $mask[] = 'sname';
                break;

            case 'premium_now':
                $sets[] = indexerKey_premiumNow();
                $mask[] = 'premium';
                break;
            case 'premium_null':
                $sets[] = $value ? indexerKey_premiumNotExists() : indexerKey_premiumExists();
                $mask[] = 'premium';
                break;

            // TODO: делать последними
            case 'birth_gt':
//                $set = 'birth_gt:' . $query_id;
//                // TODO: подумать
//                $redis->sAdd($set, $redis->zRange(indexerKey_birthSortedSet(), $value, '+inf'));
//                $temp[] = $set;
//                $sets[] = $set;
                $mask[] = 'birth';
                break;
            case 'birth_lt':
//                $set = 'birth_lt:' . $query_id;
//                // TODO: подумать
//                $redis->sAdd($set, $redis->zRange(indexerKey_birthSortedSet(), 0, $value));
//                $temp[] = $set;
//                $sets[] = $set;
                $mask[] = 'birth';
                break;
            case 'birth_year':
                $sets[] = indexerKey_birthYear($value);

//                $start = mktime(0, 0, 0, 1, 1, $value);
//                $end = mktime(0, 0, 0, 1, 1, $value + 1);
//                $set = 'birth_year:' . $query_id;
//                // TODO: подумать
//                $redis->sAdd($set, $redis->zRange(indexerKey_birthSortedSet(), $start, $end));
//                $temp[] = $set;
//                $sets[] = $set;
                $mask[] = 'birth';
                break;

            case 'email_gt':
//                $result = array_filter($result, function ($id) use ($value) {
//                    return $this->storage->accounts[$id]['email'] > $value;
//                });
//                $mask[] = 'email';
                break;
            case 'email_lt':
//                $result = array_filter($result, function ($id) use ($value) {
//                    return $this->storage->accounts[$id]['email'] < $value;
//                });
//                $mask[] = 'email';
                break;

            case 'likes_contains':
                foreach (explode(',', $value) as $likeId)
                    $sets[] = indexerKey_accountLiked((int)$likeId);
                break;

            default:
                if (count($temp))
                    $redis->delete(...$temp);
                $redis->close();
                unset($redis);
                throw new Exception400('Unknown filter "' . $key . '"');
        }
    }

    $accounts = [];

    // TODO: refactoring

    if (empty($sets)) {
        $i = $maxId->get();

        while ($id = $i--) {
            if (count($accounts) == $limit)
                break;

            $account = packerGetResultArray($redis, (int)$id, $mask);
            foreach ($params as $key => $value) {
                switch ($key) {
                    case 'birth_gt':
                        if ($account['birth'] < (int)$value)
                            continue 3;
                        break;
                    case 'birth_lt':
                        if ($account['birth'] > (int)$value)
                            continue 3;
                        break;
                    case 'email_gt':
                        if ($account['email'] < $value)
                            continue 3;
                        break;
                    case 'email_lt':
                        if ($account['email'] > $value)
                            continue 3;
                        break;
                    case 'sname_starts':
                        if (mb_strpos($value, $account['sname']) === false)
                            continue 3;
                        break;
                }
            }

            $accounts[] = $account;
        }
    } else {

        $listName = 'filtered:' . $query_id;
        redisSInterStoreWithSorting($redis, $listName, ...$sets);

        $i = 0;
        while ($id = $redis->lIndex($listName, $i++)) {
            if (count($accounts) == $limit)
                break;

            $account = packerGetResultArray($redis, (int)$id, $mask);
            foreach ($params as $key => $value) {
                switch ($key) {
                    case 'birth_gt':
                        if ($account['birth'] < (int)$value)
                            continue 3;
                        break;
                    case 'birth_lt':
                        if ($account['birth'] > (int)$value)
                            continue 3;
                        break;
                    case 'email_gt':
                        if ($account['email'] < $value)
                            continue 3;
                        break;
                    case 'email_lt':
                        if ($account['email'] > $value)
                            continue 3;
                        break;
                    case 'sname_starts':
                        if (mb_strpos($account['sname'], $value) === false)
                            continue 3;
                        break;
                }
            }

            $accounts[] = $account;
        }
        $redis->delete($listName, ...$temp);
    }
    $redis->close();
    unset($redis);

    return ['accounts' => $accounts];
}