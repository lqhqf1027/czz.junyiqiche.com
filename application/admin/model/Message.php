<?php

namespace app\admin\model;

use think\Model;

class Message extends Model
{
    // 表名
    protected $name = 'cms_message';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'analysis_text'
    ];
    

    
    public function getAnalysisList()
    {
        return ['1' => __('Analysis 1'),'2' => __('Analysis 2')];
    }     


    public function getAnalysisTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['analysis']) ? $data['analysis'] : '');
        $list = $this->getAnalysisList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
