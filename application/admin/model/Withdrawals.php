<?php

namespace app\admin\model;

use think\Model;

class Withdrawals extends Model
{
    // 表名
    protected $name = 'withdrawals_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['cash_in' => __('提现中'),'has_been_presented' => __('提现成功')];
    }     


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function store()
    {
        return $this->belongsTo('CompanyStore', 'store_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
