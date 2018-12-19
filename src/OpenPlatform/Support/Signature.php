<?php
/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/18 15:27
 */

namespace WechatProxy\OpenPlatform\Support;

use EasyWeChat\Kernel\ServiceContainer;
use Pimple\Container;
use WechatProxy\OpenPlatform\Contract\Signature as ISignature;

class Signature implements ISignature
{
    /**
     * @var Container $app
     */
    protected $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * 验证签名
     *
     * @param string $key
     * @param array $query
     * @return bool
     */
    public function verifySign(string $key, array $query)
    {
        $sign = $query['sign'];
        unset($query['sign']);
        return $sign == $this->generateSign($key, $query);
    }

    /**
     * 生成该域名的签名
     *
     * @param string $key
     * @param array $query
     * @return string
     */
    public function generateSign(string $key, array $query)
    {
        if (isset($query['sign'])) unset($query['sign']);
        $keys = array_keys($query);
        sort($keys, SORT_STRING);
        $values = "";
        foreach ($keys as $key) {
            $values .= $query[$key];
        }
        return sha1($key . $values);
    }
}