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
use think\Cache;
use think\Config;
use Think\Db;
use app\common\library\Auth;

use think\Env;
use think\Loader;
use think\Exception;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');
Loader::import('WxPay.WxPay', EXTEND_PATH, '.Notify.php');

class Wxpay extends Base
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $model = '';
    protected $user_id;
    protected $store_id;

    public function _initialize()
    {
        parent::_initialize();
    }

//    public function __construct()
//    {
//
//    }

    /**
     * 商家认证合作支付
     * @return mixed|\成功时返回，其他抛异常
     * @throws \WxPayException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function certification_wxPay()
    {

        if (!$this->request->isPost()) $this->error('非法请求');
        $this->user_id = (int)$this->request->post('user_id');
        $out_trade_no = $this->request->post('out_trade_no');
        $money = $this->request->post('money') * 100;
        $this->store_id = (int)$this->request->post('store_id');
        if (!$this->user_id || !$out_trade_no || !$money || !$this->store_id) $this->error('缺少参数');
        if (self::checkPay($this->store_id, $out_trade_no)) $this->error('该订单已存在支付！', 'error');
        $openid = self::getOpenid($this->user_id);
        //     初始化值对象
        $input = new \WxPayUnifiedOrder();
        //     文档提及的参数规范：商家名称-销售商品类目
        $input->SetBody("友车圈认证店铺年费");
        //     订单号应该是由小程序端传给服务端的，在用户下单时即生成，demo中取值是一个生成的时间戳
        $input->SetOut_trade_no("$out_trade_no");
        //     费用应该是由小程序端传给服务端的，在用户下单时告知服务端应付金额，demo中取值是1，即1分钱
        $input->SetTotal_fee("$money");
        $input->SetNotify_url("https://czz.junyiqiche.com/addons/cms/wxapp.Wxpay/certification_wxPay_noTify");
        $input->SetTrade_type("JSAPI");
        //     由小程序端传给服务端
        $input->SetOpenid($openid);
        //     向微信统一下单，并返回order，它是一个array数组
        $order = \WxPayApi::unifiedOrder($input);
        if ($order['result_code'] == 'SUCCESS') {
            $order['key'] = Env::get('wxpay.key');
            $order['appid'] = Config::get('oauth')['appid'];
            return $order;
//            $this->success('预支付successful', $order);
        }
        $this->error('签名失败', $order);

    }

    public function certification_wxPay_noTify()
    {

        $res = file_get_contents("php://input");
        $getData = xmlstr_to_array($res);
        if (($getData['total_fee']) && ($getData['result_code'] == 'SUCCESS')) {  //支付回调通知成功
            //将回调通知里的订单号前缀user_id +store_id 分隔
            $user_id = explode('_', $getData['out_trade_no'])[0];
//            Db::name('user')->where('id', 20)->setField('username', $user_id);
            $store_id = explode('_', $getData['out_trade_no'])[1];
            $getData['out_trade_no'] = explode('_', $getData['out_trade_no'])[2];
            $res = PayOrder::create(
                ['out_trade_no' => $getData['out_trade_no'],
                    'store_id' => $store_id,
                    'user_id' => $user_id,
                    'time_end' => $getData['time_end'],
                    'total_fee' => $getData['total_fee'] / 100,
                    'trade_type' => $getData['trade_type'],
                    'bank_type' => $getData['bank_type'],
                    'transaction_id' => $getData['transaction_id'],
                    'pay_type' => 'certification'
                ]
            );
            if ($res) {
                //
                if (CompanyStore::where(['id' => $store_id])->update(['auditstatus' => 'paid_the_money'])) {
                    //新增到收益表
                    $time = time();
                    $level = self::getLevel($store_id);
                    $tel = \addons\cms\model\Config::get(['name' => 'default_phone'])->value;
                        $order_number = $getData['out_trade_no'];
                        $money = $getData['out_trade_no'];
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
                                        'value' => "{$res->out_trade_no}",
                                    ),
                                    'keyword3' => array(
                                        'value' => "{$res->total_fee}",
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

                    $this->error('支付失败，收益表新增失败');
                }
                $return['return_code'] = 'SUCCESS';
                $return['return_msg'] = 'OK';
                $xml_post = '<xml>
                    <return_code>' . $return['return_code'] . '</return_code>
                    <return_msg>' . $return['return_msg'] . '</return_msg>
                    </xml>';
                echo $xml_post;
                exit;
            }
        } else {
            Cache::set('order_number', 3);

        }


//
//        return $notify->NotifyProcess();

    }

    /*
 * 给微信发送确认订单金额和签名正确，SUCCESS信息 -xzz0521
 */
    private function return_success()
    {

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
    public static function checkPay($store_id, $out_trade_no)
    {
        return collection(PayOrder::with(['pay_status'])->where(['store_id' => $store_id, 'out_trade_no' => $out_trade_no])->select())->toArray();
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