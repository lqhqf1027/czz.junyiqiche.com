<?php
/**
 * Created by PhpStorm.
 * User: glen9
 * Date: 2019/2/27
 * Time: 14:27
 */

namespace addons\cms\controller\wxapp;

use Hooklife\ThinkphpWechat\Wechat;

//use EasyWeChat\Payment\Order;

class Order extends Base
{
    public function Payment()
    {
        $app = Wechat::app();
    }
}