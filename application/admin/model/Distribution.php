<?php

namespace app\admin\model;

use think\Model;

class Distribution extends Model
{
    // 表名
    protected $name = 'distribution';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [

    ];

    //关联店铺
    public function store()
    {
        return $this->belongsTo('CompanyStore', 'level_store_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
