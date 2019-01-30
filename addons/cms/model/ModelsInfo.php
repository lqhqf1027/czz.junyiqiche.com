<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/28
 * Time: 10:55
 */

namespace addons\cms\model;


use think\Model;

class ModelsInfo extends Model
{
    protected $name = 'models_info';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'createtime';

    protected $updateTime = 'updatetime';

    // 定义全局的查询范围
    protected function base($query)
    {
        $query->where('shelfismenu','1');
    }

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

    public function setCarLicenseTimeAttr($value)
    {
        return strtotime($value);
    }

    public function companystore()
    {
        return $this->belongsTo('CompanyStore', 'store_id', 'id')->setEagerlyType(0);
    }

}