<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/2/21
 * Time: 14:25
 */

namespace addons\cms\model;


use think\Model;

class AutomotiveInformation extends Model
{
// 表名
    protected $name = 'automotive_information';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'createtime';

    // 定义全局的查询范围
    protected function base($query)
    {
        $query->where('status','normal');
    }

    public function getCreatetimeAttr($value)
    {
        return date("Y-m-d", $value);
    }
}