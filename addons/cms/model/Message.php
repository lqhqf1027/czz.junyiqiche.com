<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/3/1
 * Time: 17:26
 */

namespace addons\cms\model;


use think\Model;

class Message extends Model
{
     protected $name = 'cms_message';

    // // 定义全局的查询范围
     protected function base($query)
     {
         $query->where('ismenu', 1);
     }
}