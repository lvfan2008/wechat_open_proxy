<?php
/**
 * Description
 * User: lixinguo@vpubao.com
 * Time: 2018/12/19 15:55
 */
include_once dirname(__FILE__) . "/../vendor/autoload.php";
include_once dirname(__FILE__) . "/helper.php";

try {
    (new \Dotenv\Dotenv(__DIR__, ".env"))->load();
} catch (\Exception $e) {
    //
}