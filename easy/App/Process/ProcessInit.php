<?php
namespace App\Process;
use App\Task\InitTask;
use EasySwoole\Component\AtomicManager;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\Component\TableManager;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;

class ProcessInit extends AbstractProcess
{
    public function run($arg)
    {
        $data = json_decode(file_get_contents(TABLE_DUMP_PATH));

        AtomicManager::getInstance()->get('maxId')->set((int)$data->maxId);

        $city_table = TableManager::getInstance()->get('city');
        foreach ($data->cities as $key => $value)
            $city_table->set((string)$key, ['name' => $value]);

        $country_table = TableManager::getInstance()->get('country');
        foreach ($data->countries as $key => $value)
            $country_table->set((string)$key, ['name' => $value]);

        $domain_table = TableManager::getInstance()->get('email_domain');
        foreach ($data->domains as $key => $value)
            $domain_table->set((string)$key, ['name' => $value]);

        $fname_table = TableManager::getInstance()->get('fname');
        foreach ($data->fnames as $key => $value)
            $fname_table->set((string)$key, ['name' => $value]);

        $interest_table = TableManager::getInstance()->get('interest');
        foreach ($data->interests as $key => $value)
            $interest_table->set((string)$key, ['name' => $value]);

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