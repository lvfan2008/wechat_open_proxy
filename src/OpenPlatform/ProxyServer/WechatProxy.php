<?php
/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/12 10:00
 */

namespace WechatProxy\OpenPlatform\ProxyServer;


use EasyWeChat\Kernel\Decorators\TerminateResult;
use Psr\SimpleCache\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions;
use EasyWeChat\Kernel\AccessToken;
use GuzzleHttp;
use EasyWeChat\Kernel\Messages\Raw as RawMessage;
use WechatProxy\OpenPlatform\Contract\ClientRepository;
use WechatProxy\OpenPlatform\Contract\Signature;

/**
 * 开放平台API代理，主要为替换token进行代理 和 不同域名授权代理
 *
 * $config = [
 *  'app_id'   => '开放平台第三方平台 APPID',
 *  'secret'   => '开放平台第三方平台 Secret',
 *  'token'    => '开放平台第三方平台 Token',
 *  'aes_key'  => '开放平台第三方平台 AES Key',
 * ];
 *
 * @property Signature $signature
 * @property ClientRepository $clientRepository
 * @package WechatProxy\OpenPlatform\ProxyServer
 */
class WechatProxy extends OpenPlatform
{
    /**
     * 代理开放平台API
     *
     */
    public function proxy()
    {
        $result = $this->parseRequest();
        if (is_string($result)) {
            $res = ['errcode' => -1, 'errmsg' => $result];
            $this->outputJson($res);
        } else {
            list($apiUrl, $appId, $query, $headers, $body) = $result;
            $this->proxyRequest($apiUrl, $appId, $query, $headers, $body);
        }
    }

    /**
     * 输出JSON
     *
     * @param mixed $data
     */
    protected function outputJson($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }

    /**
     * 代理api请求
     *
     * @param string $apiUrl
     * @param string $appId
     * @param array $query
     * @param array $headers
     * @param string $body
     * @throws
     */
    protected function proxyRequest($apiUrl, $appId, $query, $headers, $body)
    {
        $result = $this->getTokenApiResult($apiUrl, $appId);
        if (!empty($result)) {
            return $this->outputJson($body);
        }

        foreach ($headers as $name => $value) {
            if (strtolower($name) == "host" || strtolower($name) == 'user-agent') continue;
            if (strtolower($name) == "content-length") $value = strlen($body);
            $headers[] = $name . ": " . implode("; ", $value);
        }

        $url = $apiUrl . ($query ? "?" . http_build_query($query) : "");
        $this->logger->debug("request url {$apiUrl}, headers: " . json_encode($headers));

        list($outHeader, $body) = $this->httpRequest($url, $_SERVER['REQUEST_METHOD'], $body, $headers);
        $outHeaders = explode("\n", $outHeader);
        foreach ($outHeaders as $outHeader) {
            if ($outHeader = trim($outHeader))
                header($outHeader);
        }
        echo $body;
    }

