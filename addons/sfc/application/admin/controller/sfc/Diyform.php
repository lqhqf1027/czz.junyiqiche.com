<?php

namespace app\admin\controller\sfc;

use app\common\controller\Backend;

/**
 * 自定义表单表
 *
 * @icon fa fa-circle-o
 */
class Diyform extends Backend
{

    /**
     * Model模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\sfc\Diyform;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

}
