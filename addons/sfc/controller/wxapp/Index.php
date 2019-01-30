<?php

namespace addons\sfc\controller\wxapp;

use addons\sfc\model\Archives;
use addons\sfc\model\Block;
use addons\sfc\model\Channel;
use addons\sfc\model\Collection;
use addons\sfc\model\Distribution;
use addons\sfc\model\StoreUser;
use addons\sfc\model\CompanyStore;
use addons\sfc\model\StoreLevel;
use addons\sfc\model\Config;
use addons\sfc\model\ModelsInfo;
use addons\sfc\model\RideSharing;
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

        $store_id = CompanyStore::get(['user_id'=>$user_id])->id;

        $carInfo = json_decode($carInfo, true);

        $carInfo['store_id'] = $store_id;
//        $this->success($carInfo);

        $modelsInfo = new ModelsInfo();

        $modelsInfo->allowField(true)->save($carInfo) ? $this->success('添加成功', 'success') : $this->error('添加失败', 'error');
        $this->success($carInfo);
    }

    /**
     * 我想买车
     */
    public function wantBuyCar()
    {
        $arr = [
            'brand_name' => '标致',
            'models_name' => '标致408 2018款 1.8L 手动领先版',
            'parkingposition' => '成都',
            'psychology_price' => '20万元',
            'phone' => '18683787363',
            'kilometres' => '',
            'license_plate' => ''
        ];
        $carInfo = $this->request->post('carInfo');
        $user_id = $this->request->post('user_id');
//        $this->success(json_encode($arr));
        $carInfo = "{\"brand_name\":\"\\u6807\\u81f4\",\"models_name\":\"\\u6807\\u81f4408 2018\\u6b3e 1.8L \\u624b\\u52a8\\u9886\\u5148\\u7248\",\"parkingposition\":\"\\u6210\\u90fd\",\"psychology_price\":\"20\\u4e07\\u5143\",\"phone\":\"18683787363\",\"kilometres\":\"\",\"license_plate\":\"\"}";
        $store_id = $store_id = CompanyStore::get(['user_id'=>$user_id])->id;
        $carInfo = json_decode($carInfo, true);
        if ($store_id) {
            $carInfo['store_id'] = $store_id;
        }

        $buyModels = new BuycarModel();
        $buyModels->allowField(true)->save($carInfo) ? $this->success('添加成功', 'success') : $this->error('添加失败', 'error');
    }

    public function clue()
    {
        $arr = [
            'modelsimages'=>'/uploads/20181220/246477e60375d326878811de4e2544e0.png,/uploads/20181220/246477e60375d326878811de4e2544e0.png,/uploads/20181220/246477e60375d326878811de4e2544e0.png,/uploads/20181220/246477e60375d326878811de4e2544e0.png,/uploads/20181220/246477e60375d326878811de4e2544e0.png,/uploads/20181220/246477e60375d326878811de4e2544e0.png',
            'models_name'=>'大众捷达1.6L',
            'parkingposition'=>'成都',
            'licenseplate_location'=>'北京',
            'guide_price'=>'49万元',
            'factorytime'=>'2015-07-11',
            'car_licensetime'=>'2017-07-02',
            'kilometres'=>'81万公里',
            'emission_standard'=>'2.0T',
            'phone'=>'17360268104',
            'store_description'=>'not bad',
            'brand_name'=>'大众'
        ];
//$this->success(json_encode($arr));
        $carInfo = $this->request->post('carInfo');
        $user_id = $this->request->post('user_id');

        $carInfo = "{\"modelsimages\":\"\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png,\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png,\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png,\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png,\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png,\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png\",\"models_name\":\"\\u5927\\u4f17\\u6377\\u8fbe1.6L\",\"parkingposition\":\"\\u6210\\u90fd\",\"licenseplate_location\":\"\\u5317\\u4eac\",\"guide_price\":\"49\\u4e07\\u5143\",\"factorytime\":\"2015-07-11\",\"car_licensetime\":\"2017-07-02\",\"kilometres\":\"81\\u4e07\\u516c\\u91cc\",\"emission_standard\":\"2.0T\",\"phone\":\"17360268104\",\"store_description\":\"not bad\",\"brand_name\":\"\\u5927\\u4f17\"}";
        $store_id = $store_id = CompanyStore::get(['user_id'=>$user_id])->id;
        $carInfo = json_decode($carInfo, true);
        if ($store_id) {
            $carInfo['store_id'] = $store_id;
        }

        $buyModels = new BuycarModel();
        $buyModels->allowField(true)->save($carInfo) ? $this->success('添加成功', 'success') : $this->error('添加失败', 'error');
    }

    /**
     *司机发布顺风车接口
     */
    public function submit_tailwind()
    {
//        $arr = [
//            'phone' => '18683787363',
//            'starting_time' => '2019-02-19 10:56:09',
//            'starting_point' => '火车北站',
//            'destination' => '万年场',
//            'money' => '70',
//            'number_people' => 2,
//            'note' => '马上开了',
//            'type'=>'driver'
//        ];

        $user_id = $this->request->post('user_id');

        $info = $this->request->post('info');

        if (!$user_id || !$info) {
            $this->error('缺少参数，请求失败', 'error');
        }
//        $info = "{\"phone\":\"18683787363\",\"starting_time\":\"2019-02-19 10:56:09\",\"starting_point\":\"\\u706b\\u8f66\\u5317\\u7ad9\",\"destination\":\"\\u4e07\\u5e74\\u573a\",\"money\":\"70\",\"number_people\":2,\"note\":\"\\u9a6c\\u4e0a\\u5f00\\u4e86\",\"type\":\"passenger\"}";

//        $this->success(json_encode($arr));
        $info = json_decode($info, true);

        $info['user_id'] = $user_id;

        RideSharing::create($info)?$this->success('发布成功','success'):$this->error('发布失败','error');

    }

    /**
     * 顺风车列表接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function downwind()
    {
        $time = time();
        $type = $this->request->post('type');

        if (!$type) {
            $this->error('缺少参数，请求失败', 'error');
        }

        $field = $type=='driver'?',money':null;

        $takeCarList = RideSharing::field('id,starting_point,destination,starting_time,number_people,note,phone'.$field)
        ->order('createtime desc')->where('type',$type)->select();
        $overdueId = [];

        $takeCar = [];

        foreach ($takeCarList as $k=>$v){
            if($time>strtotime($v['starting_time'])){
                 $overdueId[] = $v['id'];
            }else{
                $takeCar[] = $v;
            }
        }

        if($overdueId){
            RideSharing::where('id','in',$overdueId)->update(['status'=>'hidden']);
        }

        $this->success('请求成功',['takeCarList'=>$takeCar]);
    }

}
