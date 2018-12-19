<?php
/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/18 9:13
 */

namespace WechatProxy\OpenPlatform\Contract;

use WechatProxy\OpenPlatform\Support\ProxyClientInfo;

interface ClientRepository
{
    /**
     * 得到所有代理用户
     *
     * @return ProxyClientInfo[]
     */
    public function getAllClients();

    /**
     * 得到授权appId对应用户
     *
     * @param string $appId
     * @return ProxyClientInfo[]
     */
    public function getClients($appId);


    /**
     * 得到clientId对应用户
     *
     * @param string $clientId
     * @return ProxyClientInfo
     */
    public function getClient($clientId);

    /**
     * 授权AppId关联域名
     *
     * @param string $appId
     * @param string $domain
     * @return bool
     */
    public function attach($appId, $domain);

    /**
     * 移除授权AppId关联
     *
     * @param string $appId
     * @return bool
     */
    public function detach($appId);

    /**
     * 添加代理客户
     *
     * @param ProxyClientInfo $info
     * @return bool
     */
    public function addClient(ProxyClientInfo $info);

    /**
     * 删除代理客户
     *
     * @param string $clientId
     * @return bool
     */
    public function removeClient(string $clientId);

}