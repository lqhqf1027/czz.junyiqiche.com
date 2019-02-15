<?php

namespace app\admin\model;

use think\Model;

class Clue extends Model
{
    // 表名
    protected $name = 'clue';
    
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
        return $this->belongsTo('CompanyStore', 'store_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    //关联品牌
    public function brand()
    {
        return $this->belongsTo('BrandCate', 'brand_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
