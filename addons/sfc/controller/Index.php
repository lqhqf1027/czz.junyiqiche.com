<?php

namespace addons\sfc\controller;

use think\Config;

/**
 * sfc首页控制器
 * Class Index
 * @package addons\sfc\controller
 */
class Index extends Base
{

    public function index()
    {
        Config::set('sfc.title', Config::get('sfc.title') ? Config::get('sfc.title') : __('Home'));
        return $this->view->fetch('/index');
    }

}
