<?php

namespace App\HttpController\traits;

use App\Utility\Exceptions\Exception400;
use App\Utility\Exceptions\Exception404;
use App\Utility\Helper;
use App\Utility\StorageKey;
use App\Utility\IndexerKey;
use Swoole\Coroutine\Redis;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use App\Utility\Packers;

/**
 * Trait AccountsUpdate
 * @method Redis getRedis()
 * @method Request request()
 * @method Response response()
 */
trait AccountsUpdate
{
    public function actionUpdate()
    {
        $params = $this->request()->getQueryParams();
        $query_id = $params['query_id'];
        $id = $params['id'];
        unset($params['id']);
        unset($params['query_id']);

        $data = json_decode($this->request()->getBody());

        $redis = $this->getRedis();

        if (!$redis->exists(StorageKey::email_domain($id)))
            throw new Exception404('Account not found: ' . $id);

        $this->validateUpdate($redis, $data = (array)$data);

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'sex':
                    $redis->set(StorageKey::sex($id), $value);
                    $redis->sRem(IndexerKey::sex($value == 'm' ? 'f' : 'm'), $id);
                    $redis->sAdd(IndexerKey::sex($value), $id);
                    break;
                case 'status':
                    switch ($value) {
                        case STATUS_1:
                            $redis->sRem(IndexerKey::status(STATUS_2), $id);
                            $redis->sRem(IndexerKey::status(STATUS_3), $id);
                            break;
                        case STATUS_2:
                            $redis->sRem(IndexerKey::status(STATUS_1), $id);
                            $redis->sRem(IndexerKey::status(STATUS_3), $id);
                            break;
                        case STATUS_3:
                            $redis->sRem(IndexerKey::status(STATUS_1), $id);
                            $redis->sRem(IndexerKey::status(STATUS_2), $id);
                            break;
                        default:
                            throw new Exception400('Unsupported status value');
                    }
                    $redis->sAdd(IndexerKey::status($value), $id);
                    $redis->set(StorageKey::status($id), Packers\Status::pack($value));
                    break;
                case 'email':
                    list($email_name, $email_domain) = explode('@', $value);
                    $find_email = $redis->zRangeByScore('pk:en', (string)$id, (string)$id);
                    if (count($find_email))
                        Packers\EmailName::remove($redis, array_shift($find_email));

                    Packers\EmailName::register($redis, $id, $email_name);
                    $redis->sRem(IndexerKey::email_domain(
                        Packers\EmailDomain::unpack($redis->get(StorageKey::email_domain($id)))
                    ), $id);
                    $redis->set(StorageKey::email_domain($id), Packers\EmailDomain::pack($email_domain));
                    $redis->sAdd(IndexerKey::email_domain($email_domain), $id);
                    break;
                case 'country':
                    if ($redis->exists(StorageKey::country($id))) {
                        $redis->sRem(IndexerKey::country(Packers\Country::unpack($redis->get(StorageKey::country($id)))), $id);
                    } else {
                        $redis->sRem(IndexerKey::country_not_exists(), $id);
                        $redis->sAdd(IndexerKey::country_exists(), $id);
                    }
                    $redis->sAdd(IndexerKey::country($value), $id);
                    $redis->set(StorageKey::country($id), Packers\Country::pack($value));
                    break;
                case 'city':
                    if ($redis->exists(StorageKey::city($id))) {
                        $redis->sRem(IndexerKey::city(Packers\City::unpack((int)$redis->get(StorageKey::city($id)))), $id);
                    } else {
                        $redis->sRem(IndexerKey::city_not_exists(), $id);
                        $redis->sAdd(IndexerKey::city_exists(), $id);
                    }
                    $redis->sAdd(IndexerKey::city($value), $id);
                    $redis->set(StorageKey::city($id), Packers\City::pack($value));
                    break;
                case 'premium':
                    if ($redis->exists(StorageKey::premium_start($id))) {
                        if ((TIME >= $value->start) && (TIME <= $value->finish)) {
                            $redis->sAdd(IndexerKey::premium_now(), $id);
                        } else {
                            $redis->sRem(IndexerKey::premium_now(), $id);
                        }
                    } else {
                        $redis->sRem(IndexerKey::premium_not_exists(), $id);
                        $redis->sAdd(IndexerKey::premium_exists(), $id);
                    }

                    $redis->set(StorageKey::premium_start($id), $value->start);
                    $redis->set(StorageKey::premium_finish($id), $value->finish);

                    break;
                case 'fname':
                    if ($old_index = $redis->get(StorageKey::fname($id))) {
                        $old_fname = Packers\Fname::unpack($old_index);
                        $redis->sRem(IndexerKey::fname($old_fname), $id);
                    } else {
                        $redis->sRem(IndexerKey::fname_not_exists(), $id);
                        $redis->sAdd(IndexerKey::fname_exists(), $id);
                    }

                    $redis->set(StorageKey::fname($id), Packers\Fname::pack($value));
                    $redis->sAdd(IndexerKey::fname($value), $id);
                    break;
                case 'sname':
                    if ($sname = $redis->get(StorageKey::sname($id))) {
                        $redis->sRem(IndexerKey::sname_begins_with(Helper::extractIndexFromSname($sname)), $id);
                    } else {
                        $redis->sRem(IndexerKey::sname_not_exists(), $id);
                        $redis->sAdd(IndexerKey::sname_exists(), $id);
                    }

                    $redis->set(StorageKey::sname($id), $value);
                    $redis->sAdd(IndexerKey::sname_begins_with(Helper::extractIndexFromSname($value)), $id);
                    break;
                case 'interests':
                    $old = (array)Packers\Interests::unpack($redis->get(StorageKey::interests($id)));
                    foreach (array_diff($old, (array)$value) as $interest) {
                        if ($interest)
                            $redis->sRem(IndexerKey::interest($interest), $id);
                    }
                    foreach (array_diff((array)$value, $old) as $interest) {
                        if ($interest)
                            $redis->sAdd(IndexerKey::interest($interest), $id);
                    }

                    $redis->set(StorageKey::interests($id), Packers\Interests::pack((array)$value));
                    break;
                case 'phone':
                    if ($phone = $redis->get(StorageKey::phone($id))) {
                        $redis->sRem(IndexerKey::phone_with_Code(Helper::extractCodeFromPhone($phone)), $id);
                    } else {
                        $redis->sRem(IndexerKey::phone_not_exists(), $id);
                        $redis->sAdd(IndexerKey::phone_exists(), $id);
                    }

                    $redis->set(StorageKey::phone($id), $value);
                    $redis->sAdd(IndexerKey::phone_with_code(Helper::extractCodeFromPhone($value)), $id);
                    break;
                default:
                    throw new Exception400('Unsupported parameter ' . $key);
            }
        }

        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->withStatus(202);
        $this->response()->write('{}');
        $this->response()->end();
    }

    /**
     * @param Redis $redis
     * @param array $data
     */
    private function validateUpdate($redis, array $data)
    {
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'sex':
                    if (!in_array($value, ['m', 'f']))
                        throw new Exception400('Unsupported sex value');
                    break;
                case 'status':
                    if (!in_array($value, ["свободны", "заняты", "всё сложно"]))
                        throw new Exception400('Unsupported status value');
                    break;
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL))
                        throw new Exception400('Invalid email ' . $value);
                    list($email_name, $email_domain) = explode('@', $value);
                    if ($arid = (int)$redis->zScore('pk:en', $email_name)) {
                        if ($redis->get(StorageKey::email_domain($arid)) === Packers\EmailDomain::pack($email_domain))
                            throw new Exception400('Busy email ' . $value);
                    }
                    break;
                case 'country':
                    break;
                case 'city':
                    break;
                case 'premium':
                    if (!isset($value->start) || !is_int($value->start) || !isset($value->finish) || !is_int($value->finish))
                        throw new Exception400('Invalid premium value');
                    break;
                case 'fname':
                    break;
                case 'sname':
                    break;
                case 'interests':
                    break;
                case 'phone':
                    break;
                default:
                    throw new Exception400('Unknown parameter' . $key);
            }
        }
    }
}