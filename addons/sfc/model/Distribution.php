<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/24
 * Time: 17:03
 */

namespace addons\sfc\model;


use think\Model;

class Distribution extends Model
{
    protected $name = 'distribution';

    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}