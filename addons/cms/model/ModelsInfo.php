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

    // 追加属性
    protected $append = [
        'type',
    ];

    public function getTypeAttr($value)
    {
        return 'sell';
    }

    // 定义全局的查询范围
//    protected function base($query)
//    {
//        $query->where('shelfismenu','1');
//    }

//    public function setKilometresAttr($value)
//    {
//        return floatval(findNum($value))*10000;
//    }
//
//    public function setGuidePriceAttr($value)
//    {
//        return floatval(findNum($value))*10000;
//    }

//    public function setFactoryTimeAttr($value)
//    {
//        return strtotime($value);
//    }
//
//    public function setCarLicenseTimeAttr($value)
//    {
//        return strtotime($value);
//    }

    /**
     * 关联店铺表
     * @return \think\model\relation\BelongsTo
     */
    public function companystore()
    {
        return $this->belongsTo('CompanyStore', 'store_id', 'id')->setEagerlyType(0);
    }

    /**
     * 关联品牌表
     * @return \think\model\relation\BelongsTo
     */
    public function brand()
    {
        return $this->belongsTo('BrandCate', 'brand_id', 'id')->setEagerlyType(0);
    }


    /**
     * 关联品牌表
     * @return \think\model\relation\BelongsTo
     */
    public function quotedprice()
    {
        return $this->belongsTo('QuotedPrice', 'models_info_id', 'id')->setEagerlyType(0);
    }

    public function setFactoryTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    public function setCarLicenseTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    public function getCarLicenseTimeAttr($value)
    {
        return $value && !is_numeric($value) ? date("Y-m-d", intval($value)) : $value;
    }

    protected function getFactorytimeAttr($value)
    {
        return $value && !is_numeric($value) ? date("Y-m-d", $value) : $value;
    }


}
