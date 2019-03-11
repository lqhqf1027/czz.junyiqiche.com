<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/3/11
 * Time: 15:19
 */

namespace addons\cms\model;


use think\Model;

class InformationCategories extends Model
{
    protected $name = 'information_categories';

    // // 定义全局的查询范围
    protected function base($query)
    {
        $query->where('status', 'normal');
    }

    public function automotive()
    {
        return $this->hasMany('AutomotiveInformation', 'categories_id', 'id');
    }
}