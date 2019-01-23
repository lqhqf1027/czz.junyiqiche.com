<?php

namespace app\admin\model;

use think\Model;

class ModelsInfo extends Model
{
    // 表名
    protected $name = 'models_info';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'status_data_text'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    
    public function getStatusDataList()
    {
        return ['for_the_car' => __('Status_data for_the_car'),'the_car' => __('Status_data the_car'),'is_reviewing_pass' => __('Status_data is_reviewing_pass'),'take_the_car' => __('Status_data take_the_car'),'send_the_car' => __('Status_data send_the_car')];
    }     


    public function getStatusDataTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['status_data']) ? $data['status_data'] : '');
        $list = $this->getStatusDataList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function store()
    {
        return $this->belongsTo('CompanyStore', 'store_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
