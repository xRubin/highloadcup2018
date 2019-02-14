<?php declare(strict_types=1);

include __DIR__ . '/_packer_city.php';
include __DIR__ . '/_packer_country.php';
include __DIR__ . '/_packer_email.php';
include __DIR__ . '/_packer_domain.php';
include __DIR__ . '/_packer_fname.php';
include __DIR__ . '/_packer_interest.php';

{
    function unpack_status($value): string
    {
        switch ($value) {
            case 0:
                return STATUS_1;
            case 1:
                return STATUS_2;
            case 2:
                return STATUS_3;
        }
    }

    function pack_status($value): int
    {
        switch ($value) {
            case STATUS_1:
                return 0;
            case STATUS_2:
                return 1;
            case STATUS_3:
                return 2;
        }
    }
}

{
    function pack_likes(array $data): string
    {
        return implode('|', array_map(function ($like) {
            /** @var Like $like */
            return is_array($like) ? ($like['id'] . ':' . $like['ts']) : ($like->id . ':' . $like->ts);
        }, $data));
    }

    function unpack_likes($value = null): ?array
    {
        if (empty($value))
            return null;

        return array_map(function ($data) {
            list($id, $ts) = explode(':', $data, 2);
            return [
                'id' => (int)$id,
                'ts' => (int)$ts,
            ];
        }, explode('|', $value));
    }
}

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

/**
 * id и email добавляются сами
 * @param \Swoole\Coroutine\Redis $redis
 * @param int $id
 * @param array $fields
 * @return array
 */
function packerGetResultArray($redis, int $id, $fields = []): array
{
    $fields[] = 'email_name';
    $fields[] = 'email_domain';

    if (in_array('premium', $fields)) {
        $fields[] = 'premium_start';
        $fields[] = 'premium_finish';
        removeValueFromArray('premium', $fields);
    }

    $raw = $redis->mGet(array_map(function (string $field) use ($id) {
        $method = 'storageKey_' . $field;
        return $method($id);
    }, $fields));

    $result = array_filter(array_combine($fields, $raw), 'strlen');

    array_walk($result, function (&$value, string $field) use ($redis) {
        switch ($field) {
            case 'status':
                $method = 'unpack_' . $field;
                $value = $method($value);
                break;
            case 'fname':
            case 'email_domain':
            case 'country':
            case 'interests':
            case 'likes':
                $method = 'unpack_' . $field;
                $value = $method((string)$value);
                break;
            case 'birth':
                $value = (int)$value;
                break;
            case 'city':
            case 'email_name':
                $method = 'unpack_' . $field;
                $value = $method($redis, (int)$value);
                break;
        }
    });

    $result['id'] = $id;

    $find_email = $redis->zRangeByScore('pk:en', (string)$id, (string)$id);
    $result['email'] = array_shift($find_email) . '@' . $result['email_domain'];
    unset($result['email_domain']);

    if (array_key_exists('premium_start', $result)) {
        $result['premium'] = ['start' => (int)$result['premium_start'], 'finish' => (int)$result['premium_finish']];
        unset($result['premium_start']);
        unset($result['premium_finish']);
    }

    return $result;
}
