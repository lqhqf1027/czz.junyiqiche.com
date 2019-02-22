<?php

namespace app\admin\model;

use think\Model;

class CompanyStore extends Model
{
    // 表名
    protected $name = 'company_store';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'statuss_text',
        'auditstatus_text'
    ];
    

    
    public function getStatussList()
    {
        return ['normal' => __('Normal'),'hidden' => __('Hidden')];
    }     

    public function getAuditstatusList()
    {
        return ['audit_failed' => __('Audit_failed'),'pass_the_audit' => __('Pass_the_audit'),'wait_the_review' => __('Wait_the_review')];
    }     


    public function getStatussTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['statuss']) ? $data['statuss'] : '');
        $list = $this->getStatussList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getAuditstatusTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['auditstatus']) ? $data['auditstatus'] : '');
        $list = $this->getAuditstatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function storelevel()
    {
        return $this->belongsTo('StoreLevel', 'level_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
