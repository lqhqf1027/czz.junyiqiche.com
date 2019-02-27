<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/24
 * Time: 10:12
 */

namespace addons\cms\model;


use think\Model;

class EarningDetailed extends Model
{
    // 表名
    protected $name = 'earning_detailed';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'createtime';

    protected $updateTime = 'updatetime';

    // 定义全局的查询范围
    protected function base($query)
    {
        $query->where('status', 'normal');
    }

    public function store() 
    {
        return $this->belongsTo('CompanyStore', 'store_id', 'id')->setEagerlyType(0);
    }    


}