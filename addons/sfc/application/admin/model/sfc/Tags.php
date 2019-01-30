<?php

namespace app\admin\model\sfc;

use think\Model;

class Tags extends Model
{

    // 表名
    protected $name = 'sfc_test_tags';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    // 追加属性
    protected $append = [
        'url'
    ];

    public function getUrlAttr($value, $data)
    {
        return addon_url('sfc/tags/index', [':name' => $data['name']]);
    }

}
