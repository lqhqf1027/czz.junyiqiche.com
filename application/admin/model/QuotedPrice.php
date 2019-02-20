<?php

namespace app\admin\model;

use think\Model;

class QuotedPrice extends Model
{
    // 表名
    protected $name = 'quoted_price';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'quotationtime_text',
        'type_text'
    ];
    

    
    public function getTypeList()
    {
        return ['buy' => __('Buy'),'sell' => __('Sell')];
    }     


    public function getQuotationtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['quotationtime']) ? $data['quotationtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getTypeTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setQuotationtimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


    public function user()
    {
        return $this->belongsTo('User', 'user_ids', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    public function BuycarModel()
    {
        return $this->belongsTo('BuycarModel', 'buy_car_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    public function ModelsInfo()
    {
        return $this->belongsTo('ModelsInfo', 'models_info_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


}
