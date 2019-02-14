<?php

namespace App\HttpController\traits;

use App\Utility\Exceptions\Exception400;
use App\Utility\Loader;
use App\Utility\Packers\EmailDomain;
use App\Utility\Packers\EmailName;
use App\Utility\StorageKey;
use Swoole\Coroutine\Redis;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

/**
 * Trait AccountsNew
 * @method Redis getRedis()
 * @method Request request()
 * @method Response response()
 */
trait AccountsNew
{
    public function actionNew()
    {
        $params = $this->request()->getQueryParams();
        $query_id = $params['query_id'];
        unset($params['query_id']);

        $data = json_decode($this->request()->getBody());

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

        $redis = $this->getRedis();

        if ($redis->exists(StorageKey::email_domain($data->id)))
            throw new Exception400('Invalid id');


        if (isset($data->likes)) {
            /** @var Like $like */
            foreach ($data->likes as $like) {
                if (!is_int($like->ts))
                    throw new Exception400('Wrong like ts');


                if (!is_int($like->id))
                    throw new Exception400('Wrong like id ' . $like->id);


                if (!$redis->exists(StorageKey::email_domain($like->id)))
                    throw new Exception400('Wrong like id ' . $like->id);

            }
        }

        list($email_name, $email_domain) = explode('@', $data->email);
        if ($pr = (int)$redis->zScore('pk:en', $email_name)) {
            if ($redis->get(StorageKey::email_domain($pr)) == EmailDomain::pack($email_domain))
                throw new Exception400('Duplicate email');
        }

        $loader = new Loader($redis);
        $loader->addAccount($data);

        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->withStatus(201);
        $this->response()->write('{}');
        $this->response()->end();
    }
}