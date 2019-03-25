<?php
/**
 * Created by PhpStorm.
 * User: glen9
 * Date: 2019/3/7
 * Time: 10:44
 */

namespace addons\cms\model;


use think\Model;

class FormIds extends Model
{

    protected $name = "form_ids";
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = '';

    // 定义全局的查询范围
    protected function base($query)
    {
        $query->whereTime('createtime', '>',strtotime('-6 days'))->where(['status' => 1, 'form_id' => ['neq', 'the formId is a mock one']])->field('form_id');
    }

}