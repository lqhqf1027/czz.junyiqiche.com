<?php

namespace addons\cms\controller\wxapp;

use addons\cms\model\Block;
use addons\cms\model\Channel;
use addons\cms\model\ModelsInfo;
use addons\cms\model\BuycarModel;
use addons\cms\model\Clue;
use addons\cms\model\QuotedPrice;
use addons\cms\model\User;
use addons\cms\model\Config as ConfigModel;
use app\common\model\Addon;
use think\Config;
use addons\third\model\Third;

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
//        $bannerList = [];
//        $list = Block::getBlockList(['name' => 'focus', 'row' => 5]);
//        foreach ($list as $index => $item) {
//            $bannerList[] = ['image' => cdnurl($item['image'], true), 'url' => '/', 'title' => $item['title']];
//        }
//
//        //首页Tab列表
//        $indexTabList = $newsTabList = $productTabList = [['id' => 0, 'title' => '全部']];
//        $channelList = Channel::where('status', 'normal')
//            ->where('type', 'in', ['list'])
//            ->field('id,parent_id,model_id,name,diyname')
//            ->order('weigh desc,id desc')
//            ->select();
//        foreach ($channelList as $index => $item) {
//            $data = ['id' => $item['id'], 'title' => $item['name']];
//            $indexTabList[] = $data;
//            if ($item['model_id'] == 1) {
//                $newsTabList[] = $data;
//            }
//            if ($item['model_id'] == 2) {
//                $productTabList[] = $data;
//            }
//        }

        //配置信息
        $upload = Config::get('upload');
        $upload['cdnurl'] = $upload['cdnurl'] ? $upload['cdnurl'] : cdnurl('', true);
        $upload['uploadurl'] = $upload['uploadurl'] == 'ajax/upload' ? cdnurl('/ajax/upload', true) : $upload['cdnurl'];
        $config = [
            'upload' => $upload
        ];

        $data = [
//            'bannerList'     => $bannerList,
//            'indexTabList'   => $indexTabList,
//            'newsTabList'    => $newsTabList,
//            'productTabList' => $productTabList,
            'config' => $config,
            'default_image' => ConfigModel::get(['name' => 'default_picture'])->value
        ];
        $this->success('', $data);

    }

    /**
     * 车辆详情
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function car_details()
    {
        $car_id = $this->request->post('car_id');

        $type = $this->request->post('type');

        $user_id = $this->request->post('user_id');

        if (!$car_id || !$type || !$user_id) {
            $this->error('缺少参数', 'error');
        }

        $modelName = null;
        switch ($type) {
            case 'sell':
                $modelName = new ModelsInfo();
                break;
            case 'buy':
                $modelName = new BuycarModel();
                break;
            case 'clue':
                $modelName = new Clue();
                break;
            default:
                $this->error('传入错误参数', 'error');
                break;
        }

        $car_id_key = $type == 'buy' ? 'buy_car_id' : 'models_info_id';

        //判断该用户该车辆是否报价
        $isOffer = QuotedPrice::get([$car_id_key => $car_id, 'type' => $type, 'user_ids' => $user_id]);

        $condition = 'id,models_name,car_licensetime,kilometres,guide_price,parkingposition,phone,store_id,user_id,store_description';

        if($type=='sell'){
            $condition= $condition.',modelsimages';
        }

        $detail = $modelName->field($condition)
            ->with(['brand'=>function ($q){
                $q->withField('id,name');
            }])
            ->find($car_id);

//        $detail = $modelName->find($car_id)->visible($condition)->toArray();
        $detail['modelsimages'] = empty($detail['modelsimages']) ? [self::$default_image] : explode(',', $detail['modelsimages']);

        $detail['kilometres'] = $detail['kilometres'] ? ($detail['kilometres'] / 10000) . '万公里' : null;
        $detail['guide_price'] = $detail['guide_price'] ? ($detail['guide_price'] / 10000) . '万' : null;
        $detail['car_licensetime'] = $detail['car_licensetime'] ? date('Y', $detail['car_licensetime']) : null;
        $detail['isOffer'] = $isOffer ? 1 : 0;
        $detail['user'] = User::get($user_id) ? User::get($user_id)->visible(['id', 'mobile'])->toArray() : ['id' => '', 'mobile' => ''];
        $this->success('请求成功', ['detail' => $detail]);
    }


}
