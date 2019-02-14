<?php

require dirname(__FILE__) . '/vendor/autoload.php';

use App\Utility\Preloader;
use App\Utility\IndexerKey;

date_default_timezone_set('UTC');
ini_set('memory_limit', '-1');
//ini_set("default_socket_timeout", '-1');

$redis = new Redis();
$loader = new Preloader($redis);

echo IndexerKey::premium_now() . PHP_EOL;

return 0;