<?php

/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/18 12:05
 */
include_once dirname(__DIR__) . "/vendor/autoload.php";

use WechatProxy\OpenPlatform\ProxyServer\ProxyServer;
use WechatProxy\OpenPlatform\Support\Signature;
use WechatProxy\OpenPlatform\Support\ClientRepository;
use WechatProxy\OpenPlatform\Support\ProxyClientInfo;

$config = [
    'app_id' => '开放平台第三方平台 APPID',
    'secret' => '开放平台第三方平台 Secret',
    'token' => '开放平台第三方平台 Token',
    'aes_key' => '开放平台第三方平台 AES Key',
];

$baseUri = "/server";
$proxyServer = new ProxyServer($config);
$proxyServer->setBaseUri($baseUri);
$proxyServer['signature'] = function ($app) {
    return new Signature($app);
};
$proxyServer['clientRepository'] = function ($app) {
    return new ClientRepository($app);
};
$client = new ProxyClientInfo("ClientA", "a123456", "http://wx.yunyicheng.cn/client/event/callback");

$proxyServer->clientRepository->addClient($client);

$uriPath = explode("?", $_SERVER['REQUEST_URI'])[0];
$routeUri = substr($uriPath, strlen($baseUri));

switch ($routeUri) {
    case "/proxy/auth/show":
        $proxyServer->showAuth();
        break;
    case "/proxy/auth/start":
        $proxyServer->startAuthorization();
        break;
    case "/proxy/auth/callback":
        $proxyServer->authorizationCallback();
        break;
    case "/proxy/component/callback":
        $proxyServer->onComponentCallBack();
        break;
    default:
        if (preg_match('#/proxy/app/(.*?)/callback#', $routeUri, $matches)) {
            $proxyServer->onAppEventCallBack($matches[1]);
        } else {
            die("404");
        }
}

