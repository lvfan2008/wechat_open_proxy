<?php
/**
 * Created by PhpStorm.
 * User: 新国
 * Date: 2017/12/22
 * Time: 9:29
 */

namespace WechatProxy\OpenPlatform\ProxyServer;

use EasyWeChat\Kernel\Decorators\TerminateResult;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Traits\InteractsWithCache;
use EasyWeChat\OpenPlatform\Application as EasyWeChatPlatform;
use EasyWeChat\OpenPlatform\Authorizer\OfficialAccount\OAuth\ComponentDelegate;
use Overtrue\Socialite\Providers\WeChatProvider;
use Overtrue\Socialite\SocialiteManager;
use Psr\SimpleCache\InvalidArgumentException;


class OpenPlatform extends EasyWeChatPlatform
{
    use InteractsWithCache;

    /**
     * @var string
     */
    protected $openPrefix = "open.platform.authorizer.token.";

    /**
     * 开放平台事件处理
     */
    public function onComponentCallBack()
    {
        $server = $this->server;
        try {
            $server->push(function ($message) {
                $this->logger->debug("OpenPlatform: receiveComponentEvent: " . print_r($message, true));
                if ($message['InfoType'] != 'component_verify_ticket')
                    $this->processComponentEvent($message);
            });
            $response = $server->serve();
            $response->send();
        } catch (\Exception $e) {
            $this->logger->error("OpenPlatform: onComponentCallBack exception, message: " . $e->getMessage());
        }
    }

    /**
     * 小程序或公众号事件处理
     *
     * @param string $appId
     * @return string
     */
    public function onAppEventCallBack($appId)
    {
        if ($this->isGlobalTestAppId($appId)) {
            return $this->globalTest($appId);
        }
        $account = $this->getAccount($appId);
        try {
            $account->server->push(function ($message) use ($appId) {
                $this->logger->debug("OpenPlatform: receiveAppEvent: {$appId} " . print_r($message, true));
                return $this->processAppEvent($appId, $message);
            });
            $response = $account->server->serve();
            $response->send();
        } catch (\Exception $e) {
            $this->logger->error("OpenPlatform: onAppEventCallBack exception, message: " . $e->getMessage());
        }
    }

    /**
     * 是否为全网测试的appId
     *
     * @param string $appId
     * @return bool
     */
    protected function isGlobalTestAppId($appId)
    {
        return ($appId == "wx570bc396a51b8ff8" || $appId == "wxd101a85aa106f53e");
    }

    /**
     * 全网发布测试
     *
     * @param string $appId
     * @return bool
     */
    protected function globalTest($appId)
    {
        if (!$this->isGlobalTestAppId($appId)) return false;
        if ($appId == "wxd101a85aa106f53e") {
            $account = $this->miniProgram($appId);
        } else {
            $account = $this->officialAccount($appId);
        }
        try {
            $replyMessage = $authCode = "";
            $receiveMessage = [];
            $server = $account->server;
            $that = $this;
            $server->push(function ($message) use ($that, &$replyMessage, &$authCode, &$receiveMessage) {
                $receiveMessage = $message;
                if ($message['MsgType'] == "event") {
                    $replyMessage = $message['Event'] . "from_callback";
                } else if ($message['MsgType'] == "text" && "TESTCOMPONENT_MSG_TYPE_TEXT" == $message['Content']) {
                    $replyMessage = $message['Content'] . "_callback";
                } else if ($message['MsgType'] == "text" && preg_match('#QUERY_AUTH_CODE:(.*)#', $message['Content'], $matches)) {
                    $authCode = $matches[1];
                    $authInfo = $that->handleAuthorize($authCode);
                    $this->onAuthCb($authInfo);
                    $replyMessage = "";
                }
                return new TerminateResult(new Text($replyMessage));
            });
            $response = $server->serve();
            $this->logger->debug("OpenPlatform: Receive message: " . json_encode($receiveMessage));

            $this->logger->debug("OpenPlatform: Send replyMessage: " . $replyMessage);
            $response->send();


            if ($authCode) {
                $this->logger->debug("OpenPlatform: receive authCode: " . $authCode);
                $account = $this->getAccount($appId);
                $message = [
                    'touser' => $receiveMessage['FromUserName'],
                    'msgtype' => 'text',
                    'text' => ['content' => $authCode . "_from_api"]
                ];
                $result = $account->customer_service->send($message);
                $this->logger->debug("OpenPlatform: customer_service->send " . $authCode . "_from_api result:" . json_encode($result));
            }
        } catch (\Exception $e) {
            $this->logger->error("OpenPlatform: globalTest error " . $e->getMessage());
        }
        return true;

    }

