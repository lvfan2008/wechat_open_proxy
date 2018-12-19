<?php
/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/12 15:11
 */

namespace WechatProxy\OpenPlatform\ProxyClient;

use EasyWeChat\OpenPlatform\Application as EasyWeChatPlatform;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Cache\Simple\FilesystemCache;
use WechatProxy\OpenPlatform\Support\Signature;
use WechatProxy\OpenPlatform\Support\ProxyClientInfo;

/**
 *
 * @property Signature $signature
 * @package WechatProxy\OpenPlatform\ProxyClient
 */
class OpenProxyClient extends EasyWeChatPlatform
{
    /**
     * @var ProxyClientInfo $proxyClientInfo
     */
    protected $proxyClientInfo;

    /**
     * @param ProxyClientInfo $proxyClientInfo
     */
    public function setProxyClientInfo(ProxyClientInfo $proxyClientInfo)
    {
        $this->proxyClientInfo = $proxyClientInfo;
    }


    /**
     * OpenProxyClient constructor.
     * @param array $config
     * @param array $prepends
     * @param string|null $id
     * @throws
     */
    public function __construct(array $config = [], array $prepends = [], string $id = null)
    {
        if (!isset($config["proxy_base_uri"]) && !$config["proxy_base_uri"]) {
            throw new \RuntimeException("base_uri can not null");
        }

        if (!isset($config["proxy_auth_url"]) && !$config["proxy_auth_url"]) {
            throw new \RuntimeException("proxy_auth_url can not null");
        }

        $invalidConfig = [
            'app_id' => 'proxy_app_id',
            'secret' => 'proxy_app_secret',
            'token' => 'proxy_app_token',
            'aes_key' => 'proxy_aes_key',
        ];

        $config = array_merge_recursive($config, $invalidConfig);

        $replaceServices = [
            'access_token' => function ($app) {
                return new OpenProxyAccessToken($app);
            },
            'http_client' => function ($app) {
                return new OpenProxyHttpClient([], $app);
            },
        ];
        $prepends = array_merge_recursive($prepends, $replaceServices);

        parent::__construct($config, $prepends, $id);

        $this['signature'] = function ($app) {
            return new Signature($app);
        };
        $this['cache'] = function ($app) {
            return new FilesystemCache("", 0, $app['config']->get("cache.path"));
        };

        $this->access_token->setToken("invalid_token");
    }

    /**
     * @param string $appId
     * @param bool $isMini 是否为小程序
     * @return \EasyWeChat\OpenPlatform\Authorizer\MiniProgram\Application|\EasyWeChat\OpenPlatform\Authorizer\OfficialAccount\Application
     */
    public function getAccount(string $appId, bool $isMini)
    {
        $account = $isMini ? $this->miniProgram($appId, "invalid_refresh_token") :
            $this->officialAccount($appId, "invalid_refresh_token");
        try {
            $account->access_token->setToken('invalid_token');
        } catch (InvalidArgumentException $e) {
        }
        $account['http_client'] = function ($app) use ($appId) {
            $client = new OpenProxyHttpClient([], $this);
            $client->setAccountAppId($appId);
            return $client;
        };
        return $account;
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
     * 验证签名
     *
     * @param array $query
     * @return bool
     */
    public function verifySign($query)
    {
        if ($this->proxyClientInfo->clientId != ($query['client_id'] ?? "")) return false;
        return $this->signature->verifySign($this->proxyClientInfo->key, $query);
    }

    /**
     * 签名
     *
     * @param array $query
     * @return array
     */
    public function sign($query)
    {
        $query['client_id'] = $this->proxyClientInfo->clientId;
        $query['sign'] = $this->signature->generateSign($this->proxyClientInfo->key, $query);
        return $query;
    }

    /**
     * @param mixed $param
     * @return string
     */
    public function getProxyAuthUrl($param)
    {
        $data = [
            'client_id' => $this->proxyClientInfo->clientId,
            'cb_url' => $this->proxyClientInfo->cbUrl,
            'param' => json_encode($param),
        ];
        $data['sign'] = $this->signature->generateSign($this->proxyClientInfo->key, $data);
        $query = [
            'client_param' => base64_encode(json_encode($data)),
        ];
        $proxyServerAuthUrl = $this['config']->get('proxy_auth_url');
        return $proxyServerAuthUrl . "?" . http_build_query($query);
    }

    /**
     * @return string
     */
    public function onComponentEvent()
    {
        $content = file_get_contents('php://input');
        $message = json_decode($content, true);
        $this->logger->debug("ProxyClient onComponentEvent receive message: " . print_r($message, true));
        echo "success";
    }

    /**
     * 回复XML or "success"
     *
     * @param string $appId
     * @return string
     */
    public function onAppEvent($appId)
    {
        $content = file_get_contents('php://input');
        $message = json_decode($content, true);
        $this->logger->debug("ProxyClient onAppEvent receive {$appId} message: " . print_r($message, true));
        echo "success";
    }

}