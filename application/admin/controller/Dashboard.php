<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\model\CompanyStore;
use think\Config;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
      
        return $this->view->fetch();
    }

    /** 
     * 店铺状态
     */
    public function hint()
    {
        $result = Collection(CompanyStore::field('id,store_name,auditstatus,store_img')->select())->toArray();

        return $result;
        
    }

}
