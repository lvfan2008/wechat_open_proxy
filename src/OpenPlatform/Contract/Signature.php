<?php
/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/18 13:57
 */

namespace WechatProxy\OpenPlatform\Contract;


interface Signature
{
    /**
     * 验证签名
     *
     * @param string $key
     * @param array $query
     * @return bool
     */
    public function verifySign(string $key, array $query);

    /**
     * 生成该域名的签名
     *
     * @param string $key
     * @param array $query
     * @return string
     */
    public function generateSign(string $key, array $query);
}