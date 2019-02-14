<?php

namespace App\Utility;

use App\Utility\Packers;

class Packer
{


// TODO: maybe later
//function storageKey_accountHash(int $id)
//{
//    return (string)$id;
//}
//
//function packerPackFieldNames($fields = [])
//{
//    return array_map(function ($key) {
//        switch ($key) {
//            case 'ed':
//                return 'email_domain';
//            case 'prs':
//                return 'premium_start';
//        }
//    }, $fields);
//}
//
//function packerUnpackFieldNames($fields = [])
//{
//    return array_map(function ($key) {
//        switch ($key) {
//            case 'email_domain':
//                return 'ed';
//            case 'prs':
//                return 'premium_start';
//            case 'prf':
//                return 'premium_finish';
//        }
//    }, $fields);
//}
//
///**
// * @param \Swoole\Coroutine\Redis $redis
// * @param int $id
// * @return mixed
// */
//function packerGetAccountRaw($redis, int $id, $fields = [])
//{
//    if (empty($fields))
//        $fields = ['ed', 'prs', 'prf', 'st', 's', 'fn', 'sn', 'ct', 'cr', 'b', 'p', 'j', 'int'];
//    else
//        $fields = packerPackFieldnames($fields);
//
//    return $redis->hMGet(storageKey_accountHash($id), $fields);
//}

    // ==========


    // =======


    /**
     * id и email добавляются сами
     * @param \Swoole\Coroutine\Redis $redis
     * @param int $id
     * @param array $fields
     * @return array
     */
    public static function getResultArray($redis, int $id, $fields = []): array
    {
        //$fields[] = 'email_name';
        $fields[] = 'email_domain';

        if (in_array('premium', $fields)) {
            $fields[] = 'premium_start';
            $fields[] = 'premium_finish';
            Helper::removeValueFromArray('premium', $fields);
        }

        $raw = $redis->mGet(array_map(function (string $field) use ($id) {
            $method = __NAMESPACE__ . '\StorageKey::' . $field;
            return $method($id);
        }, $fields));

        $result = array_filter(array_combine($fields, $raw), 'strlen');

        array_walk($result, function (&$value, string $field) use ($redis) {
            switch ($field) {
                case 'status':
                    $value = Packers\Status::unpack((string)$value);
                    break;
                case 'fname':
                    $value = Packers\Fname::unpack((string)$value);
                    break;
                case 'interests':
                    $value = Packers\Interests::unpack((string)$value);
                    break;
                case 'likes':
                    $value = Packers\Likes::unpack((string)$value);
                    break;
                case 'birth':
                    $value = (int)$value;
                    break;
                case 'country':
                    $value = Packers\Country::unpack((string)$value);
                    break;
                case 'city':
                    $value = Packers\City::unpack((string)$value);
                    break;
//                case 'email_name':
//                    $value = Packers\EmailName::unpack($redis, (int)$value);
//                    break;
                case 'email_domain':
                    $value = Packers\EmailDomain::unpack((string)$value);
                    break;
            }
        });

        $result['id'] = $id;

        if (!array_key_exists('email_domain', $result))
            var_dump($result);

        $find_email = $redis->zRangeByScore('pk:en', (string)$id, (string)$id);
        $result['email'] = array_shift($find_email) . '@' . $result['email_domain'];
        unset($result['email_domain']);
        unset($result['email_name']);

        if (array_key_exists('premium_start', $result)) {
            $result['premium'] = ['start' => (int)$result['premium_start'], 'finish' => (int)$result['premium_finish']];
            unset($result['premium_start']);
            unset($result['premium_finish']);
        }

        return $result;
    }

}