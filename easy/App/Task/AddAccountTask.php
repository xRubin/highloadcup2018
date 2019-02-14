<?php

namespace App\Task;

use App\Utility\Loader;
use App\Utility\Pool\RedisPool;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\EasySwoole\Swoole\Task\AbstractAsyncTask;
use Swoole\Coroutine\Redis;

class AddAccountTask extends AbstractAsyncTask
{
    protected function run($taskData, $taskId, $fromWorkerId, $flags = null)
    {
        /** @var Redis $redis */
        $redis = PoolManager::getInstance()->getPool(RedisPool::class)->getObj(300);
        $loader = new Loader($redis);
        foreach ($taskData as $account) {
            $loader->addAccount($account);
        }
        return PoolManager::getInstance()->getPool(RedisPool::class)->unsetObj($redis);
    }

    function finish($result, $task_id)
    {
        return 1;
    }
}