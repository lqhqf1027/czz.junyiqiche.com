<?php

namespace app\admin\model\buycar;

use think\Model;

class Model extends Model
{
    // 表名
    protected $name = 'buycar_model';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'factorytime_text',
        'car_licensetime_text'
    ];
    

    



    public function getFactorytimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['factorytime']) ? $data['factorytime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCarLicensetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['car_licensetime']) ? $data['car_licensetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setFactorytimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setCarLicensetimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function brand()
    {
        return $this->belongsTo('Brand', 'brand_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
