<?php
/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/18 15:34
 */

namespace WechatProxy\OpenPlatform\Support;

use EasyWeChat\Kernel\Traits\InteractsWithCache;
use Pimple\Container;
use WechatProxy\OpenPlatform\Contract\ClientRepository as IClientRepository;

class ClientRepository implements IClientRepository
{
    use InteractsWithCache;

    /**
     * @var Container $app
     */
    protected $app;

    protected $cacheKey = "repository_info";


    /**
     * @var ProxyClientInfo[]
     */
    protected $proxyClients = [];


    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->load();
    }


    /**
     * 得到所有代理用户
     *
     * @return ProxyClientInfo[]
     */
    public function getAllClients()
    {
        return $this->proxyClients;
    }

    /**
     * 得到授权appId对应用户
     *
     * @param string $appId
     * @return ProxyClientInfo[]
     */
    public function getClients($appId)
    {
        $clients = [];
        foreach ($this->proxyClients as $proxyClient) {
            if ($proxyClient->appId == $appId) {
                $clients[] = $proxyClient;
            }
        }
        return $clients;
    }

    /**
     * 得到clientId对应用户
     *
     * @param string $clientId
     * @return ProxyClientInfo
     */
    public function getClient($clientId)
    {
        foreach ($this->proxyClients as $proxyClient) {
            if ($proxyClient->clientId == $clientId) {
                return $proxyClient;
            }
        }
        return null;
    }

    /**
     * 授权AppId关联ClientId
     *
     * @param string $appId
     * @param string $clientId
     * @return bool
     */
    public function attach($appId, $clientId)
    {
        foreach ($this->proxyClients as $i => $proxyClient) {
            if ($proxyClient->clientId == $clientId) {
                $this->proxyClients[$i]->appId = $appId;
                $this->save();
                return true;
            }
        }
        return false;
    }

    /**
     * 移除授权AppId关联
     *
     * @param string $appId
     * @return bool
     */
    public function detach($appId)
    {
        foreach ($this->proxyClients as $i => $proxyClient) {
            if ($proxyClient->appId == $appId) {
                $this->proxyClients[$i]->appId = "";
                $this->save();
                return true;
            }
        }
        return false;
    }

    /**
     * 添加代理客户
     *
     * @param ProxyClientInfo $info
     * @return bool
     */
    public function addClient(ProxyClientInfo $info)
    {
        foreach ($this->proxyClients as $i => $proxyClient) {
            if ($proxyClient->clientId == $info->clientId) {
                $info->appId = $proxyClient->appId;
                $this->proxyClients[$i] = $info;
                $this->save();
                return true;
            }
        }
        $this->proxyClients[] = $info;
        $this->save();
        return true;
    }

    /**
     * 删除代理客户
     *
     * @param string $clientId
     * @return bool
     */
    public function removeClient(string $clientId)
    {
        foreach ($this->proxyClients as $i => $proxyClient) {
            if ($proxyClient->clientId == $clientId) {
                unset($this->proxyClients[$i]);
                return true;
            }
        }
        return false;
    }

    public function load()
    {
        try {
            $content = $this->getCache()->get($this->cacheKey);
            $this->proxyClients = is_array($content) ? $content : [];
        } catch (\Psr\SimpleCache\InvalidArgumentException $e) {

        }
    }

    public function save()
    {
        try {
            $this->getCache()->set($this->cacheKey, $this->proxyClients);
        } catch (\Psr\SimpleCache\InvalidArgumentException $e) {

        }
    }
}