    /**
     * @param string $url
     * @param string $method
     * @param string $body
     * @param array $headers
     * @param int $timeout
     * @return array
     */
    protected function httpRequest($url, $method = "GET", $body = "", $headers = [], $timeout = 5)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_HEADER, true);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $headers);
        if ($method == "POST" || $body != "") {
            curl_setopt($oCurl, CURLOPT_POST, true);
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $body);
            $this->logger->debug("request content {$body}");
        }

        $sContent = curl_exec($oCurl);
        $headerSize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
        $outHeader = substr($sContent, 0, $headerSize);
        $body = substr($sContent, $headerSize);
        $this->logger->debug("outHeader: " . $outHeader);
        $this->logger->debug("body: " . $body);
        curl_close($oCurl);
        return [$outHeader, $body];
    }

    /**
     * 解析请求，进行token替换
     *
     * @return string|array
     */
    protected function parseRequest()
    {
        if (!$this->verifySign()) return 'verifySign failed!';

        $query = $this->getReplaceQuery($_GET);
        if (is_string($query)) return $query;

        list($query, $apiUrl, $appId) = $query;
        $body = $this->getReplaceBody();
        $headers = $this->getHeaders();

        return [$apiUrl, $appId, $query, $headers, $body];
    }

    /**
     * 替换Query参数：component_access_token 和 access_token
     *
     * @param array $query
     * @return string|array
     */
    protected function getReplaceQuery($query)
    {
        if (!isset($query['ori_uri_path']) || !isset($query['ori_base_uri'])) {
            return "query parameter ori_uri_path and org_base_uri must exists!";
        }

        $apiUrl = $this->combineUrl($query['ori_base_uri'], $query['ori_uri_path']);
        $appId = $query['account_app_id'] ?? "";

        if (!isset($query['component_access_token']) && !isset($query['access_token'])) {
            return "query parameter component_access_token or access_token must one exists!!";
        }

        if (isset($query['component_access_token'])) {
            if (isset($query['component_appid'])) $query['component_appid'] = $this['config']['app_id'];
            $token = $query['component_access_token'] = $this->getToken($this->access_token);
            if (is_array($token)) return "getToken component_access_token error,message: " . $token[1];
        }

        if (isset($query['access_token'])) {
            if (!$appId) return 'account_app_id is null!';
            $account = $this->getAccount($appId);
            if ($account) {
                $token = $query['access_token'] = $this->getToken($account->access_token);
                if (is_array($token)) return "getToken component_access_token error,message: " . $token[1];
            } else {
                return " get account {$appId} failed";
            }
        }
        unset($query['uri_path']);
        unset($query['org_base_uri']);
        unset($query['account_app_id']);
        return [$query, $apiUrl, $appId];
    }

    /**
     * 得到请求的headers
     *
     * @return array
     */
    protected function getHeaders()
    {
        $headers = array();
        $contentHeaders = array('CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true);
        foreach ($_SERVER as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } // CONTENT_* are not prefixed with HTTP_
            elseif (isset($contentHeaders[$key])) {
                $headers[$key] = $value;
            }
        }
        return $headers;
    }


    /**
     * POST请求时，替换component_appid字段
     *
     * @return string
     */
    protected function getReplaceBody()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $content = file_get_contents("php://input");
            $contentTypeMap = ["application/json", "application/x-www-form-urlencoded"];
            $headers = $this->getHeaders();
            $contentType = $headers['CONTENT_TYPE'] ?? "";
            $contentType = explode("; ", $contentType)[0];
            if (($type = array_search($contentType, $contentTypeMap)) !== false) {
                $data = $type == 0 ? json_decode($content, true) : GuzzleHttp\Psr7\parse_query($content);
                if (isset($data['component_appid'])) {
                    $data['component_appid'] = $this['config']['app_id'];
                    $body = $type == 1 ? json_encode($data) : http_build_query($data);
                    $this->logger->debug("proxy replaced request body: {$body} ");
                    return $body;
                }
            }
            return $content;
        }
        return "";
    }

    /**
     *
     * @param string $url
     * @param string $appId
     * @return array
     * @throws Exceptions\HttpException
     * @throws Exceptions\InvalidArgumentException
     * @throws Exceptions\InvalidConfigException
     * @throws InvalidArgumentException
     */
    protected function getTokenApiResult($url, $appId)
    {
        if ($this->isOpenPlatformGetTokenUrl($url)) {
            $token = $this->access_token->getToken();
            return $token;
        }
        if ($this->isAuthorizerRefreshTokenUrl($url)) {
            $tokenInfo = $this->getAccount($appId)->access_token->getToken();
            return $tokenInfo;
        }
        return [];
    }

    /**
     * 组合URL
     * @param string $baseUri
     * @param string $uriPath
     * @return string
     */
    protected function combineUrl($baseUri, $uriPath)
    {
        return rtrim($baseUri, "/") . "/" . ltrim($uriPath, "/");
    }

    /**
     * 判断是否为开放平台获取token的Url
     *
     * @param string $url
     * @return bool
     */
    protected function isOpenPlatformGetTokenUrl($url)
    {
        return $url == "https://api.weixin.qq.com/cgi-bin/component/api_component_token";
    }

    /**
     * 判断是否为授权公众号或小程序刷新token的url
     *
     * @param string $url
     * @return bool
     */
    protected function isAuthorizerRefreshTokenUrl($url)
    {
        return $url == "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token";
    }

    /**
     * @param \EasyWeChat\Kernel\AccessToken $accessToken
     * @return string|array
     */
    protected function getToken(AccessToken $accessToken)
    {
        try {
            $token = $accessToken->getToken();
            $this->logger->debug("access token:" . print_r($token, true));
            return $token[$accessToken->getTokenKey()];
        } catch (Exceptions\HttpException $e) {
            return [$e->getCode(), $e->getMessage()];
        } catch (InvalidArgumentException $e) {
            return [$e->getCode(), $e->getMessage()];
        } catch (Exceptions\InvalidConfigException $e) {
            return [$e->getCode(), $e->getMessage()];
        } catch (Exceptions\InvalidArgumentException $e) {
            return [$e->getCode(), $e->getMessage()];
        }
    }

    public function verifySign()
    {
        if (!isset($_GET['client_id'])) return false;
        $client = $this->clientRepository->getClient($_GET['client_id']);
        if (!$client) return false;
        return $this->signature->verifySign($client->key, $_GET);
    }

    /**
     * 处理开放平台事件
     *
     * @param array $message
     */
    protected function processComponentEvent($message)
    {
        if ($this->clientRepository) {
            $clients = $this->clientRepository->getClients($message['AuthorizerAppid']);
            foreach ($clients as $client) {
                $query['type'] = "component_event";
                $query['client_id'] = $client->clientId;
                $query['time'] = time();
                $query['sign'] = $this->signature->generateSign($client->key, $query);
                $url = $client->cbUrl . "?" . http_build_query($query);
                $this->getClientReply($url, $message);
            }
        }
    }

    /**
     * 处理公众号或小程序事件
     *
     * @param string $appId
     * @param array $message
     * @return TerminateResult|null
     */
    protected function processAppEvent($appId, $message)
    {
        $result = null;
        $clients = $this->clientRepository->getClients($appId);
        foreach ($clients as $client) {
            $query['client_id'] = $client->clientId;
            $query['type'] = "app_event";
            $query['app_id'] = $appId;
            $query['time'] = time();
            $query['sign'] = $this->signature->generateSign($client->key, $query);
            $url = $client->cbUrl . "?" . http_build_query($query);
            $content = $this->getClientReply($url, $message);
            if ($content != "" && $result == null) {
                $result = new TerminateResult(new RawMessage($content)); // 只回复第一个
            }
        }
        return $result;
    }

    /**
     * 自动回复
     *
     * @param string $url
     * @param array $param
     * @return bool|string 回复xml内容
     */
    protected function getClientReply($url, $param)
    {
        $this->logger->debug("getClientReply url:{$url} param:" . print_r($param, true));
        list($_, $result) = $this->httpRequest($url, "POST", json_encode($param));
        $result = json_decode($result, true);
        $this->logger->debug("getClientReply result: " . print_r($result, true));
        return $result;
    }
}