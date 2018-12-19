<?php
/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/18 10:32
 */

namespace WechatProxy\OpenPlatform\ProxyServer;


class ProxyServer extends WechatProxy
{
    protected $baseUri = "";

    /**
     * @param string $baseUri
     */
    public function setBaseUri(string $baseUri)
    {
        $this->baseUri = $baseUri;
    }


    /**
     * 显示授权页面
     */
    public function showAuth()
    {
        if ($this->verifyAuthUrl()) {
            $authUrl = $this->baseUri . "/proxy/auth/start?" . http_build_query($_GET);
            $html = <<<EOF
<html>
<head>
 <meta charset="utf-8">
</head>
<body>
<style>
a {
font-size: 16px;
padding: 10px 20px;;
}
</style>
<a href="{$authUrl}" id="auth" >正在登录授权中... &nbsp; <b id="sec">5秒</b></a>
<script>
setTimeout(function() {
  document.getElementById("auth").click();
  var sec = 5;
  var timer = setInterval(function() {
      sec--;
      var el = document.getElementById("sec").innerText = sec+"秒"
    if(sec == 0){
        clearInterval(timer);
        document.getElementById("auth").innerText = "请手动点击授权 >>> ";
    }
  },1000);
},100);
</script>
</body>
</html>
EOF;
            echo $html;
        } else {
            die("invalid request!");
        }
    }

    /**
     * 验证URL
     *
     * @return bool
     */
    public function verifyAuthUrl()
    {
        $clientParam = $this->parseParam($_GET['client_param']);
        if (!isset($clientParam['cb_url']) && !isset($clientParam['client_id']))
            return false;
        $client = $this->clientRepository->getClient($clientParam['client_id']);
        if (!$client) return false;
        $result = $this->signature->verifySign($client->key, $clientParam);
        return $result;
    }


    /**
     * 开始授权
     */
    public function startAuthorization()
    {
        if ($this->verifyAuthUrl()) {
            $authUrl = $this->getPreAuthorizationUrl("{$this->baseUri}/proxy/auth/callback?" . http_build_query($_GET));
            header("Location: {$authUrl}");
            exit;
        } else {
            die("invalid request!");
        }
    }


    /**
     * @param string $param ['client_id'=>,'cb_url'=>'','other_param'=>]
     * @return array
     */
    public function parseParam($param)
    {
        return json_decode(base64_decode($param), true);
    }

    /**
     * 公众号/小程序授权回调
     */
    public function authorizationCallback()
    {
        $params = $this->parseParam($_GET['client_param']);
        $authInfo = $this->handleAuthorize();
        $this->onAuthCb($authInfo);
        $_GET['type'] = 'auth_callback';
        $_GET['app_id'] = $authInfo['authorization_info']['authorizer_appid'];
        $this->clientRepository->attach($_GET['app_id'], $params['client_id']);
        $url = $params["cb_url"] . "?" . http_build_query($_GET);
        header("Location: {$url}");
        exit;
    }

}