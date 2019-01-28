<?php

namespace addons\cms\controller\wxapp;

use addons\cms\model\Archives;
use addons\cms\model\Block;
use addons\cms\model\Channel;
use addons\cms\model\Collection;
use addons\cms\model\Distribution;
use addons\cms\model\StoreUser;
use addons\cms\model\CompanyStore;
use addons\cms\model\StoreLevel;
use addons\cms\model\Config;
use addons\cms\model\ModelsInfo;
use app\common\model\Addon;
use think\Cache;
use think\Db;


/**
 * 首页
 */
class Index extends Base
{

    protected $noNeedLogin = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 首页
     */
    public function index()
    {
        $bannerList = [];
        $list = Block::getBlockList(['name' => 'focus']);
        foreach ($list as $index => $item) {

            $bannerList[] = ['image' => cdnurl($item['image'], true), 'url' => '/', 'title' => $item['title']];
        }

        $this->success('请求成功', ['bannerList' => $bannerList]);

    }

    /**
     * 发布车源接口
     */
    public function uploadModels()
    {
//        $arr = [
//            'modelsimages'=>'/uploads/20181220/246477e60375d326878811de4e2544e0.png,/uploads/20181220/246477e60375d326878811de4e2544e0.png,/uploads/20181220/246477e60375d326878811de4e2544e0.png,/uploads/20181220/246477e60375d326878811de4e2544e0.png,/uploads/20181220/246477e60375d326878811de4e2544e0.png,/uploads/20181220/246477e60375d326878811de4e2544e0.png',
//            'models_name'=>'标致408 2018款 1.8L 手动领先版',
//            'parkingposition'=>'成都',
//            'license_plate'=>'北京',
//            'guide_price'=>'20万元',
//            'factorytime'=>'2013-11-01',
//            'car_licensetime'=>'2015-01-08',
//            'kilometres'=>'35万公里',
//            'emission_standard'=>'1.0T',
//            'phone'=>'18683787363',
//            'store_description'=>'很漂亮的车',
//            'brand_name'=>'标致'
//        ];
        $carInfo = $this->request->post('carInfo');

        $user_id = $this->request->post('user_id');
//$this->success(json_encode($arr));
        $carInfo = "{\"modelsimages\":\"\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png,\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png,\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png,\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png,\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png,\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png\",\"models_name\":\"\\u6807\\u81f4408 2018\\u6b3e 1.8L \\u624b\\u52a8\\u9886\\u5148\\u7248\",\"parkingposition\":\"\\u6210\\u90fd\",\"license_plate\":\"\\u5317\\u4eac\",\"guide_price\":\"20\\u4e07\\u5143\",\"factorytime\":\"2013-11-01\",\"car_licensetime\":\"2015-01-08\",\"kilometres\":\"35\\u4e07\\u516c\\u91cc\",\"emission_standard\":\"1.0T\",\"phone\":\"18683787363\",\"store_description\":\"\\u5f88\\u6f02\\u4eae\\u7684\\u8f66\",\"brand_name\":\"\\u6807\\u81f4\"}";

        $store_id = Db::name('store_user')
            ->alias('a')
            ->join('company_store b', 'a.id = b.store_user_id')
            ->where('a.id', $user_id)
            ->value('b.id');

        $carInfo = json_decode($carInfo, true);

        $carInfo['store_id'] = $store_id;

        $modelsInfo = new ModelsInfo();

        $modelsInfo->allowField(true)->save($carInfo) ? $this->success('添加成功', 'success') : $this->error('添加失败', 'error');
        $this->success($carInfo);
    }

}
