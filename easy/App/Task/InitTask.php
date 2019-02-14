<?php

namespace App\Task;

use EasySwoole\EasySwoole\Swoole\Task\AbstractAsyncTask;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;

class InitTask extends AbstractAsyncTask
{
    protected function run($taskData, $taskId, $fromWorkerId, $flags = null)
    {
        printf("[%s] loading %s\n", date('Y-m-d H:i:s'), $taskData);

        $data = @file_get_contents($taskData);
        $decoded = json_decode($data);

        array_map(function($set) {
            TaskManager::processAsync(new AddAccountTask($set));
        }, array_chunk($decoded->accounts, 50));

//        TaskManager::barrier(array_map(function ($account) {
//            return new AddAccountTask($account);
//        }, $decoded->accounts), 600);
//        foreach ($decoded->accounts as $account) {
//            TaskManager::processAsync(new AddAccountTask($account));
//        }

        printf("[%s] %s loaded\n", date('Y-m-d H:i:s'), $taskData);

        return true;
    }

    function finish($result, $task_id)
    {
        return 1;
    }
}