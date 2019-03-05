<?php
/**
 * Created by PhpStorm.
 * User: glen9
 * Date: 2019/3/4
 * Time: 11:08
 */

namespace addons\cms\controller\wxapp;

use addons\cms\model\CompanyStore;
use addons\cms\model\EarningDetailed;
use addons\cms\model\PayOrder;
use think\Config;
use Think\Db;
use think\Env;
use think\Loader;
use think\Exception;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');

class Wxpay extends Base
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $model = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        if (!$this->request->isPost()) $this->error('非法请求');
        $user_id = (int)$this->request->post('user_id');
        $order_number = (int)$this->request->post('order_number');
        $money = $this->request->post('money') * 100;
        $store_id = (int)$this->request->post('store_id');
        if (!$user_id || !$order_number || !$money || !$store_id) $this->error('缺少参数');
        if (self::checkPay($store_id, $order_number)) $this->error('该订单已存在支付！', 'error');
        return getAccessToken();
        $openid = self::getOpenid($user_id);
        //     初始化值对象
        $input = new \WxPayUnifiedOrder();
        //     文档提及的参数规范：商家名称-销售商品类目
        $input->SetBody("友车圈认证店铺年费");
        //     订单号应该是由小程序端传给服务端的，在用户下单时即生成，demo中取值是一个生成的时间戳
        $input->SetOut_trade_no("$order_number");
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
        $order['key'] = Env::get('wxpay.key');
        $order['appid'] = Config::get('oauth')['appid'];
        echo json_encode($order);
    }

    /**
     * 检测是否存在重复支付
     * @param $store_id
     * @param $order_number
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function checkPay($store_id, $order_number)
    {
        return collection(PayOrder::with(['pay_status'])->where(['store_id' => $store_id, 'order_number' => $order_number])->select())->toArray();
    }
    /** 微信toke
     * @return array|mixed  返回Token
     */
    public static function getWxtoken()
    {
        $config = get_addon_config('cms');
        $appid = $config['wxappid'];
        $secret = $config['wxappsecret'];
        $token = cache('Token');
        if (!$token['access_token'] || $token['expires_in'] <= time()) {
            //            https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret
            $rslt = gets("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . '&secret=' . $secret);
            if ($rslt) { //
                $accessArr = array(
                    'access_token' => $rslt['access_token'],
                    'expires_in' => time() + $rslt['expires_in'] - 200
                );
                cache('Token', $accessArr);
                $token = $accessArr;
            }
        }
        return $token;
    }
    /**
     * 支付成功回调
     */
    public function PaySuccessFulCb()
    {
        if (!$this->request->isPost()) $this->error('非法请求');
        $user_id = (int)$this->request->post('user_id');
        $order_number = (int)$this->request->post('order_number');
        $money = $this->request->post('money');
        $store_id = (int)$this->request->post('store_id');
        $formId = $this->request->post('formId');
        $pay_type = $this->request->post('pay_type');
        $pay_time = $this->request->post('pay_time');
        if (!$user_id || !$order_number || !$money || !$store_id || !$formId || !$pay_type || !$pay_time) $this->error('缺少参数');

        $res = PayOrder::create(['order_number' => $order_number, 'store_id' => $store_id, 'user_id' => $user_id, 'money' => $money, 'pay_time' => $pay_time, 'pay_type' => $pay_type]);
        if ($res) {
            if (CompanyStore::where(['id' => $store_id])->update(['auditstatus' => 'paid_the_money'])) {
                //新增到收益表
                $time = time();
                $level = self::getLevel($store_id);
                $tel = \addons\cms\model\Config::get(['name' => 'default_phone'])->value;
                if (EarningDetailed::create(['store_id' => $store_id])) {
                    $openid = self::getOpenid($user_id);
                    if ($openid) {
                        $keyword1 = "友车圈{$level}认证费,有效期为一年";
                        $temp_msg = array(
                            'touser' => "{$openid}",
                            'template_id' => "dQRHk_MhaFwaYmeV_LSO6gfWz5TeTb4WIUO_9Y8WItM",
                            'page' => "/pages/mine/mine",
                            'form_id' => "{$formId}",
                            'data' => array(
                                'keyword1' => array(
                                    'value' => "{$keyword1}",
                                ),
                                'keyword2' => array(
                                    'value' => "{$order_number}",
                                ),
                                'keyword3' => array(
                                    'value' => "{$money}",
                                ),
                                'keyword4' => array(
                                    'value' => date('Y-m-d H:i:s', time()),
                                ),
                                'keyword5' => array(
                                    'value' => "{$tel}",
                                )
                            ),
                        );
                        $res = $this->sendXcxTemplateMsg(json_encode($temp_msg));
                        $res['errcode' == 0] ? $this->success('支付完成') : $this->error('支付完成，但模板消息推送失败');
                    }
                    $this->error('支付失败，获取用户openid失败');
                }
                $this->error('支付失败，收益表新增失败');
            }
            $this->error('支付失败，更新门店支付字段失败');

        }

    }

    /**
     * 发送小程序模板消息
     * @param $data
     * @return array
     */

    public function sendXcxTemplateMsg($data = '')
    {
        $access_token = self::getWxtoken()['access_token'];
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={$access_token}";
        return posts($url, $data);
    }

    /**
     * 获取支付店铺等级
     * @param $store_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getLevel($store_id)
    {
        return collection(CompanyStore::with(['storelevel' => function ($q) {
            $q->withField(['partner_rank', 'id']);
        }])->where(['id' => $store_id])->field('id,level_id')->select())->toArray()[0]['storelevel']['partner_rank'];
    }

    /**
     * 获取用户openid
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getOpenid($user_id)
    {
        return Db::name('third')->where(['user_id' => $user_id])->find()['openid'];
    }


}