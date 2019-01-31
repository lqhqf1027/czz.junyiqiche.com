<?php

namespace addons\cms\controller\wxapp;

use addons\cms\model\Archives;
use addons\cms\model\Block;
use addons\cms\model\Channel;
use addons\cms\model\Clue;
use addons\cms\model\Collection;
use addons\cms\model\Distribution;
use addons\cms\model\StoreUser;
use addons\cms\model\CompanyStore;
use addons\cms\model\StoreLevel;
use addons\cms\model\Config;
use addons\cms\model\ModelsInfo;
use addons\cms\model\RideSharing;
use addons\cms\model\BuycarModel;
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

        //推荐店铺
        $storeList = CompanyStore::field('id,cities_name,main_camp')
            ->withCount(['modelsinfo'])->where('recommend', 1)->select();

//        $this->success($storeList);
        $modelsInfoList = $this->typeCar(1);
        $buycarModelList = $this->typeCar(2);
        $clueList = $this->typeCar(3);
//        $this->success($modelsInfoList);

        $this->success('请求成功', [
            'bannerList' => $bannerList,
            'storeList' => $storeList,
            'carModelList' => [
                'modelsInfoList' => $modelsInfoList,
                'buycarModelList' => $buycarModelList,
                'clueList' => $clueList
            ]

        ]);

    }

    /**
     * 不同总类车辆数据
     * @param $modelType
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function typeCar($modelType, $isBrand = 0)
    {
        $modelName = null;
        switch ($modelType) {
            case '1':
                $modelName = new ModelsInfo();
                break;
            case '2':
                $modelName = new BuycarModel();
                break;
            case '3':
                $modelName = new Clue();
                break;
        }

        $else = $modelType == 2 ? '' : ',modelsimages';

        if ($isBrand == 1) {
            $else .= ',brand_name';
        }

        $modelsInfoList = collection($modelName->field('id,models_name,guide_price,car_licensetime,kilometres,parkingposition' . $else)
            ->order('createtime desc')->select())->toArray();

        $default_image = self::$default_image;

        foreach ($modelsInfoList as $k => $v) {

            $modelsInfoList[$k]['modelsimages'] = !empty($v['modelsimages']) ? explode(';', $v['modelsimages'])[0] : $default_image;
            $modelsInfoList[$k]['kilometres'] = $v['kilometres'] ? ($v['kilometres'] / 10000) . '万公里' : null;
            $modelsInfoList[$k]['guide_price'] = $v['guide_price'] ? ($v['guide_price'] / 10000) . '万' : null;
            $modelsInfoList[$k]['car_licensetime'] = $v['car_licensetime'] ? date('Y', $v['car_licensetime']) : null;
        }

        return $modelsInfoList;
    }


    /**
     * 发布车源接口
     */
    public function uploadModels()
    {
        $arr = [
            'modelsimages' => '/uploads/20181220/246477e60375d326878811de4e2544e0.png;/uploads/20181220/246477e60375d326878811de4e2544e0.png;/uploads/20181220/246477e60375d326878811de4e2544e0.png;/uploads/20181220/246477e60375d326878811de4e2544e0.png;/uploads/20181220/246477e60375d326878811de4e2544e0.png;/uploads/20181220/246477e60375d326878811de4e2544e0.png',
            'models_name' => '标致408 2018款 1.8L 手动领先版',
            'parkingposition' => '成都',
            'license_plate' => '北京',
            'guide_price' => '20万元',
            'factorytime' => '2013-11-01',
            'car_licensetime' => '2015-01-08',
            'kilometres' => '35万公里',
            'emission_standard' => '1.0T',
            'phone' => '18683787363',
            'store_description' => '很漂亮的车',
            'brand_name' => '标致'
        ];
        $carInfo = $this->request->post('carInfo');

        $user_id = $this->request->post('user_id');

        if (!$user_id || !$carInfo) {
            $this->error('缺少参数，请求失败', 'error');
        }
//$this->success(json_encode($arr));
//        $carInfo = "{\"modelsimages\":\"\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png\",\"models_name\":\"\\u6807\\u81f4408 2018\\u6b3e 1.8L \\u624b\\u52a8\\u9886\\u5148\\u7248\",\"parkingposition\":\"\\u6210\\u90fd\",\"license_plate\":\"\\u5317\\u4eac\",\"guide_price\":\"20\\u4e07\\u5143\",\"factorytime\":\"2013-11-01\",\"car_licensetime\":\"2015-01-08\",\"kilometres\":\"35\\u4e07\\u516c\\u91cc\",\"emission_standard\":\"1.0T\",\"phone\":\"18683787363\",\"store_description\":\"\\u5f88\\u6f02\\u4eae\\u7684\\u8f66\",\"brand_name\":\"\\u6807\\u81f4\"}";

        $store_id = CompanyStore::get(['user_id' => $user_id])->id;
        $carInfo = json_decode($carInfo, true);

        $carInfo['store_id'] = $store_id;

//        $this->success($carInfo);

        $modelsInfo = new ModelsInfo();

        $modelsInfo->allowField(true)->save($carInfo) ? $this->success('添加成功', 'success') : $this->error('添加失败', 'error');
    }

    /**
     * 我想买车接口
     */
    public function wantBuyCar()
    {
        $arr = [
            'brand_name' => '标致',
            'models_name' => '标致408 2018款 1.8L 手动领先版',
            'parkingposition' => '成都',
            'guide_price' => '20万元',
            'phone' => '18683787363',
            'kilometres' => '',
            'emission_standard' => '2.5T',
            'license_plate' => '',
            'store_description' => '',
            'factorytime' => '2018-06-05',
            'car_licensetime' => '2014-01-07'

        ];
        $carInfo = $this->request->post('carInfo');
        $user_id = $this->request->post('user_id');

        if (!$user_id || !$carInfo) {
            $this->error('缺少参数，请求失败', 'error');
        }
//        $this->success(json_encode($arr));
//        $carInfo = "{\"brand_name\":\"\\u6807\\u81f4\",\"models_name\":\"\\u6807\\u81f4408 2018\\u6b3e 1.8L \\u624b\\u52a8\\u9886\\u5148\\u7248\",\"parkingposition\":\"\\u6210\\u90fd\",\"guide_price\":\"20\\u4e07\\u5143\",\"phone\":\"18683787363\",\"kilometres\":\"\",\"emission_standard\":\"2.5T\",\"license_plate\":\"\",\"store_description\":\"\",\"factorytime\":\"2018-06-05\",\"car_licensetime\":\"2014-01-07\"}";
        $store_id = $store_id = CompanyStore::get(['user_id' => $user_id])->id;
        $carInfo = json_decode($carInfo, true);
        if ($store_id) {
            $carInfo['store_id'] = $store_id;
        }
        $carInfo['user_id'] = $user_id;
        $buyModels = new BuycarModel();
        $buyModels->allowField(true)->save($carInfo) ? $this->success('添加成功', 'success') : $this->error('添加失败', 'error');
    }

    /**
     * 我有线索
     * @throws \think\exception\DbException
     */
    public function clue()
    {
        $arr = [
            'brand_name' => '标致',
            'phone' => '18683787363',
            'parkingposition' => '成都',
            'modelsimages' => '/uploads/20181220/246477e60375d326878811de4e2544e0.png;/uploads/20181220/246477e60375d326878811de4e2544e0.png;/uploads/20181220/246477e60375d326878811de4e2544e0.png;/uploads/20181220/246477e60375d326878811de4e2544e0.png;/uploads/20181220/246477e60375d326878811de4e2544e0.png;/uploads/20181220/246477e60375d326878811de4e2544e0.png',
            'models_name' => '标致408 2018款 1.8L 手动领先版',
            'license_plate' => '',
            'factorytime' => '',
            'car_licensetime' => '2015-01-08',
            'kilometres' => '35万公里',
            'emission_standard' => '1.0T',
            'store_description' => ''

        ];
//$this->success(json_encode($arr));
        $carInfo = $this->request->post('carInfo');
        $user_id = $this->request->post('user_id');

        if (!$user_id || !$carInfo) {
            $this->error('缺少参数，请求失败', 'error');
        }
//        $carInfo = "{\"brand_name\":\"\\u6807\\u81f4\",\"phone\":\"18683787363\",\"parkingposition\":\"\\u6210\\u90fd\",\"modelsimages\":\"\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png\",\"models_name\":\"\\u6807\\u81f4408 2018\\u6b3e 1.8L \\u624b\\u52a8\\u9886\\u5148\\u7248\",\"license_plate\":\"\",\"factorytime\":\"\",\"car_licensetime\":\"2015-01-08\",\"kilometres\":\"35\\u4e07\\u516c\\u91cc\",\"emission_standard\":\"1.0T\",\"store_description\":\"\"}";
        $store_id = $store_id = CompanyStore::get(['user_id' => $user_id])->id;
        $carInfo = json_decode($carInfo, true);
        if ($store_id) {
            $carInfo['store_id'] = $store_id;
        }
        $carInfo['user_id'] = $user_id;
//        $this->success($carInfo);
        $clue = new Clue();
        $clue->allowField(true)->save($carInfo) ? $this->success('添加成功', 'success') : $this->error('添加失败', 'error');
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

        RideSharing::create($info) ? $this->success('发布成功', 'success') : $this->error('发布失败', 'error');

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

        $field = $type == 'driver' ? ',money' : null;

        $takeCarList = RideSharing::field('id,starting_point,destination,starting_time,number_people,note,phone' . $field)
            ->order('createtime desc')->where('type', $type)->select();
        $overdueId = [];

        $takeCar = [];

        foreach ($takeCarList as $k => $v) {
            if ($time > strtotime($v['starting_time'])) {
                $overdueId[] = $v['id'];
            } else {
                $takeCar[] = $v;
            }
        }

        if ($overdueId) {
            RideSharing::where('id', 'in', $overdueId)->update(['status' => 'hidden']);
        }

        $this->success('请求成功', ['takeCarList' => $takeCar]);
    }

}
