<?php

namespace App\HttpController\traits;

use App\Utility\Exceptions\Exception400;
use App\Utility\StorageKey;
use App\Utility\IndexerKey;
use Swoole\Coroutine\Redis;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use App\Utility\Packers\Likes;

/**
 * Trait AccountsLikes
 * @method Redis getRedis()
 * @method Request request()
 * @method Response response()
 */
trait AccountsLikes
{
    public function actionLikes()
    {
        $params = $this->request()->getQueryParams();
        $query_id = $params['query_id'];
        unset($params['query_id']);

        $data = json_decode($this->request()->getBody());

        $redis = $this->getRedis();

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

            if (!$redis->exists(StorageKey::email_domain($like->liker)))
                throw new Exception400('Unknown account ' . $like->liker);

            if (!$redis->exists(StorageKey::email_domain($like->likee)))
                throw new Exception400('Unknown account ' . $like->likee);

        }

        foreach ($data->likes as $like) {
            $redis->sAdd(IndexerKey::account_liked($like->likee), $like->liker);
            $likes =  @(array)Likes::unpack($redis->get(StorageKey::likes($like->liker)));
            $likes[] = ['ts' => $like->ts, 'id' => $like->likee];
            $redis->set(StorageKey::likes($like->liker), Likes::pack($likes));
        }

        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->withStatus(202);
        $this->response()->write('{}');
        $this->response()->end();
    }
}