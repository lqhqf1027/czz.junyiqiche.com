<?php

namespace addons\sfc\controller;

use addons\sfc\library\OrderException;
use addons\sfc\model\Archives;
use think\Exception;

/**
 * 订单控制器
 * Class Order
 * @package addons\sfc\controller
 */
class Order extends Base
{

    public function _initialize()
    {
        return parent::_initialize();
    }

    /**
     * 创建订单并发起支付请求
     * @throws \think\exception\DbException
     */
    public function submit()
    {
        if (!$this->auth->isLogin()) {
            //这里可以控制是否登录后才可以创建订单
            //$this->error("请登录后再进行操作!");
        }
        $id = $this->request->request('id');
        $paytype = $this->request->request('paytype');
        $archives = Archives::get($id);
        if (!$archives || ($archives['user_id'] != $this->auth->id && $archives['status'] != 'normal') || $archives['deletetime']) {
            $this->error('未找到指的文档');
        }
        try {
            \addons\sfc\model\Order::submitOrder($id, $paytype ? $paytype : 'wechat');
        } catch (OrderException $e) {
            if ($e->getCode() == 1) {
                $this->success($e->getMessage(), $archives->url);
            } else {
                $this->error($e->getMessage());
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        return;
    }

    /**
     * 企业支付通知和回调
     * @throws \think\exception\DbException
     */
    public function epay()
    {
        $type = $this->request->param('type');
        $paytype = $this->request->param('paytype');
        if ($type == 'notify') {
            $pay = \addons\epay\library\Service::checkNotify($paytype);
            if (!$pay) {
                echo '签名错误';
                return;
            }
            $data = $pay->verify();
            try {
                $payamount = $paytype == 'alipay' ? $data['total_amount'] : $data['total_fee'] / 100;
                \addons\sfc\model\Order::settle($data['out_trade_no'], $payamount);
            } catch (Exception $e) {

            }
            echo $pay->success();
        } else {
            $pay = \addons\epay\library\Service::checkReturn($paytype);
            if (!$pay) {
                $this->error('签名错误');
            }
            $data = $pay->verify();

            $archives = Archives::get($data['out_trade_no']);
            if (!$archives) {
                $this->error('未找到文档信息!');
            }
            //你可以在这里定义你的提示信息,但切记不可在此编写逻辑
            $this->success("恭喜你！支付成功!", $archives->url);
        }
        return;
    }

}
