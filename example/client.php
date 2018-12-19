<?php

/**
 * 客户端测试代码
 *
 * User: lv_fan2008@sina.com
 * Time: 2018/12/18 12:05
 */
include_once dirname(__FILE__) . "/bootstrap.php";

use WechatProxy\OpenPlatform\ProxyClient\OpenProxyClient;
use WechatProxy\OpenPlatform\Support\ProxyClientInfo;

$config = require_once dirname(__FILE__) . "/config/client_config.php";
$clientInfo = require_once dirname(__FILE__) . "/config/client_info.php";

$openClient = new OpenProxyClient($config);
$client = new ProxyClientInfo(...$clientInfo);
$openClient->setProxyClientInfo($client);

$routeUri = explode("?", $_SERVER['REQUEST_URI'])[0];

switch ($routeUri) {
    case "/client/test/api/getips":
        {
            if (!isset($_GET['app_id'])) {
                echo "app_id不存在，请在URL中添加已授权的app_id参数";
                exit;
            }
            $appId = $_GET['app_id'];
            $result = $openClient->getAccount($appId, false)->base->getValidIps();
            print_r($result);
            break;
        }

    case "/client/start/auth":
        {
            $param = ['shop_id' => 2];
            $authUrl = $openClient->getProxyAuthUrl($param);
            echo "<a href=\"{$authUrl}\" >授权</a>";
            break;
        }

    case "/client/event/callback":
        {
            print_r($_GET);
            if ($_GET['type'] == 'auth_callback') {
                $clientParam = $openClient->parseParam($_GET['client_param']);
                if (!$openClient->verifySign($clientParam)) {
                    print_r($clientParam);
                    die("signed failed!");
                }
                $authorizer = $openClient->getAuthorizer($_GET['app_id']);
                var_dump($authorizer);
            } else if ($_GET['type'] == 'component_event') {
                $openClient->onComponentEvent();
            } else if ($_GET['type'] == 'app_event') {
                $openClient->onAppEvent($_GET['app_id']);
            }
            break;
        }

    default:
        $param = ['shop_id' => 2];
        $authUrl = $openClient->getProxyAuthUrl($param);
        echo "<a href=\"{$authUrl}\" >测试授权</a>";

}

