<?php
namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\AbstractRouter;
use FastRoute\RouteCollector;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;


class Router extends AbstractRouter
{
    public function initialize(RouteCollector $routeCollector)
    {
//        $this->setGlobalMode(true);
        $this->setMethodNotAllowCallBack(function (Request $request,Response $response){
            $response->withStatus(404);
            $response->end();
        });
        $this->setRouterNotFoundCallBack(function (Request $request,Response $response){
            $response->withStatus(404);
            $response->end();
        });

        $routeCollector->get('/accounts/filter', '/Accounts/actionFilter');
        $routeCollector->get('/accounts/group', '/Accounts/actionGroup');
        $routeCollector->get('/accounts/{id:\d+}/suggest',  '/Accounts/actionSuggest');
        $routeCollector->get('/accounts/{id:\d+}/recommend',  '/Accounts/actionRecommend');

        $routeCollector->post('/accounts/new', '/Accounts/actionNew');
        $routeCollector->post('/accounts/likes', '/Accounts/actionLikes');
        $routeCollector->post('/accounts/{id:\d+}', '/Accounts/actionUpdate');
    }
}