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
use addons\cms\model\FormIds;
use addons\cms\model\PayOrder;
use addons\cms\model\Distribution;
use addons\cms\model\StoreLevel;
use think\Cache;
use think\Config;
use Think\Db;
use app\common\library\Auth;
use addons\cms\model\Config as ConfigModel;
use think\Env;
use think\Loader;
use think\Exception;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');
Loader::import('WxPay.WxPay', EXTEND_PATH, '.Notify.php');

class StoreCertificationPay extends Base
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $model = '';

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
        $user_id = (int)$this->request->post('user_id');
        $formId = $this->request->post('formId');
        $f = Common::writeFormId($formId, $user_id);
        $out_trade_no = $this->request->post('out_trade_no');
        $money = $this->request->post('money') * 100;
        $store_id = (int)$this->request->post('store_id');
        if (!$user_id || !$out_trade_no || !$money || !$store_id) $this->error('缺少参数');
        $checkOrder = end(explode('_', $out_trade_no));
        if (Common::checkPay($store_id, $checkOrder)) $this->error('该订单已存在支付！', 'error');
        $openid = Common::getOpenid($user_id);
        //     初始化值对象
        $input = new \WxPayUnifiedOrder();
        //     文档提及的参数规范：商家名称-销售商品类目
        $input->SetBody("友车圈认证店铺年费");
        //     订单号应该是由小程序端传给服务端的，在用户下单时即生成，demo中取值是一个生成的时间戳
        $input->SetOut_trade_no("$out_trade_no");
        //     费用应该是由小程序端传给服务端的，在用户下单时告知服务端应付金额，demo中取值是1，即1分钱
        $input->SetTotal_fee("$money");
        $input->SetNotify_url($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/addons/cms/wxapp.store_certification_pay/certification_wxPay_noTify');
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

    /**
     * 店铺认证支付回调
     */
    public function certification_wxPay_noTify()
    {
        $res = file_get_contents("php://input");
        $getData = xmlstr_to_array($res);
        if (($getData['total_fee']) && ($getData['result_code'] == 'SUCCESS')) {  //支付回调通知成功
            //将回调通知里的订单号前缀user_id +store_id 分隔
            $user_id = explode('_', $getData['out_trade_no'])[0]; //获取user_id
            $store_id = explode('_', $getData['out_trade_no'])[1]; //获取门店id
            $getData['out_trade_no'] = explode('_', $getData['out_trade_no'])[2]; //获取订单号
            Db::startTrans();
            try {
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
     * 店铺认证支付成功后接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function after_successful_payment()
    {
        $user_id = $this->request->post('user_id');
        $store_id = $this->request->post('store_id');
        $formId = $this->request->post('formId');
        if (!$user_id || !$store_id) {
            $this->error('缺少参数');
        }

        Db::startTrans();
        try {
            $formId = current(array_values(Common::getFormId($user_id)))['form_id']; //获取formId
            $level = Common::getLevel($store_id);
            $tel = \addons\cms\model\Config::get(['name' => 'default_phone'])->value;
            $o = Common::getPayOrderNum($user_id, $store_id);
            $order_number = $o->out_trade_no;
            $money = $o->total_fee;
            $openid = Common::getOpenid($user_id);
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
                            'value' => "{$money}元",
                        ),
                        'keyword4' => array(
                            'value' => date('Y-m-d H:i:s', time()),
                        ),
                        'keyword5' => array(
                            'value' => "{$tel}",
                        )
                    ),
                );
                $res = Common::sendXcxTemplateMsg(json_encode($temp_msg));
                if ($res['errcode'] == 0) {
                    FormIds::where(['user_id' => $user_id, 'form_id' => $formId])->delete();

                }
            }
//            Db::name('form_ids')->where(['user_id' => $user_id, 'form_id' => $formId])->setField('status', 0);
            CompanyStore::where(['user_id' => $user_id])->setField('auditstatus', 'paid_the_money');

            $company_info = CompanyStore::field('id')
                ->with(['belongsStoreLevel' => function ($q) {
                    $q->withField('id,money');
                }])->where([
                    'user_id' => $user_id,
                    'auditstatus' => 'paid_the_money'
                ])->find();

            if (!$company_info) {
                throw new Exception('未知错误');
            }

            //查出收益率
            $rate = ConfigModel::where('group', 'rate')->column('value');

            $check_earning = EarningDetailed::get(['store_id' => $company_info['id']]);

            //如果没有收益明细表，创建
            if (!$check_earning) {
                EarningDetailed::create(['store_id' => $company_info['id']]);
            }

            //能获取的1级收益
            $first_income = $company_info['belongs_store_level']['money'] * floatval($rate[0]);
            //能获取的2级收益
            $second_income = $company_info['belongs_store_level']['money'] * floatval($rate[1]);

            Distribution::where('level_store_id', $company_info['id'])->update([
                'earnings' => $first_income,
                'second_earnings' => $second_income
            ]);

            $up_id = Distribution::get(['level_store_id' => $company_info['id']])->store_id;
            if ($up_id) {
                //加锁查询上级的金额信息
                $up_data = EarningDetailed::field('first_earnings,total_earnings')->where('store_id', $up_id)->lock(true)->select();
                //如果有上级，将上级的收益加入上级收益明细表中
                EarningDetailed::where('store_id', $up_id)
                    ->update(['first_earnings' => $up_data['first_earnings'] + $first_income,
                        'total_earnings' => $up_data['total_earnings'] + $first_income]);

                $up_up_id = Distribution::get(['level_store_id' => $up_id])->store_id;

                if ($up_up_id) {
                    //加锁查询上上级的金额信息
                    $up_up_data = EarningDetailed::field('second_earnings,total_earnings')->where('store_id', $up_id)->lock(true)->select();
                    //如果有上上级，将上级的收益加入上上级收益明细表中
                    EarningDetailed::where('store_id', $up_up_id)
                        ->update(['second_earnings' => $up_up_data['second_earnings'] + $second_income,
                            'total_earnings' => $up_up_data['total_earnings'] + $second_income]);
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('请求成功');
    }


}