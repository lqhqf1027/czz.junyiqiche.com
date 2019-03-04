<?php
/**
 * Created by PhpStorm.
 * User: glen9
 * Date: 2019/3/4
 * Time: 11:08
 */

namespace addons\cms\controller\wxapp;

use think\Config;
use think\Loader;
use Think\Db;
use think\Env;
Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');

class Wxpay extends Base
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $model = '';

    public function index()
    {
        $user_id = (int)$this->request->post('user_id');
        $order = (int)$this->request->post('order');
        $money =  $this->request->post('money')*100;
        if (!$user_id || !$order || !$money) $this->error('缺少参数');
        $openid = self::getOpenid($user_id);
        //     初始化值对象
//        $input = new \WxPayUnifiedOrder();
        $input = new \WxPayUnifiedOrder();
        //     文档提及的参数规范：商家名称-销售商品类目
        $input->SetBody("认证店铺");
        //     订单号应该是由小程序端传给服务端的，在用户下单时即生成，demo中取值是一个生成的时间戳
        $input->SetOut_trade_no("$order");
        //     费用应该是由小程序端传给服务端的，在用户下单时告知服务端应付金额，demo中取值是1，即1分钱
        $input->SetTotal_fee("$money");
        $input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");
        $input->SetTrade_type("JSAPI");
        //     由小程序端传给服务端
        $input->SetOpenid($openid);
        //     向微信统一下单，并返回order，它是一个array数组
        $order = \WxPayApi::unifiedOrder($input);
        //     json化返回给小程序端
        header("Content-Type: application/json");
        $order['key'] =Env::get('wxpay.key');
        $order['appid'] =Config::get('oauth')['appid'];
        echo json_encode($order);
    }

    public static function getOpenid($user_id)
    {
        return Db::name('third')->where(['user_id' => $user_id])->find()['openid'];
    }

}