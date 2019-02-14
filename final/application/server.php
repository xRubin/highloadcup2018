<?php declare(strict_types=1);

date_default_timezone_set('UTC');
ini_set('memory_limit', '-1');

printf("[%s] go go go\n", date('Y-m-d H:i:s'));

use Swoole\Http\Request;
use Swoole\Http\Response;

include __DIR__ . '/includes/_exceptions.php';
include __DIR__ . '/includes/_util.php';
include __DIR__ . '/includes/_packer.php';
include __DIR__ . '/includes/_indexer.php';
include __DIR__ . '/includes/_storage.php';
include __DIR__ . '/includes/_router.php';

define('DATA_DIR', '/tmp/data');
define('DATA_UNPACKED_DIR', '/var/www/html/data');
$options = file_get_contents(DATA_DIR . '/options.txt');
list($time, ) = explode("\n", $options, 2);
define('TIME', $time);

define('STATUS_1', "свободны");
define('STATUS_2', "заняты");
define('STATUS_3', "всё сложно");


//Swoole\Runtime::enableCoroutine(true);
//
//Swoole\Coroutine::set([
//    'max_coroutine' => 4096,
//]);

$maxId = new Swoole\Atomic(0);

class RedisQueue
{
    public static function getRedis(): Redis
    {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->select(3);
        return $redis;
    }
}

$server = new \Swoole\Http\Server("0.0.0.0", 80, SWOOLE_BASE);
$server->set([
    'worker_num' => 4,
    //'daemonize' => 1,
    'dispatchMode' => SWOOLE_IPC_PREEMPTIVE,
    'open_cpu_affinity' => 1,
    'enable_port_reuse' => true,
    'open_tcp_nodelay' => true,
]);

$server->on('start', function() {
    printf("[%s] Start uploading\n", date('Y-m-d H:i:s'));
    ($redis = RedisQueue::getRedis())->flushAll();

    for ($i=1; $i < 100000; $i++) {
        $path = DATA_UNPACKED_DIR . '/accounts_' . $i. '.json';
        printf("[%s] loading %s\n", date('Y-m-d H:i:s'), $path);
        $pipe = $redis->multi(Redis::PIPELINE);

        $data = file_get_contents($path);
        if (!$data) {
            //unset($temp);
            unset($decoded);
            break;
        }
        $decoded = json_decode($data);

        /** @var Account $account */
        foreach ($decoded->accounts as $account) {
            storageAccountAdd($pipe, $redis, $account);
        }
        $pipe->exec();
        printf("[%s] %s loaded\n", date('Y-m-d H:i:s'), $path);
    }
    gc_collect_cycles();
    printf("[%s] Server ready\n", date('Y-m-d H:i:s'));
});

$server->on('request', function (Request $request, Response $response) {
    try {
        route($request, $response);
    } catch (Throwable $e) {
        var_dump([$request->server['request_uri'], $request->get, $e->getFile(), $e->getLine(), $e->getMessage()]);
        $response->header('Content-Type', 'application/json');
        $response->status(500);
        $response->end('{}');
    }
});

$server->start();

