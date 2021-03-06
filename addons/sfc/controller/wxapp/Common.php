<?php

namespace addons\sfc\controller\wxapp;

use addons\sfc\model\Block;
use addons\sfc\model\Channel;
use app\common\model\Addon;
use think\Config;

/**
 * 公共
 */
class Common extends Base
{

    protected $noNeedLogin = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 初始化
     */
    public function init()
    {
        //焦点图
        $bannerList = [];
        $list = Block::getBlockList(['name' => 'focus', 'row' => 5]);
        foreach ($list as $index => $item) {
            $bannerList[] = ['image' => cdnurl($item['image'], true), 'url' => '/', 'title' => $item['title']];
        }

        //首页Tab列表
        $indexTabList = $newsTabList = $productTabList = [['id' => 0, 'title' => '全部']];
        $channelList = Channel::where('status', 'normal')
            ->where('type', 'in', ['list'])
            ->field('id,parent_id,model_id,name,diyname')
            ->order('weigh desc,id desc')
            ->select();
        foreach ($channelList as $index => $item) {
            $data = ['id' => $item['id'], 'title' => $item['name']];
            $indexTabList[] = $data;
            if ($item['model_id'] == 1) {
                $newsTabList[] = $data;
            }
            if ($item['model_id'] == 2) {
                $productTabList[] = $data;
            }
        }

        //配置信息
        $upload = Config::get('upload');
        $upload['cdnurl'] = $upload['cdnurl'] ? $upload['cdnurl'] : cdnurl('', true);
        $upload['uploadurl'] = $upload['uploadurl'] == 'ajax/upload' ? cdnurl('/ajax/upload', true) : $upload['cdnurl'];
        $config = [
            'upload' => $upload
        ];

        $data = [
            'bannerList'     => $bannerList,
            'indexTabList'   => $indexTabList,
            'newsTabList'    => $newsTabList,
            'productTabList' => $productTabList,
            'config'         => $config
        ];
        $this->success('', $data);

    }


}
