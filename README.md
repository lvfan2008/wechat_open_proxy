## 微信开放平台代理 

wechat_open_proxy 1.0 微信开放平台代理 ，PHP版本。

wechat_open_proxy 1.0 是基于easyWechat类库，编写的微信开放平台代理服务类库。使用此类库，可以实现开放平台Api的统一代理使用，
公众号和小程序统一在一台代理服务器进行授权绑定。不同域名下的业务使用统一域名代理服务器，进行授权管理和Api代理调用。

## 下载
你可以 clone 这个仓库，自行下载使用。

## 实现功能
1. 实现公众号和小程序授权
2. 实现公众号和小程序的事件代理
3. 实现开放平台事件代理
4. 实现公众号全网发布接入检测的自动化测试代码
5. 增加Composer支持

## 简单使用说明
1. 假定代码目录为/data/website/wechat_open_proxy/
2. 配置apache网站目录为/data/website/wechat_open_proxy/example/
3. 假设网站域名为www.xxx.com
4. 登录微信开放平台，配置微信第三方平台参数
    * 授权事件接收URL：http://www.xxx.com/server/proxy/component/callback
    * 消息与事件接收URL：http://www.xxx.com/server/proxy/app/$APPID$/callback
    * 根据需要配置其他参数
5. 配置网站代码：
    * cd /data/website/wechat_open_proxy/
    * composer install #如果不熟悉Composer，可以参考下面Composer安装说明第3项。
    * cd ./example
    * cp .env.example .env
    * 设置SERVER_DOMAIN=www.xxx.com
    * 设置CLIENT_DOMAIN=www.xxx.com，也可以为实际的客户端域名
    * 设置开放平台的以OPEN_开头的配置
6. 配置完成后，等待component_verify_ticket消息（10分钟1次）到来后，进行全网发布接入检测，参考[微信官网接入说明](https://open.weixin.qq.com/cgi-bin/showdocument?action=dir_list&t=resource/res_list&verify=1&id=open1419318611&token=&lang=zh_CN)
7. 如果不成功，检查以下几项
    * 检查/example/目录是否可写
    * 检查/example/cache/目录的cache文件，查看原因
    * 检查/example/log/目录的日志文件，查看原因
    * 检查apache rewrite模块是否开启，是否支持.htaccess
    * 检查apache配置的错误日志文件，查看原因
8. 发布成功后，测试相应功能：
    * 用浏览器打开http://www.xxx.com/client.php，测试授权功能。
    * 授权成功后：输入http://www.xxx.com/client/test/api/getips?app_id=【授权后的app_id】
    
## Composer安装说明
1. composer require lv_fan2008/wechat_open_proxy
2. composer中文文档参考[http://docs.phpcomposer.com/](http://docs.phpcomposer.com/)
3. composer安装参考：https://pkg.phpcomposer.com/#how-to-install-composer


## 我的测试环境
1. 使用的是阿里云主机
2. 操作系统为Debian 8.0 64Bits
3. Apache/2.4.10 (Debian) PHP 7.2。0
4. php扩展模块有curl openssl

## 建议和疑问

如果你有好的建议或者疑问，欢迎给我提issue或pull request，或者发邮件到lv_fan2008@sina.com 。
也可以加入到QQ群519270384进行讨论。

## LICENSE

[MIT](https://opensource.org/licenses/MIT)，尽情享受开源代码。

