<?php declare(strict_types=1);

function updaterAccountNew(stdClass $data)
{
    if (!isset($data->id))
        throw new Exception400('Id required');

    if (!isset($data->status))
        throw new Exception400('Status required');

    if (isset($data->status) && !in_array($data->status, [STATUS_1, STATUS_2, STATUS_3]))
        throw new Exception400('Invalid status ' . $data->status);

    if (!isset($data->email))
        throw new Exception400('Email required');

    if (!isset($data->joined))
        throw new Exception400('Joined required');

    if (!isset($data->sex))
        throw new Exception400('Sex required :)');

    if (!in_array($data->sex, ['m', 'f']))
        throw new Exception400('Unsupported sex value');

    if (!isset($data->birth)) {
        throw new Exception400('Birth required');
    } elseif (!is_int($data->birth))
        throw new Exception400('Wrong birth');

    $redis = RedisQueue::getRedis();

    if ($redis->exists(storageKey_email_domain($data->id))) {
        $redis->close();
        unset($redis);
        throw new Exception400('Invalid id');
    }

    if (isset($data->likes)) {
        /** @var Like $like */
        foreach ($data->likes as $like) {
            if (!is_int($like->ts)) {
                $redis->close();
                unset($redis);
                throw new Exception400('Wrong like ts');
            }

            if (!is_int($like->id)) {
                $redis->close();
                unset($redis);
                throw new Exception400('Wrong like id ' . $like->id);
            }

            if (!$redis->exists(storageKey_email_domain($like->id))) {
                $redis->close();
                unset($redis);
                throw new Exception400('Wrong like id ' . $like->id);
            }
        }
    }

    // TODO: !!! check email exist
//    foreach ($this->storage->accounts as $somebody)
//        if ($somebody['email'] == $data->email)
//            throw new Exception400('Duplicate email ' . $data->email);

    storageAccountAdd($redis, $data);

    unset($redis);

    return [];
}