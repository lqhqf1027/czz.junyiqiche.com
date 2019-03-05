<?php

namespace app\admin\model;

use think\Model;

class PayOrder extends Model
{
    // 表名
    protected $name = 'pay_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'pay_time_text',
        'pay_type_text'
    ];
    

    
    public function getPayTypeList()
    {
        return ['certification' => __('Certification'),'up' => __('Up')];
    }     


    public function getPayTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_time']) ? $data['pay_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getPayTypeTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['pay_type']) ? $data['pay_type'] : '');
        $list = $this->getPayTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setPayTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


    public function store()
    {
        return $this->belongsTo('CompanyStore', 'store_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function level()
    {
        return $this->belongsTo('StoreLevel', 'level_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
