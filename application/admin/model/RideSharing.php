<?php

namespace app\admin\model;

use think\Model;

class RideSharing extends Model
{
    // 表名
    protected $name = 'ride_sharing';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'starting_time_text'
    ];
    

    



    public function getStartingTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['starting_time']) ? $data['starting_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setStartingTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


}
