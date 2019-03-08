<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/29
 * Time: 11:13
 */

namespace addons\cms\model;


use think\Model;

class BuycarModel extends Model
{
    protected $name = 'buycar_model';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
    protected $append = [
        'type',
    ];

    public function getTypeAttr($value)
    {
        return 'buy';
    }

    public function setStoredescriptionAttr($value)
    {
        return emoji_encode($value);
    }

    public function getStoredescriptionAttr($value)
    {
        return emoji_decode($value);
    }

    // 定义全局的查询范围
    protected function base($query)
    {
        $query->where('shelfismenu','1');
    }

    /**
     * 关联品牌表
     * @return \think\model\relation\BelongsTo
     */
    public function brand()
    {
        return $this->belongsTo('Brand', 'brand_id', 'id')->setEagerlyType(0);
    }

    /**
     * 关联品牌表
     * @return \think\model\relation\BelongsTo
     */
    public function quotedprice()
    {
        return $this->belongsTo('QuotedPrice', 'buy_car_id', 'id')->setEagerlyType(0);
    }


    protected function setFactorytimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setCarLicensetimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

}