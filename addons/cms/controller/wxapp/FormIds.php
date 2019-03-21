<?php
/**
 * Created by PhpStorm.
 * User: glen9
 * Date: 2019/3/8
 * Time: 13:59
 */

namespace addons\cms\controller\wxapp;


class FormIds extends Base
{
    protected $noNeedLogin = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function storageFormIds()
    {
        $user_id = (int)$this->request->post('user_id');
        $formId = $this->request->post('formId');
        if (!$user_id || !$formId) $this->error('缺少参数');

        Common::writeFormId($formId, $user_id) ? $this->success('formId存储成功') : $this->error('存储失败');
    }
}