<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/28
 * Time: 10:55
 */

namespace addons\cms\model;


use think\Model;



class ModelsInfo  extends Model
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
    protected function base($query)
    {
        $query->where('shelfismenu','1');
    }

    public function setStoredescriptionAttr($value)
    {
        return emoji_encode($value);
    }

    public function getStoredescriptionAttr($value)
    {
        return emoji_decode($value);
    }

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
        return $this->belongsTo('Brand', 'brand_id', 'id')->setEagerlyType(0);
    }

    public function publisherUser()
    {
        return $this->belongsTo('User', 'user_id', 'id')->setEagerlyType(0);
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
