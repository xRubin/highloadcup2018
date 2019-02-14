<?php declare(strict_types=1);

date_default_timezone_set('UTC');
ini_set('memory_limit', '-1');
ini_set("default_socket_timeout", '-1');

use Swoole\Http\Request;
use Swoole\Http\Response;

define('STATUS_1', "свободны");
define('STATUS_2', "заняты");
define('STATUS_3', "всё сложно");

define('DATA_DIR', '/tmp/data');
define('DATA_UNPACKED_DIR', '/var/www/html/data');

$options = file_get_contents(DATA_DIR . '/options.txt');
list($time, ) = explode("\n", $options, 2);
define('TIME', $time);

$maxId = new Swoole\Atomic(0);

function checkLimit($get): bool
{
    $limit = @$get['limit'];
    if (!is_numeric($limit))
        return false;
    $limit = (int)$limit;
    if ($limit < 1)
        return false;
    return true;
}

function checkFields($get, array $variants): bool
{
    return count(array_diff(array_keys($get), $variants)) == 0;
}

function checkNew($data): bool
{
    if (!isset($data->id))
        return false;

    if (!isset($data->status))
        return false;

    if (isset($data->status) && !in_array($data->status, [STATUS_1, STATUS_2, STATUS_3]))
        return false;

    if (!isset($data->email))
        return false;

    if (!isset($data->joined))
        return false;

    if (!isset($data->sex))
        return false;

    if (!in_array($data->sex, ['m', 'f']))
        return false;

    if (!isset($data->birth)) {
        return false;
    } elseif (!is_int($data->birth))
        return false;

    return true;
}

function checkUpdate($data): bool
{
    foreach ($data as $key => $value) {
        switch ($key) {
            case 'sex':
                if (!in_array($value, ['m', 'f']))
                    return false;
                break;
            case 'status':
                if (!in_array($value, [STATUS_1, STATUS_2, STATUS_3]))
                    return false;
                break;
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL))
                    return false;
                break;
            case 'country':
                break;
            case 'city':
                break;
            case 'premium':
                if (!isset($value->start) || !is_int($value->start) || !isset($value->finish) || !is_int($value->finish))
                    return false;
                break;
            case 'fname':
                break;
            case 'sname':
                break;
            case 'interests':
                break;
            case 'phone':
                break;
            default:
                return false;
        }
    }

    return true;
}

function checkLikes($data): bool
{
    global $maxId;

    foreach ($data->likes as $like) {
        if (!is_int($like->liker))
            return false;
        if (!is_int($like->likee))
            return false;
        if (!is_int($like->ts))
            return false;
        if ($like->ts > TIME)
            return false;

        if ($maxId->get() < $like->liker)
            return false;

        if ($maxId->get() < $like->likee)
            return false;
    }

    return true;
}

$server = new \Swoole\Http\Server("0.0.0.0", 80, SWOOLE_PROCESS);
$server->set([
    'worker_num' => 16,
    //'daemonize' => 1,
    //'dispatchMode' => SWOOLE_IPC_PREEMPTIVE,
    'open_cpu_affinity' => 1,
    'enable_port_reuse' => true,
    'open_tcp_nodelay' => true,

    //'discard_timeout_request' => true,
    'dispatchMode' => 1,
    'enable_reuse_port' => true,
    'log_level' => 5,
    'reactor_num' => 16, // swoole_base
]);

$server->on('start', function () use ($server, $maxId) {
    for ($i = 1; $i < 100000; $i++) {
        $path = DATA_UNPACKED_DIR . '/accounts_' . $i . '.json';
        printf("[%s] loading %s\n", date('Y-m-d H:i:s'), $path);

        $data = file_get_contents($path);
        if (!$data) {
            //unset($temp);
            unset($decoded);
            break;
        }
        $decoded = json_decode($data);

        /** @var Account $account */
        foreach ($decoded->accounts as $account) {
            if ($account->id > $maxId->get())
                $maxId->set($account->id);
        }
        //$pipe->exec();
        printf("[%s] %s loaded\n", date('Y-m-d H:i:s'), $path);
    }

    gc_collect_cycles();

    printf("[%s] Server ready on port %s\n", date('Y-m-d H:i:s'), $server->port);
});

