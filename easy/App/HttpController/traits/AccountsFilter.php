<?php

namespace App\HttpController\traits;

use App\Utility\Exceptions\Exception400;
use App\Utility\Helper;
use App\Utility\IndexerKey;
use App\Utility\Packer;
use App\Utility\Validator;
use EasySwoole\Component\AtomicManager;
use Swoole\Coroutine\Redis;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

/**
 * Trait AccountsFilter
 * @method Redis getRedis()
 * @method Request request()
 * @method Response response()
 */
trait AccountsFilter
{
    public function actionFilter()
    {
        $limit = Validator::validateLimit($this->request());
        $params = $this->request()->getQueryParams();
        $query_id = $params['query_id'];
        unset($params['query_id']);
        unset($params['limit']);

        if (array_key_exists('birth_gt', $params) || array_key_exists('birth_lt', $params)
            || array_key_exists('email_gt', $params) || array_key_exists('email_lt', $params)) {
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->write('{"accounts":[]}');
            $this->response()->end();
            return;
        }

        $sets = [];
        $temp = [];
        $mask = [];

        $redis = $this->getRedis();

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'sex_eq':
                    $sets[] = IndexerKey::sex($value);
                    $mask[] = 'sex';
                    break;

                case 'email_domain':
                    $sets[] = IndexerKey::email_domain($value);
                    //$mask[] = 'email';
                    break;

                case 'city_eq':
                    $sets[] = IndexerKey::city($value);
                    $mask[] = 'city';
                    break;
                case 'city_any':
                    $union = array_map(function ($city) {
                        return IndexerKey::city($city);
                    }, explode(',', $value));
                    $set = 'city_any:' . $query_id;
                    $redis->sUnionStore($set, ...$union);
                    $temp[] = $set;
                    $sets[] = $set;
                    $mask[] = 'city';
                    break;
                case 'city_null':
                    $sets[] = $value ? IndexerKey::city_not_exists() : IndexerKey::city_exists();
                    $mask[] = 'city';
                    break;

                case 'country_eq':
                    $sets[] = IndexerKey::country($value);
                    $mask[] = 'country';
                    break;
                case 'country_null':
                    $sets[] = $value ? IndexerKey::country_not_exists() : IndexerKey::country_exists();
                    $mask[] = 'country';
                    break;

                case 'phone_code':
                    $sets[] = IndexerKey::phone_with_code($value);
                    $mask[] = 'phone';
                    break;
                case 'phone_null':
                    $sets[] = $value ? IndexerKey::phone_not_exists() : IndexerKey::phone_exists();
                    $mask[] = 'phone';
                    break;

                case 'status_eq':
                    $sets[] = IndexerKey::status($value);
                    $mask[] = 'status';
                    break;
                case 'status_neq':
                    $set = 'status_neq:' . $query_id;

                    switch ($value) {
                        case STATUS_1:
                            $redis->sUnionStore($set, IndexerKey::status(STATUS_2), IndexerKey::status(STATUS_3));
                            break;
                        case STATUS_2:
                            $redis->sUnionStore($set, IndexerKey::status(STATUS_1), IndexerKey::status(STATUS_3));
                            break;
                        case STATUS_3:
                            $redis->sUnionStore($set, IndexerKey::status(STATUS_1), IndexerKey::status(STATUS_2));
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
                        return IndexerKey::Interest($interest);
                    }, explode(',', $value));
                    $set = 'interests_any:' . $query_id;
                    $redis->sUnionStore($set, ...$union);
                    $temp[] = $set;
                    $sets[] = $set;
                    //$mask[] = 'interests';
                    break;
                case 'interests_contains':
                    foreach (explode(',', $value) as $interest)
                        $sets[] = IndexerKey::interest($interest);
                    //$mask[] = 'interests';
                    break;

                case 'fname_any':
                    $union = array_map(function (string $fname) {
                        return IndexerKey::fname($fname);
                    }, explode(',', $value));
                    $set = 'fname_any:' . $query_id;
                    $redis->sUnionStore($set, ...$union);
                    $temp[] = $set;
                    $sets[] = $set;
                    $mask[] = 'fname';
                    break;
                case 'fname_null':
                    $sets[] = $value ? IndexerKey::fname_not_exists() : IndexerKey::fname_exists();
                    $mask[] = 'fname';
                    break;

                case 'sname_starts':
                    $sets[] = IndexerKey::sname_begins_with(Helper::extractIndexFromSname($value));
                    $mask[] = 'sname';
                    break;
                case 'sname_null':
                    $sets[] = $value ? IndexerKey::sname_not_exists() : IndexerKey::sname_exists();
                    $mask[] = 'sname';
                    break;

                case 'premium_now':
                    $sets[] = IndexerKey::premium_now();
                    $mask[] = 'premium';
                    break;
                case 'premium_null':
                    $sets[] = $value ? IndexerKey::premium_not_exists() : IndexerKey::premium_exists();
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
                    $sets[] = IndexerKey::birth_year($value);

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
                        $sets[] = IndexerKey::account_liked((int)$likeId);
                    break;

                default:
                    if (count($temp))
                        $redis->delete(...$temp);
                    throw new Exception400('Unknown filter "' . $key . '"');
            }
        }

        $accounts = [];

        // TODO: refactoring

        if (empty($sets)) {
            $i = AtomicManager::getInstance()->get('maxId')->get();

            while ($id = $i--) {
                if (count($accounts) == $limit)
                    break;

                $account = Packer::getResultArray($redis, (int)$id, $mask);
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
            Helper::redisSInterStoreWithSorting($redis, $listName, ...$sets);

            $i = 0;
            while ($id = $redis->lIndex($listName, $i++)) {
                if (count($accounts) == $limit)
                    break;

                $account = Packer::getResultArray($redis, (int)$id, $mask);
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

        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write(json_encode(['accounts' => $accounts]));
        $this->response()->end();
    }
}