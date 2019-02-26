<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/24
 * Time: 17:03
 */

namespace addons\cms\model;


use think\Model;

class Distribution extends Model
{
    protected $name = 'distribution';

    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 关联店铺表
     * @return \think\model\relation\BelongsTo
     */
    public function store()
    {
        return $this->belongsTo('CompanyStore', 'level_store_id', 'id')->setEagerlyType(0);
    }

}