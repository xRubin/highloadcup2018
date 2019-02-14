<?php declare(strict_types=1);

/**
 * @param array $params
 * @return array
 */
function resolverAccountGroup(array $params): array
{

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

    $keys = explode(',', $params['keys']);
    unset($params['keys']);
    if (count(array_diff($keys, ['interests','status','sex','country','city'])))
        throw new Exception400('wrong keys');

    $order = $params['order'];
    unset($params['order']);

    $sets = [];
    //$temp = [];

    foreach ($params as $key => $value) {
        switch ($key) {
            case 'sex':
                $sets[] = indexerKey_sex($value);
                break;
            case 'country':
                $sets[] = indexerKey_country($value);
                break;
            case 'city':
                $sets[] = indexerKey_city($value);
                break;
            case 'status':
                $sets[] = indexerKey_status($value);
                break;
            case 'interests':
                $sets[] = indexerKey_interest($value);
                break;
            case 'likes':
                $sets[] = indexerKey_accountLiked((int)$value);
                break;
            case 'joined':
                $sets[] = indexerKey_joinedYear($value);
                break;
            case 'birth':
                $sets[] = indexerKey_birthYear($value);
                break;
            default:
                throw new Exception400('Unsupported parameter ' . $key);
        }
    }

    $redis = $redis = RedisQueue::getRedis();

    if (count($sets)) {
        $redis->sInterStore($targets = 'group:' . $query_id, ...$sets);
        if (!$redis->scard($targets)) {
            $redis->delete($targets);
            return ['groups' => []];
        }
    }

    $markers = [];
    foreach ($keys as $key) {
        switch ($key) {
            case 'interests':
                if (array_key_exists('interests', $params)) {
                    $markers[$key][] = indexerKey_interest($params['interests']);
                } else {
                    global $interest_table;
                    foreach ($interest_table as $row)
                        $markers[$key][] = indexerKey_interest($row['name']);
                }
                break;
            case 'status':
                if (array_key_exists('status', $params)) {
                    $markers[$key][] = indexerKey_status($params['status']);
                } else {
                    foreach ([STATUS_1, STATUS_2, STATUS_3] as $status)
                        $markers[$key][] = indexerKey_status($status);
                }
                break;
            case 'sex':
                if (array_key_exists('sex', $params)) {
                    $markers[$key][] = indexerKey_sex($params['sex']);
                } else {
                    foreach (['f', 'm'] as $sex)
                        $markers[$key][] = indexerKey_sex($sex);
                }
                break;
            case 'country':
                if (array_key_exists('country', $params)) {
                    $markers[$key][] = indexerKey_country($params['country']);
                } else {
                    global $country_table;
                    foreach ($country_table as $row)
                        $markers[$key][] = indexerKey_country($row['name']);
                    $markers[$key][] = indexerKey_countryNotExists();
                }
                break;
            case 'city':
                if (array_key_exists('city', $params)) {
                    $markers[$key][] = indexerKey_city($params['city']);
                } else {
                    $markers[$key] = array_map(function($city) {
                        return indexerKey_city($city);
                        }, $redis->zRange('pk:c', 0, -1)
                    );
                    $markers[$key][] = indexerKey_cityNotExists();
                }
                break;
        }
    }

    $groups = combineArrays($markers);

    foreach ($groups as &$group) {
        $name = implode(':', array_values($group));
            $group['count'] = isset($targets) ? redisSInterCount($redis, $targets, ...array_values($group)) :  redisSInterCount($redis, ...array_values($group));
        $group['name'] = $name;
    }

    if (isset($targets))
        $redis->delete($targets);

    if ($order == '1') {
        usort($groups, function ($group1, $group2) {
            if ($group1['count'] == $group2['count'])
                return $group1['name'] <=> $group2['name'];

            return $group1['count'] <=> $group2['count'];
        });
    } else {
        usort($groups, function ($group1, $group2) {
            if ($group2['count'] == $group1['count'])
                return $group2['name'] <=> $group1['name'];

            return $group2['count'] <=> $group1['count'];
        });
    }

    $response = [];
    foreach ($groups as &$group) {
        if (count($response) == $limit)
            break;

        if (!$group['count'])
            continue;

        unset($group['name']);

        foreach ($group as $key => $value) {
            if ($key == 'count')
                continue;
            $group[$key] = str_replace(['ik:ct:-', 'ik:ct:', 'ik:cr:-', 'ik:cr:', 'ik:s:', 'ik:st:', 'ik:int:'], '', $value);
        }

        $result = array_filter($group);
        if (in_array('interests', $keys)) {
            if (count($result) > 1)
                $response[] = $result;
        } else {
            $response[] = $result;
        }
    }
    unset($redis);

    return ['groups' => $response];
}
