<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/31
 * Time: 13:50
 */

namespace addons\cms\model;


use think\Model;

class User extends Model
{
    protected $name = 'user';

    public function companystore()
    {
        return $this->hasMany('CompanyStore', 'user_id', 'id')->field('id,store_address,phone,
        store_qrcode,level_id,user_id');
    }

    public function companystoreone()
    {
        return $this->hasOne('CompanyStore', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function storeHasMany()
    {
        return $this->hasMany('CompanyStore', 'user_id', 'id')->field('id,auditstatus,store_name,level_id,user_id');
    }

    // 定义全局的查询范围
    protected function base($query)
    {
        $query->where('status', 'normal');
    }
}