<?php
/**
 * Created by PhpStorm.
 * User: glen9
 * Date: 2019/2/14
 * Time: 16:02
 */
namespace app\wechat\controller;

use app\common\controller\Frontend;
use think\request;
use think\Controller;
use think\Loader;
use think\Config;
use think\Db;
class Wechat extends  Controller{
    /**
     * 微信验证
     */
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    public function wx()
    {
        define("TOKEN", "YOUCHEQUAN");
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            echo $echoStr;
            die;
        }

    }
    /**
     * 检查签名
     *
     */
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    public function uploadsfiles(){
        return  action('api/common/upload');
    }

}