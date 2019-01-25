<?php

namespace addons\cms\model;

use think\Model;

class StoreUser extends Model
{
    // 表名
    protected $name = 'store_user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [

    ];


    public function companystore(){
        return $this->hasMany('CompanyStore','store_user_id','id')->field('id,store_address,phone,
        store_qrcode,level_id,store_user_id');
    }

    public function companystoreone()
    {
        return $this->hasOne('CompanyStore','store_user_id','id',[],'LEFT')->setEagerlyType(0);
    }

}
