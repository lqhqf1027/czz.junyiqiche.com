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

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id')->setEagerlyType(0);
    }

    public function level()
    {
        return $this->belongsTo('StoreLevel', 'level_id', 'id')->setEagerlyType(0);
    }

    public function companyStore()
    {
        return $this->belongsTo('CompanyStore', 'store_id', 'id')->setEagerlyType(0);
    }

    public function getTimeendAttr($value)
    {
        return date('Y-m-d H:i:s',strtotime($value));
    }
}