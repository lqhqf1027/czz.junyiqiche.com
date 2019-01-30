<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/29
 * Time: 17:45
 */

namespace addons\cms\model;


use think\Model;

class Clue extends Model
{
    protected $name = 'clue';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'createtime';

    protected $updateTime = 'updatetime';

    public function setKilometresAttr($value)
    {
        return floatval(findNum($value))*10000;
    }

    public function setGuidePriceAttr($value)
    {
        return floatval(findNum($value))*10000;
    }

    public function setFactoryTimeAttr($value)
    {
        return strtotime($value);
    }

    public function setCarLicensetimeAttr($value)
    {
        return strtotime($value);
    }

    // 定义全局的查询范围
    protected function base($query)
    {
        $query->where('shelfismenu','1');
    }
}