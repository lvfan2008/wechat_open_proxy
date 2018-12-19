<?php
/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/18 9:39
 */

namespace WechatProxy\OpenPlatform\Support;


class ProxyClientInfo
{
    /**
     * 代理客户标示，不能重复
     *
     * @var string
     */
    public $clientId;

    /**
     * 验证签名所需Key
     *
     * @var string
     */
    public $key;

    /**
     * 事件回调Url
     *
     * @var string
     */
    public $cbUrl;

    /**
     * 授权appId
     *
     * @var string
     */
    public $appId;


    /**
     * ProxyClientInfo constructor.
     * @param $clientId
     * @param $key
     * @param $cbUrl
     * @param string $appId
     */
    public function __construct($clientId, $key, $cbUrl, $appId = "")
    {
        $this->clientId = $clientId;
        $this->key = $key;
        $this->cbUrl = $cbUrl;
        $this->appId = $appId;
    }


}