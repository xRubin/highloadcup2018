<?php declare(strict_types=1);

use Swoole\Http\Request;
use Swoole\Http\Response;

include __DIR__ . '/_resolver_filter.php';
include __DIR__ . '/_resolver_group.php';
include __DIR__ . '/_resolver_recommend.php';
include __DIR__ . '/_resolver_suggest.php';
include __DIR__ . '/_updater_new.php';
include __DIR__ . '/_updater_likes.php';
include __DIR__ . '/_updater_update.php';

function route(Request $request, Response $response)
{
    try {
        if ($request->server['request_method'] == 'GET') {
            switch ($request->server['request_uri']) {
                case '/accounts/filter/':
                    $response->header('Content-Type', 'application/json');
                    $response->end(json_encode(resolverAccountFilter($request->get), JSON_UNESCAPED_UNICODE));
                    break;
                case '/accounts/group/':
                    $response->header('Content-Type', 'application/json');
                    $response->end(json_encode(resolverAccountGroup($request->get), JSON_UNESCAPED_UNICODE));
                    break;
                default:
                    if (preg_match('/^\/accounts\/(\d+)\/recommend\/$/', $request->server['request_uri'], $matches)) {
                        $response->header('Content-Type', 'application/json');
                        $response->end(json_encode(resolverAccountsRecommend((int)$matches[1], $request->get), JSON_UNESCAPED_UNICODE));
                        break;
                    }

                    if (preg_match('/^\/accounts\/(\d+)\/suggest\/$/', $request->server['request_uri'], $matches)) {
                        parse_str($request->server['query_string'], $query);
                        $response->header('Content-Type', 'application/json');
                        $response->end(json_encode(resolverAccountsSuggest((int)$matches[1], $request->get), JSON_UNESCAPED_UNICODE));
                        break;
                    }

                    throw new Exception404('Unknown request: ' . $request->server['request_uri']);
            }
        } elseif ($request->server['request_method'] == 'POST') {
            switch ($request->server['request_uri']) {
                case '/accounts/new/':
                    $response->status(201);
                    $response->header('Content-Type', 'application/json');
                    $response->end(json_encode(updaterAccountNew(json_decode($request->rawcontent())), JSON_UNESCAPED_UNICODE));
                    break;
                case '/accounts/likes/':
                    $response->status(202);
                    $response->header('Content-Type', 'application/json');
                    $response->end(json_encode(updaterAccountsLikes(json_decode($request->rawcontent())), JSON_UNESCAPED_UNICODE));
                    break;
                default:
                    if (preg_match('/^\/accounts\/(\d+)\/$/', $request->server['request_uri'], $matches)) {
                        $response->status(202);
                        $response->header('Content-Type', 'application/json');
                        $response->end(json_encode(updaterAccountUpdate((int)$matches[1], json_decode($request->rawcontent())), JSON_UNESCAPED_UNICODE));
                        break;
                    }
                    throw new Exception404('Unknown request: ' . $request->server['request_uri']);
            }
        } else
            throw new Exception404('Unknown request: ' . $request->request);

    } catch (ControlledException $e) {
        $response->header('Content-Type', 'application/json');
        $response->status($e->httpCode);
        $response->end('');
    }
}