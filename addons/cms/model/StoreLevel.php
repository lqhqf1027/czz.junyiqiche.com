<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/24
 * Time: 15:31
 */

namespace addons\cms\model;


use think\Model;

class StoreLevel extends Model
{
    protected $name = 'store_level';

    // 追加属性
    protected $append = [
        'condition',
    ];

    // 定义全局的查询范围
    protected function base($query)
    {
        $query->where('status', 'normal');
    }

    public function getConditionAttr($value)
    {
        return 'visible';
    }

}