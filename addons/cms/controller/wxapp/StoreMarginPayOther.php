<?php
/**
 * Created by PhpStorm.
 * User: glen9
 * Date: 2019/3/12
 * Time: 11:16
 */

namespace addons\cms\controller\wxapp;


use addons\cms\model\QuotedPrice;
use think\Cache;
use think\Config;
use Think\Db;
use app\common\library\Auth;
use think\Env;
use think\Loader;
use think\Exception;
use addons\cms\model\PayOrder;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');
Loader::import('WxPay.WxPay', EXTEND_PATH, '.Notify.php');

class StoreMarginPayOther extends Base
{
    protected $noNeedLogin = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 卖家保证金支付
     * @return \成功时返回，其他抛异常
     * @throws \WxPayException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function marginPay()
    {
        $user_id = $this->request->post('user_id');
        $money = $this->request->post('money') * 100;
        $formId = $this->request->post('formId');
        $out_trade_no = $this->request->post('out_trade_no');
        if (!$user_id || !$formId || !$out_trade_no || !$money) $this->error('缺少参数');

        //写入formIds表
        Common::writeFormId($formId, $user_id);
        $openid = Common::getOpenid($user_id);
        //     初始化值对象
        $input = new \WxPayUnifiedOrder();
        //     文档提及的参数规范：商家名称-销售商品类目
        $input->SetBody("友车圈车辆交易保证金支付");
        //     订单号应该是由小程序端传给服务端的，在用户下单时即生成，demo中取值是一个生成的时间戳
        $input->SetOut_trade_no("$out_trade_no");
        //     费用应该是由小程序端传给服务端的，在用户下单时告知服务端应付金额，demo中取值是1，即1分钱
        $input->SetTotal_fee("$money");
        $input->SetNotify_url($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/addons/cms/wxapp.store_margin_pay_other/margin_wxPay_noTify');
        $input->SetTrade_type("JSAPI");
        //     由小程序端传给服务端+.
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

    /**
     * 微信回调
     * @throws \think\exception\DbException
     */
    public function margin_wxPay_noTify()
    {
        $res = file_get_contents("php://input");
        $getData = xmlstr_to_array($res);
        if (($getData['total_fee']) && ($getData['result_code'] == 'SUCCESS')) {  //支付回调通知成功
            //将回调通知里的订单号前缀user_id +store_id 分隔
            $explodeData = explode('_', $getData['out_trade_no']);
            $user_id = $explodeData[1]; //获取user_id
            $store_id = Common::getStoreInfo($user_id)->id;
            Db::startTrans();
            try {
                $res = PayOrder::create(
                    ['out_trade_no' => $explodeData[3],
                        'store_id' => $store_id,
                        'user_id' => $user_id,
                        'time_end' => $getData['time_end'],
                        'total_fee' => $getData['total_fee'] / 100,
                        'trade_type' => $getData['trade_type'],
                        'bank_type' => $getData['bank_type'],
                        'transaction_id' => $getData['transaction_id'],
                        $explodeData[0] == 'seller' ? 'seller_id' : 'buyers_id' => $user_id,
                        'buy_trading_models_id' => $explodeData[2],
                        'pay_type' => 'bond'
                    ]
                );
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                echo exit('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>');

            }
            echo exit('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');

        } else {
            echo exit('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>');

        }


    }

    /**
     * 保证金支付成功后接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function after_successful_payment()
    {
        $user_id = $this->request->post('user_id');
        $formId = $this->request->post('formId');
        $out_trade_no = $this->request->post('out_trade_no');

        if (!$user_id || !$formId || !$out_trade_no) $this->error('缺少参数');
        $out_trade_no_new = explode('_', $out_trade_no)[3];
        //写入formIds表
        $openid = Common::getOpenid($user_id);
        Db::startTrans();
        try {
            $formId = Common::getFormId($user_id); //获取formId

            $payUserType = explode('_', $out_trade_no)[0] == 'seller' ? 'seller_id' : 'buyers_id'; //卖家或者买家身份

            //检查支付状态
            $checkOrder = PayOrder::get(function ($q) use ($out_trade_no_new, $user_id, $payUserType) {
                $q->where(['out_trade_no' => $out_trade_no_new, 'user_id' => $user_id, $payUserType => $user_id]);
            });
            if (is_null($checkOrder)) throw new Exception('未检查到支付订单');
            //修改报价表   买卖家

            $res= QuotedPrice::where([
                'buy_car_id' => explode('_', $out_trade_no)[2],
                'deal_status' => 'click_the_deal',
                $payUserType == 'seller_id' ? 'user_ids' : 'by_user_ids' => $user_id
            ])->update([$payUserType == 'seller_id' ? 'seller_payment_status' : 'buyer_payment_status' => 'already_paid']);
            if(!$res) throw new Exception('更新失败');

            //修改店铺等级为 升级后的level
            /*  if ($openid) {

                  $temp_msg = array(
                      'touser' => "{$openid}",
                      'template_id' => "-pD8LYQSrGITNoQU45yHS-aXtwfFzcpOXuOaWf_2Jso",
                      'page' => "/pages/mine/mine",
                      'form_id' => "{$formId}",
                      'data' => array(
                          'keyword1' => array(
                              'value' => "{$store_name}",
                          ),
                          'keyword2' => array(
                              'value' => "{$newKey}",
                          ),
                          'keyword3' => array(
                              'value' => "升级{$keyword2}成功",
                          ),
                          'keyword4' => array(
                              'value' => date('Y-m-d H:i:s', time()),
                          ),

                      ),
                  );
                  $res = Common::sendXcxTemplateMsg(json_encode($temp_msg));
                  if ($res['errcode'] == 0) {
                      FormIds::where(['user_id' => $user_id, 'form_id' => $formId])->delete();
                  }
              }*/
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), '');
        }
        $this->success('支付成功！');
    }

    /**
     * 卖家发货交易流程
     * @throws \think\exception\DbException
     */
    public function sellerConfirmTheDelivery()
    {
        $user_id = $this->request->post('user_id');
        $formId = $this->request->post('formId');
        $trading_models_id = (int)$this->request->post('trading_models_id');//车辆交易Id
        $user_ids = $this->request->post('user_ids');//报价人的user_ids
        $quotationtime = $this->request->post('quotationtime');//报价时间
        if (!$user_id || !$formId || !$trading_models_id || !$quotationtime) $this->error('缺少参数');
        //写入formIds表
        Common::writeFormId($formId, $user_id);
        //查询买家是否已确认收货
        $buy_user = QuotedPrice::get(['buyer_payment_status' => 'confirm_receipt', 'quotationtime' => $quotationtime, 'buy_car_id' => $trading_models_id, 'user_ids' => $user_id]);
        //更新卖家字段
        $q = QuotedPrice::where(['user_ids' => $user_id, 'quotationtime' => $quotationtime, 'seller_payment_status' => 'to_the_account'])
            ->update(['seller_payment_status' => $buy_user ? 'confirm_receipt' : 'waiting_for_buyers'])
            ? $this->success('操作成功') : $this->error('操作失败');

    }

    /**
     * 买家收货交易流程
     * @throws \think\exception\DbException
     */
    public function buyersConfirmTheDelivery()
    {
        $user_id = $this->request->post('user_id');
        $formId = $this->request->post('formId');
        $trading_models_id = $this->request->post('trading_models_id');//车辆交易Id
//        $buyer_payment_status = $this->request->post('buyer_payment_status');
//        $user_ids = $this->request->post('user_ids');//报价人的user_ids
        $by_user_ids = $this->request->post('by_user_ids');//卖家的id
        $quotationtime = $this->request->post('quotationtime');//报价时间
        if (!$user_id || !$formId || !$trading_models_id || !$by_user_ids || !$quotationtime) $this->error('缺少参数');
        //写入formIds表
        Common::writeFormId($formId, $user_id);
        //查询卖家是否正在等待买家确认收货
//        $buy_user = QuotedPrice::get(['seller_payment_status' => 'waiting_for_buyers', 'quotationtime' => $quotationtime, 'models_info_id' => $trading_models_id, 'by_user_ids' => $by_user_ids]);


        //更新买家字段
        $q = QuotedPrice::where(['by_user_ids' => $user_id, 'quotationtime' => $quotationtime, 'buyer_payment_status' => 'to_the_account', 'buy_car_id' => $trading_models_id])
            ->update(['buyer_payment_status' => 'confirm_receipt', 'seller_payment_status' =>'confirm_receipt'])
            ? $this->success('操作成功') : $this->error('操作失败');

    }
}