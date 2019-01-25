<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/24
 * Time: 15:31
 */

namespace addons\cms\model;


use think\Model;

class StoreLevel extends Model
{
    protected $name = 'store_level';

    // 定义全局的查询范围
    protected function base($query)
    {
        $query->where('status', 1);
    }

//    public function getMoneyAttr($value)
//    {
//        $money = floatval($value) / 10000;
//
//        $money = round($money, 1);
//
//        if ($money >= 1) {
//            return $money . '万';
//        }
//
//        return ($money * 10) . '千';
//
//
//    }
}