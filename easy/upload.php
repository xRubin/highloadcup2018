<?php

require dirname(__FILE__) . '/vendor/autoload.php';

use App\Utility\Preloader;

date_default_timezone_set('UTC');
ini_set('memory_limit', '-1');
//ini_set("default_socket_timeout", '-1');

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

$redis = new Redis();
$loader = new Preloader($redis);

printf("[%s] Start uploading\n", date('Y-m-d H:i:s'));

for ($i = 1; $i < 100000; $i++) {
    $path = DATA_UNPACKED_DIR . '/accounts_' . $i . '.json';
    printf("[%s] loading %s\n", date('Y-m-d H:i:s'), $path);

    if (!file_exists($path))
        break;

    $redis->connect('127.0.0.1', 6379, 600.0);

    $data = file_get_contents($path);
    $decoded = json_decode($data);

    /** @var Account $account */
    foreach ($decoded->accounts as $account) {
        a:
        try {
            $loader->addAccount($account);
        } catch (Throwable $e) {
            $redis->close();
            sleep(5);
            $redis->connect('127.0.0.1', 6379, 600.0);
            goto a;
        }
    }

    $redis->close();
    printf("[%s] %s loaded\n", date('Y-m-d H:i:s'), $path);
}

$loader->dump(TABLE_DUMP_PATH);
printf("[%s] dumping complete\n", date('Y-m-d H:i:s'));