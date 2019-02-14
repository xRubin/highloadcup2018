<?php
namespace App\Process;
use App\Task\AddAccountTask;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;

class ProcessAddAccount extends AbstractProcess
{
    public function run($arg)
    {
        for ($i = 1; $i < 100000; $i++) {
            $path = DATA_UNPACKED_DIR . '/accounts_' . $i . '.json';

            if (file_exists($path)) {
                $data = @file_get_contents($path);
                $decoded = json_decode($data);
                foreach ($decoded->accounts as $account)
                    TaskManager::processAsync(new AddAccountTask($account));
            }else
                break;
        }
        return true;
    }
    public function onShutDown()
    {
        echo "task_test_process is onShutDown.\n";
        // TODO: Implement onShutDown() method.
    }
    public function onReceive(string $str)
    {
        echo "task_test_process is onReceive.\n";
        // TODO: Implement onReceive() method.
    }
}