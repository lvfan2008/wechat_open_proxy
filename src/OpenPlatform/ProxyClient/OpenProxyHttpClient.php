<?php
/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/14 16:34
 */

namespace WechatProxy\OpenPlatform\ProxyClient;


use GuzzleHttp\Client;
use function GuzzleHttp\Psr7\parse_query;
use Pimple\Container;

/**
 * 代理公众号或小程序的Client，请求时，加入http query参数account_app_id
 *
 * @package App\Service\Wechat
 */
class OpenProxyHttpClient extends Client
{
    protected $accountAppId = null;

    /**
     * @var Container $app
     */
    protected $app;

    public function __construct(array $config = [], Container $app)
    {
        parent::__construct($config);
        $this->app = $app;
    }

    /**
     * 设置公众号或小程序appId
     * @param string $appId
     */
    public function setAccountAppId($appId)
    {
        $this->accountAppId = $appId;
    }

    public function request($method, $uri = '', array $options = [])
    {
        if (isset($options['query']) && is_string($options['query'])) {
            $options = parse_query($options['query']);
        }
        if ($this->accountAppId)
            $options['query']['account_app_id'] = $this->accountAppId;
        if (!isset($options['base_uri'])) {
            $options['query']['org_base_uri'] = "https://api.weixin.qq.com/";
        } else {
            $options['query']['org_base_uri'] = strval($options['base_uri']);
        }
        $options['query']['ori_uri_path'] = $uri;
        $options['base_uri'] = $this->app['config']->get("proxy_base_uri");
        $this->app['logger']->debug("ProxyClient request  option: " . json_encode($options));
        return parent::request($method, $uri, $options);
    }
}