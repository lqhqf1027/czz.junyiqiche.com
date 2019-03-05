<?php
/**
 * Created by PhpStorm.
 * User: glen9
 * Date: 2019/3/5
 * Time: 11:57
 */

namespace addons\cms\model;


use think\Model;

class PayOrder extends Model
{
    protected $name = "pay_order";

    public function payStatus()
    {
        return $this->belongsTo('CompanyStore', 'store_id', 'id')->setEagerlyType(1);
    }
}