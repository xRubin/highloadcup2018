<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/28
 * Time: 下午6:33
 */

namespace EasySwoole\EasySwoole;

use App\Process\ProcessInit;
use EasySwoole\Component\TableManager;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Component\Pool\PoolManager;
use App\Utility\Pool\RedisPool;
use EasySwoole\Component\AtomicManager;
use Swoole\Table;
use EasySwoole\Component\Di;

define('DATA_DIR', '/tmp/data');
define('DATA_UNPACKED_DIR', '/var/www/html/data');
//define('DATA_DIR', __DIR__ . '/../../data/291218/data');
//define('DATA_UNPACKED_DIR', __DIR__ . '/../../data/data');
define('TABLE_DUMP_PATH', __DIR__ . '/dump.json');

$options = file_get_contents(DATA_DIR . '/options.txt');
list($time,) = explode("\n", $options, 2);
define('TIME', $time);

define('STATUS_1', "свободны");
define('STATUS_2', "заняты");
define('STATUS_3', "всё сложно");

class EasySwooleEvent implements Event
{

    /**
     * @throws \EasySwoole\Component\Pool\Exception\PoolObjectNumError
     */
    public static function initialize()
    {
        // TODO: Implement initialize() method.
        //date_default_timezone_set('Asia/Shanghai');
        date_default_timezone_set('UTC');
        ini_set('memory_limit', '-1');
        ini_set("default_socket_timeout", '-1');

        Di::getInstance()->set(SysConst::HTTP_CONTROLLER_POOL_MAX_NUM, 2047);

        PoolManager::getInstance()->register(RedisPool::class, Config::getInstance()->getConf('REDIS.POOL_MAX_NUM'))->setMinObjectNum((int)Config::getInstance()->getConf('REDIS.POOL_MIN_NUM'));

        AtomicManager::getInstance()->add('maxId');

        TableManager::getInstance()->add(
            'email_domain',
            [
                'name' => ['type' => Table::TYPE_STRING, 'size' => 50],
            ],
            31
        );

        TableManager::getInstance()->add(
            'fname',
            [
                'name' => ['type' => Table::TYPE_STRING, 'size' => 50],
            ],
            127
        );

        TableManager::getInstance()->add(
            'country',
            [
                'name' => ['type' => Table::TYPE_STRING, 'size' => 50],
            ],
            255
        );

        TableManager::getInstance()->add(
            'city',
            [
                'name' => ['type' => Table::TYPE_STRING, 'size' => 50],
            ],
            511
        );

        TableManager::getInstance()->add(
            'interest',
            [
                'name' => ['type' => Table::TYPE_STRING, 'size' => 50],
            ],
            127
        );
    }

    public static function mainServerCreate(EventRegister $register)
    {
        // TODO: Implement mainServerCreate() method.
        $register->add($register::onWorkerStart, function (\swoole_server $server, int $workerId) {
            if ($server->taskworker == false) {
                PoolManager::getInstance()->getPool(RedisPool::class)->preLoad((int)Config::getInstance()->getConf('REDIS.POOL_MIN_NUM'));
            }
        });

        $swooleServer = ServerManager::getInstance()->getSwooleServer();
        $swooleServer->addProcess((new ProcessInit('init'))->getProcess());
        //$swooleServer->addProcess((new ProcessAddAccount('add_account'))->getProcess());
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }
}