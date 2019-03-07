<?php
//+----------------------------------------------------------------------
//| 版权所有 YI ，并保留所有权利
//| 这不是一个自由软件！禁止任何形式的拷贝、修改、发布
//+----------------------------------------------------------------------
//| 开发者: YI
//| 时间  : 9:32
//+----------------------------------------------------------------------
namespace wechat;
/**微信类
 * 微信官方文档：https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1496904104_cfEfT
 * Class wx
 */
class Wx
{
    protected $appid;
    protected $secret;

    public function __construct($appid = '', $secret = '')
    {

        $this->appid = $appid;
        $this->secret = $secret;
    }

    /**
     * 聚合支付
     */
    public function clientApiPay($payConfig)
    {
        \think\Loader::import('Payment.Client.Client', EXTEND_PATH, '.php');
        $clientApy = new \Client($this->config['clientApy'], $payConfig['openid']);
        $clientApy->setNotify('');
        $back = $clientApy->unifiedorder($payConfig['number'], $payConfig['total_fee'], $payConfig['desc']);
        if ($back['code']) return $back;
    }

    /**
     * 聚合支付回调
     */
    public function ClientSetNotify()
    {
        $xml = file_get_contents('php://input');
        $xml = objectToArray(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $out_trade_no = $xml['out_trade_no'];##订单号
        $out_transaction_id = $xml['out_transaction_id'];##商户号
        $this->payOrderChk($out_trade_no, $out_transaction_id);
    }

    /**微信公众号支付
     * @param $payConfig  订单配置
     * @return JsApi   返回数据
     */
    public function wxJsApiPay($payConfig)
    {
        \think\Loader::import('Payment.Wxpay.example.jsapi', EXTEND_PATH, '.php');
        $wx = new \JsApi($payConfig);
        $jsApiParameters = $wx->jsApiParameters;##把这个值传到前端页面
        return $jsApiParameters;
    }

    /** 就是支付的审核填写的回调地址
     * 微信异步回调地址 检测微信结果
     */
    public function notifyWxPay()
    {
        \think\Loader::import('Payment.Wxpay.example.notify', EXTEND_PATH, '.php');
        $wx = new \Notify($this);
        $wx->Handle(false);
    }

    /**  支付成功回自动进行到这个页面
     * @param $order_num  订单号
     * @param $pay_num    流水单号
     * @return string  返回成功信息
     */
    function payOrderChk($order_num, $pay_num)
    {
        $order = db('order')->where('number', $order_num)->field('paystatus')->find();
        if (!$order['paystatus']) {
            $update['transaction_number'] = $pay_num;##交易流水号
            $update['api_time'] = time();##支付时间
            $update['paystatus'] = 1;
            $update['shippingStatus'] = 0;
            $update['orderState'] = 1;
            db('order')->where('number', $order_num)->update($update);
        }
        return 'success';
    }


}