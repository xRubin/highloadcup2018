<?php declare(strict_types=1);

date_default_timezone_set('UTC');
ini_set('memory_limit', '-1');
ini_set("default_socket_timeout", '-1');

printf("[%s] go go go\n", date('Y-m-d H:i:s'));

use Swoole\Http\Request;
use Swoole\Http\Response;

include __DIR__ . '/includes/_exceptions.php';
include __DIR__ . '/includes/_util.php';
include __DIR__ . '/includes/_packer.php';
include __DIR__ . '/includes/_indexer.php';
include __DIR__ . '/includes/_storage.php';
include __DIR__ . '/includes/_router.php';

include __DIR__ . '/includes/Loader.php';

define('DATA_DIR', '/tmp/data');
define('DATA_UNPACKED_DIR', '/var/www/html/data');
//define('DATA_DIR', __DIR__ . '/../../../data');
//define('DATA_UNPACKED_DIR', __DIR__ . '/../../../data/data');

$options = file_get_contents(DATA_DIR . '/options.txt');
list($time, ) = explode("\n", $options, 2);
define('TIME', $time);

define('STATUS_1', "свободны");
define('STATUS_2', "заняты");
define('STATUS_3', "всё сложно");

global $cities;
$cities = [];

$maxId = new Swoole\Atomic(0);
$maxCityId = new \Swoole\Atomic(0);

class RedisQueue
{
    public static function getRedis(): Redis
    {
        $redis = new Redis();
        $redis->pconnect('127.0.0.1', 6379, 600.0);
        //$redis->connect('/var/run/redis/redis.sock');
        $redis->select(3);
        return $redis;
    }
}

Swoole\Runtime::enableCoroutine(true);

Swoole\Coroutine::set([
    'max_coroutine' => 4096,
]);

$server = new \Swoole\Http\Server("0.0.0.0", 80, SWOOLE_PROCESS);
$server->set([
    'worker_num' => 32,
    //'daemonize' => 1,
    //'dispatchMode' => SWOOLE_IPC_PREEMPTIVE,
    'open_cpu_affinity' => 1,
    //'enable_port_reuse' => true,
    'open_tcp_nodelay' => true,

    'discard_timeout_request' => true,
    'dispatchMode' => 1,
    //'enable_reuse_port' => true,
    'log_level' => 5,
    'reactor_num' => 16, // swoole_base
]);

$server->on('start', function() use ($maxCityId) {
    printf("[%s] Server ready\n", date('Y-m-d H:i:s'));
});

$server->on('request', function (Request $request, Response $response) {
    try {
        route($request, $response);
    } catch (Throwable $e) {
        $response->header('Content-Type', 'application/json');
        $response->status(500);
        $response->end('{}');
        var_dump([$request->server['request_uri'], $request->get, $e->getFile(), $e->getLine(), $e->getMessage()]);
    }
});

printf("[%s] Start uploading\n", date('Y-m-d H:i:s'));
($redis = RedisQueue::getRedis())->flushAll();
$redis->close();
unset($redis);

for ($i=1; $i < 100000; $i++) {
    $path = DATA_UNPACKED_DIR . '/accounts_' . $i. '.json';
    printf("[%s] loading %s\n", date('Y-m-d H:i:s'), $path);
    $loader = new Loader($redis = RedisQueue::getRedis());

    $data = @file_get_contents($path);
    if (!$data) {
        //unset($temp);
        unset($decoded);
        break;
    }
    $decoded = json_decode($data);

    /** @var Account $account */
    foreach ($decoded->accounts as $account) {
        $loader->addAccount($account);
    }
    sleep(1);
    $redis->close();
    unset($redis);
    unset($loader);
    printf("[%s] %s loaded\n", date('Y-m-d H:i:s'), $path);
    printf("[%s] memory usage: %s\n", date('Y-m-d H:i:s'), getServerMemoryUsage());
    gc_collect_cycles();
}

$line = [];
foreach ($cities as $key => $value) {
    $line[] = $key;
    $line[] = $value;
}
$maxCityId->set(count($cities));
($redis = RedisQueue::getRedis())->zAdd('pk:c', ...$line);
$redis->close();
unset($redis);

$server->start();