    /**
     * 公众号/小程序授权回调
     *
     * @param array $authInfo
     * @return array
     */
    public function onAuthCb($authInfo)
    {
        $authInfo = $authInfo['authorization_info'];
        $appId = $authInfo['authorizer_appid'];
        $authorizer = $this->getAuthorizer($appId);
        $tokenInfo = [
            "authorizer_access_token" => $authInfo['authorizer_access_token'],
            "expires_in" => $authInfo['expires_in'],
            "authorizer_refresh_token" => $authInfo['authorizer_refresh_token'],
            "isMiniProgram" => isset($authorizer['authorizer_info']['MiniProgramInfo']),
            "time" => time(),
        ];
        $result = $this->setTokenCache($appId, $tokenInfo);
        $this->logger->debug("OpenPlatform: onAuthCb setTokenCache result = $result , $appId : " . print_r($tokenInfo, true));
        return $authorizer;
    }

    /**
     * 处理开放平台事件
     *
     * @param array $message
     */
    protected function processComponentEvent($message)
    {

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
        return null;
    }


    /**
     * 获取公众号/小程序对象
     *
     * @param string $appId
     * @return \EasyWeChat\OpenPlatform\Authorizer\MiniProgram\Application|\EasyWeChat\OpenPlatform\Authorizer\OfficialAccount\Application
     */
    public function getAccount($appId)
    {
        $tokenInfo = $this->getTokenCache($appId);
        if (!$tokenInfo) {
            $this->logger->error("getAccount({$appId}) tokenInfo  not found! ");
            return null;
        }

        if ($tokenInfo['isMiniProgram']) {
            $account = $this->miniProgram($appId, $tokenInfo['authorizer_refresh_token']);
        } else {
            $account = $this->officialAccount($appId, $tokenInfo['authorizer_refresh_token']);
        }
        try {
            if ($this->isTokenExpired($tokenInfo)) {
                $newTokenInfo = $account->access_token->getToken(true);
                $newTokenInfo['time'] = time();
                $newTokenInfo['isMiniProgram'] = $tokenInfo['isMiniProgram'];
                $this->setTokenCache($appId, $newTokenInfo);
            } else {
                $account->access_token->setToken($tokenInfo['authorizer_access_token'], time() - $tokenInfo['time'] - 500);
            }
        } catch (InvalidArgumentException $e) {
            $this->logger->error("getAccount({$appId}) exception: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error("getAccount({$appId}) exception: " . $e->getMessage());
        }
        return $account;
    }

    /**
     * 保存appId的token信息
     *
     * @param string $appId
     * @param array $tokenInfo
     * @return bool
     */
    protected function setTokenCache($appId, $tokenInfo)
    {
        try {
            return $this->getCache()->set($this->getTokenCacheKey($appId), $tokenInfo, 100 * 365 * 3600);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }


    /**
     * 得到appId的Token信息
     *
     * @param string $appId
     * @return array|null
     */
    protected function getTokenCache($appId)
    {
        try {
            return $this->getCache()->get($this->getTokenCacheKey($appId));
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * 判断token是否过期
     *
     * @param array $tokenInfo
     * @return bool
     */
    protected function isTokenExpired($tokenInfo)
    {
        return time() - 500 < $tokenInfo['time'] + $tokenInfo['expires_in'];
    }

    /**
     * 得到授权key
     *
     * @param string $appId
     * @return string
     */
    protected function getTokenCacheKey($appId)
    {
        return $this->openPrefix . "token." . $this->config['app_id'] . "-" . $appId;
    }

    /**
     * Wechat Login By OAuth
     *
     * @param string $callbackUrl
     */
    public function OAuthRedirect($appId, $callbackUrl)
    {
        $response = $this->getWechatProvider($appId)->redirect($callbackUrl);
        $response->send();
    }

    /**
     * Get User From OAuth Callback
     *
     * @param string $appId
     * @return \Overtrue\Socialite\User
     */
    public function OAuthUser($appId)
    {
        return $this->getWechatProvider($appId)->user();
    }

    /**
     * Get WeChatProvider
     *
     * @param string $appId
     * @return WeChatProvider
     */
    protected function getWechatProvider($appId)
    {
        $manager = new SocialiteManager([
            'wechat' => [
                'client_id' => $appId,
                'client_secret' => '',
                'redirect' => '',
            ]
        ]);
        /**
         * @var WeChatProvider $oAuth
         */
        $oAuth = $manager->driver("wechat");
        $oAuth->component(new ComponentDelegate($this));
        $oAuth->scopes(['snsapi_userinfo']);
        return $oAuth;
    }
}