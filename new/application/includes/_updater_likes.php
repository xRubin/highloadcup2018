<?php declare(strict_types=1);

function updaterAccountsLikes($data)
{
    $redis = RedisQueue::getRedis();

    /** @var LikePost $like */
    foreach ($data->likes as $like) {
        if (!is_int($like->liker))
            throw new Exception400('Invalid liker');
        if (!is_int($like->likee))
            throw new Exception400('Invalid likee');
        if (!is_int($like->ts))
            throw new Exception400('Invalid ts');
        if ($like->ts > TIME)
            throw new Exception400('Invalid ts');

        if (!$redis->exists(storageKey_email_domain($like->liker))) {
            $redis->close();
            unset($redis);
            throw new Exception400('Unknown account ' . $like->liker);
        }
        if (!$redis->exists(storageKey_email_domain($like->likee))) {
            $redis->close();
            unset($redis);
            throw new Exception400('Unknown account ' . $like->likee);
        }
    }

    foreach ($data->likes as $like) {
        $redis->sAdd(indexerKey_accountLiked($like->likee), $like->liker);
        $likes =  @(array)unpack_likes($redis->get(storageKey_likes($like->liker)));
        $likes[] = ['ts' => $like->ts, 'id' => $like->likee];
        $redis->set(storageKey_likes($like->liker), pack_likes($likes));
    }

    $redis->close();
    unset($redis);

    return [];
}