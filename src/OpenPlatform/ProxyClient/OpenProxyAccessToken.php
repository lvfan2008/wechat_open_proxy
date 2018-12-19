<?php
/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/13 11:04
 */

namespace WechatProxy\OpenPlatform\ProxyClient;


use EasyWeChat\OpenPlatform\Auth\AccessToken;

class OpenProxyAccessToken extends AccessToken
{
    /**
     * @return array
     */
    protected function getCredentials(): array
    {
        return [
            'component_appid' => $this->app['config']['app_id'],
            'component_appsecret' => $this->app['config']['secret'],
            'component_verify_ticket' => 'test',
        ];
    }
}