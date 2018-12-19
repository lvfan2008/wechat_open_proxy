<?php

/**
 * Description
 * User: lv_fan2008@sina.com
 * Time: 2018/12/18 12:05
 */
include_once dirname(__FILE__) . "/bootstrap.php";

use WechatProxy\OpenPlatform\ProxyServer\ProxyServer;
use WechatProxy\OpenPlatform\Support\ProxyClientInfo;

$config = require_once dirname(__FILE__) . "/config/server_config.php";
$clientInfo = require_once dirname(__FILE__) . "/config/client_info.php";

$proxyServer = new ProxyServer($config);
$client = new ProxyClientInfo(...$clientInfo);
$proxyServer->clientRepository->addClient($client);

$routeUri = explode("?", $_SERVER['REQUEST_URI'])[0];

switch ($routeUri) {
    case "/server/proxy/auth/show":
        $proxyServer->showAuth();
        break;
    case "/server/proxy/auth/start":
        $proxyServer->startAuthorization();
        break;
    case "/server/proxy/auth/callback":
        $proxyServer->authorizationCallback();
        break;
    case "/server/proxy/component/callback":
        $proxyServer->onComponentCallBack();
        break;
    default:
        if (preg_match('#/server/proxy/api/(.*)#', $routeUri, $matches)) {
            $proxyServer->proxy();
        } else if (preg_match('#/server/proxy/app/(.*?)/callback#', $routeUri, $matches)) {
            $proxyServer->onAppEventCallBack($matches[1]);
        } else {
            die("404");
        }
}

