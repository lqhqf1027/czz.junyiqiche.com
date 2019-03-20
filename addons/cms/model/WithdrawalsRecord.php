<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/3/13
 * Time: 16:41
 */

namespace addons\cms\model;


use think\Model;

class WithdrawalsRecord extends Model
{
    protected $name = 'withdrawals_record';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'createtime';

    protected $updateTime = false;

    public function store()
    {
        return $this->belongsTo('CompanyStore', 'store_id', 'id')->setEagerlyType(0);
    }

    protected function getCreateTimeAttr($value)
    {
        return date('Y-m-d',$value);
    }
}