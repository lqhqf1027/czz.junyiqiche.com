<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/28
 * Time: 10:55
 */

namespace addons\cms\model;


use think\Model;

class Brand extends Model
{

       protected $name = 'brand';

// 定义全局的查询范围
    protected function base($query)
    {
        $query->where('status', 'normal');
    }

}