$server->on('request', function (Request $request, Response $response) use ($maxId) {
    $response->header('Content-Type', 'application/json');

    if ($request->server['request_method'] == 'GET') {
        switch ($request->server['request_uri']) {
            case '/accounts/filter/':
                if (checkLimit($request->get) && checkFields($request->get, [
                        'query_id',
                        'limit',
                        'sex_eq',
                        'email_domain',
                        'email_gt',
                        'email_lt',
                        'status_eq',
                        'status_neq',
                        'fname_eq',
                        'fname_any',
                        'fname_null',
                        'sname_eq',
                        'sname_starts',
                        'sname_null',
                        'phone_code',
                        'phone_null',
                        'country_eq',
                        'country_null',
                        'city_eq',
                        'city_any',
                        'city_null',
                        'birth_lt',
                        'birth_gt',
                        'birth_year',
                        'interests_contains',
                        'interests_any',
                        'likes_contains',
                        'premium_now',
                        'premium_null',
                    ])) {
                    $response->end('{"accounts":[]}');
                } else {
                    $response->status(400);
                    $response->end('');
                }
                break;
            case '/accounts/group/':
                if (checkLimit($request->get) && checkFields($request->get, [
                        'keys',
                        'order',
                        'query_id',
                        'limit',
                        'sex',
                        'country',
                        'city',
                        'status',
                        'interests',
                        'likes',
                        'joined',
                        'birth'
                    ])) {
                    $response->end('{"groups":[]}');
                } else {
                    $response->status(400);
                    $response->end('');
                }
                break;
            default:
                if (preg_match('/^\/accounts\/(\d+)\/recommend\/$/', $request->server['request_uri'], $matches)) {
                    if (checkLimit($request->get) && checkFields($request->get, [
                            'query_id',
                            'limit',
                            'country',
                            'city'
                        ])) {
                        if ((int)$matches[1] < $maxId->get()) {
                            $response->end('{"accounts":[]}');
                        } else {
                            $response->status(404);
                            $response->end('');
                        }
                    } else {
                        $response->status(400);
                        $response->end('');
                    }
                    break;
                }

                if (preg_match('/^\/accounts\/(\d+)\/suggest\/$/', $request->server['request_uri'], $matches)) {
                    if (checkLimit($request->get) && checkFields($request->get, [
                            'query_id',
                            'limit',
                            'country',
                            'city'
                        ])) {
                        if ((int)$matches[1] < $maxId->get()) {
                            $response->end('{"accounts":[]}');
                        } else {
                            $response->status(404);
                            $response->end('');
                        }
                    } else {
                        $response->status(400);
                        $response->end('');
                    }
                    break;
                }

                $response->status(404);
                $response->end('');
        }
    } elseif ($request->server['request_method'] == 'POST') {
        switch ($request->server['request_uri']) {
            case '/accounts/new/':
                $data = json_decode($request->rawcontent());
                if (checkNew($data)) {
                    $response->status(201);
                    $response->end('{}');
                    if ($maxId->get() < $data->id)
                        $maxId->set((int)$data->id);
                } else {
                    $response->status(400);
                    $response->end('');
                }
                break;
            case '/accounts/likes/':
                if (checkLikes(json_decode($request->rawcontent()))) {
                    $response->status(202);
                    $response->end('{}');
                } else {
                    $response->status(400);
                    $response->end('');
                }
                break;
            default:
                if (preg_match('/^\/accounts\/(\d+)\/$/', $request->server['request_uri'], $matches)) {
                    if ((int)$matches[1] < $maxId->get()) {
                        if (checkUpdate(json_decode($request->rawcontent()))) {
                            $response->status(202);
                            $response->end('{}');
                        } else {
                            $response->status(400);
                            $response->end('');
                        }
                    } else {
                        $response->status(404);
                        $response->end('');
                    }
                    break;
                }
                $response->status(404);
                $response->end('');
        }
    } else {
        $response->status(404);
        $response->end('');
    }
});

$server->start();

