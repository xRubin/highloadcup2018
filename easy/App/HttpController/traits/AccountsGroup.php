<?php

namespace App\HttpController\traits;

use App\Utility\Exceptions\Exception400;
use App\Utility\Helper;
use App\Utility\IndexerKey;
use App\Utility\Validator;
use EasySwoole\Component\TableManager;
use Swoole\Coroutine\Redis;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

/**
 * Trait AccountsGroup
 * @method Redis getRedis()
 * @method Request request()
 * @method Response response()
 */
trait AccountsGroup
{
    public function actionGroup()
    {
        $limit=Validator::validateLimit($this->request());
        $keys=Validator::validateGroupKeys($this->request());
        $params = $this->request()->getQueryParams();
        $order = $params['order'];
        $query_id = $params['query_id'];
        unset($params['query_id']);
        unset($params['limit']);
        unset($params['keys']);
        unset($params['order']);

        

        $sets = [];
        //$temp = [];

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'sex':
                    $sets[] = IndexerKey::sex($value);
                    break;
                case 'country':
                    $sets[] = IndexerKey::country($value);
                    break;
                case 'city':
                    $sets[] = IndexerKey::city($value);
                    break;
                case 'status':
                    $sets[] = IndexerKey::status($value);
                    break;
                case 'interests':
                    $sets[] = IndexerKey::interest($value);
                    break;
                case 'likes':
                    $sets[] = IndexerKey::account_liked((int)$value);
                    break;
                case 'joined':
                    $sets[] = IndexerKey::joined_year($value);
                    break;
                case 'birth':
                    $sets[] = IndexerKey::birth_year($value);
                    break;
                default:
                    throw new Exception400('Unsupported parameter ' . $key);
            }
        }

        $redis = $this->getRedis();

        if (count($sets)) {
            $redis->sInterStore($targets = 'group:' . $query_id, ...$sets);
            if (!$redis->scard($targets)) {
                $redis->delete($targets);
                $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
                $this->response()->write(json_encode(['groups' => []]));
                $this->response()->end();
                return;
            }
        }

        $markers = [];
        foreach ($keys as $key) {
            switch ($key) {
                case 'interests':
                    if (array_key_exists('interests', $params)) {
                        $markers[$key][] = IndexerKey::interest($params['interests']);
                    } else {
                        $interest_table = TableManager::getInstance()->get('interest');
                        foreach ($interest_table as $row)
                            $markers[$key][] = IndexerKey::interest($row['name']);
                    }
                    break;
                case 'status':
                    if (array_key_exists('status', $params)) {
                        $markers[$key][] = IndexerKey::status($params['status']);
                    } else {
                        foreach ([STATUS_1, STATUS_2, STATUS_3] as $status)
                            $markers[$key][] = IndexerKey::status($status);
                    }
                    break;
                case 'sex':
                    if (array_key_exists('sex', $params)) {
                        $markers[$key][] = IndexerKey::sex($params['sex']);
                    } else {
                        foreach (['f', 'm'] as $sex)
                            $markers[$key][] = IndexerKey::sex($sex);
                    }
                    break;
                case 'country':
                    if (array_key_exists('country', $params)) {
                        $markers[$key][] = IndexerKey::country($params['country']);
                    } else {
                        $country_table = TableManager::getInstance()->get('country');
                        foreach ($country_table as $row)
                            $markers[$key][] = IndexerKey::country($row['name']);
                        $markers[$key][] = IndexerKey::country_not_exists();
                    }
                    break;
                case 'city':
                    if (array_key_exists('city', $params)) {
                        $markers[$key][] = IndexerKey::city($params['city']);
                    } else {
                        $city_table = TableManager::getInstance()->get('city');
                        foreach ($city_table as $row)
                            $markers[$key][] = IndexerKey::city($row['name']);
                        $markers[$key][] = IndexerKey::city_not_exists();
                    }
                    break;
            }
        }

        $groups = Helper::combineArrays($markers);

        foreach ($groups as &$group) {
            $name = implode(':', array_values($group));
            $group['count'] = isset($targets) ? Helper::redisSInterCount($redis, $targets, ...array_values($group)) :  Helper::redisSInterCount($redis, ...array_values($group));
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

        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write(json_encode(['groups' => $response]));
        $this->response()->end();
    }
}