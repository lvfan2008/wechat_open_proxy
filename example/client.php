<?php

/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/18 12:05
 */
include_once dirname(__DIR__) . "/vendor/autoload.php";

use WechatProxy\OpenPlatform\ProxyClient\OpenProxyClient;
use WechatProxy\OpenPlatform\Support\ProxyClientInfo;
use WechatProxy\OpenPlatform\Support\Signature;

$config = [
    'proxy_base_uri' => 'http://wx.yunyicheng.cn/server/',
    'proxy_auth_url' => 'http://wx.yunyicheng.cn/server/proxy/auth/show'
];
$client = new ProxyClientInfo("ClientA", "a123456", "http://wx.yunyicheng.cn/client/event/callback");

$baseUri = "/client";
$openClient = new OpenProxyClient($config);
$openClient->setProxyClientInfo($client);
$openClient['signature'] = function ($app) {
    return new Signature($app);
};

$uriPath = explode("?", $_SERVER['REQUEST_URI'])[0];
$routeUri = substr($uriPath, strlen($baseUri));

switch ($routeUri) {
    case "/test/api":
        {
            $appId = $_GET['app_id'];
            $result = $openClient->getAccount($appId, false)->base->getValidIps();
            print_r($result);
        }
        break;
    case "/start/auth":
        {
            $param = ['shop_id' => 2];
            $authUrl = $openClient->getProxyAuthUrl($param);
            echo "<a href=\"{$authUrl}\" >授权</a>";
        }
        break;
    case "/event/callback":
        print_r($_GET);
        if ($_GET['type'] == 'auth_callback') {
            $clientParam = $openClient->parseParam($_GET['client_param']);
            if (!$openClient->verifySign($clientParam)) {
                die("signed failed!");
            }
            $authorizer = $openClient->getAuthorizer($_GET['app_id']);
            print_r($authorizer);
        } else if ($_GET['type'] == 'component_event') {
            $openClient->onComponentEvent();
        } else if ($_GET['type'] == 'component_event') {
            $openClient->onAppEvent($_GET['app_id']);
        }
        break;
}

