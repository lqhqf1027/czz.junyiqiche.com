<?php

namespace addons\sfc\controller;

use addons\sfc\model\Page as PageModel;
use think\Config;

/**
 * sfc单页控制器
 * Class Page
 * @package addons\sfc\controller
 */
class Page extends Base
{

    public function index()
    {
        $diyname = $this->request->param('diyname');
        if ($diyname && !is_numeric($diyname)) {
            $page = PageModel::getByDiyname($diyname);
        } else {
            $id = $diyname ? $diyname : $this->request->param('id', '');
            $page = PageModel::get($id);
        }
        if (!$page || $page['status'] != 'normal') {
            $this->error(__('No specified page found'));
        }
        $this->view->assign("__PAGE__", $page);
        Config::set('sfc.title', $page['title']);
        Config::set('sfc.keywords', $page['keywords']);
        Config::set('sfc.description', $page['description']);
        $template = preg_replace("/\.html$/i", "", $page['showtpl'] ? $page['showtpl'] : 'page');
        return $this->view->fetch('/' . $template);
    }

}
