<?php
/**
 * Created by PhpStorm.
 * User: glen9
 * Date: 2019/3/6
 * Time: 11:56
 */

namespace addons\cms\controller\wxapp;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');
class WxPayNotify extends \WxPayNotify
{
    public function NotifyProcess($data, &$msg)
    {
        $postStr = $GLOBALS['HTTP_RAW_POST_DATA']; // 这里拿到微信返回的数据结果

        $getData = xmlstr_to_array($postStr);
        return $getData;
    }
}