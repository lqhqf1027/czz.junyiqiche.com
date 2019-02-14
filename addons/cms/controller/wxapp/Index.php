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
use addons\cms\model\Config as ConfigModel;
use addons\cms\model\ModelsInfo;
use addons\cms\model\RideSharing;
use addons\cms\model\BuycarModel;
use addons\cms\model\BrandCate;
use addons\cms\model\User;
use app\common\model\Addon;
use think\Cache;
use think\Db;
use GuzzleHttp\Client;


/**
 * 首页
 */
class Index extends Base
{

    protected $noNeedLogin = '*';

    /**
     * 云之讯短信发送模板
     * @var array
     */
    protected static $Ucpass = [
        'accountsid' => 'ffc7d537e8eb86b6ffa3fab06c77fc02',
        'token' => '894cfaaf869767dce526a6eba54ffe52',
        'appid' => '33553da944fb487089dadb16a37c53cc',
        'templateid' => '430761',
    ];

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

        $share = collection(ConfigModel::all(function ($q){
            $q->where('group','shares')->field('name,value');
        }))->toArray();

//        pr($share);die();



        $this->success('请求成功', [
            'bannerList' => $bannerList,
            'storeList' => $storeList,
            'carModelList' => [
                'modelsInfoList' => $modelsInfoList,
                'buycarModelList' => $buycarModelList,
                'clueList' => $clueList
            ],
            'default_image' => ConfigModel::get(['name'=>'default_picture'])->value,
            'share'=>[
                $share[0]['name'] =>  $share[0]['value'],
                $share[1]['name'] =>  $share[1]['value'],
                $share[2]['name'] =>  $share[2]['value'],
                $share[3]['name'] =>  $share[3]['value']
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
     * 获取车辆品牌
     */
    public function brand()
    {
        $brandList = BrandCate::field('id,name,bfirstletter,thumb')->select();
        $check = [];

        foreach ($brandList as $k => $v) {

            if (in_array($v['bfirstletter'], $check)) {
               
                continue;
            } else {
                $check[] = $v['bfirstletter'];
            }

        }

        sort($check);

        foreach ($check as $k => $v) {

            foreach ($brandList as $key => $value) {

                if ($v == $value['bfirstletter']) {
                    unset($check[$k]);
                    $check[$v][] = [
                        'id' => $value['id'],
                        'name' => $value['name']
                    ];
                }
    
            }

        }
        //缓冲品牌
        Cache::set('brandCate', $check);

        return Cache::get('brandCate');

    }

    /**
     * 发布车源接口中的车辆品牌
     */
    public function brandCates()
    {
        $user_id = $this->request->post('user_id');

        if (!$user_id) {
            $this->error('缺少参数,请求失败', 'error');
        }
        //用户信息
        $userData = User::where('id', $user_id)->find();

        //得到所有的品牌列表
        if (Cache::get('brandCate')) {
            $brand = Cache::get('brandCate');
        } else {
            Cache::set('brandCate', $this->brand());
            $brand = Cache::get('brandCate');
        }

        $this->success('请求成功', ['brand' => $brand, 'mobile' => $userData['mobile']]);
    }


    /**
     * 有默认手机号，点击提交发布车源按钮后，弹框接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function clickAppointment()
    {
        //必传
        $user_id = $this->request->post('user_id');

        //如果是走的手机号码验证 必须传递 mobile  和code参数
        $mobile = $this->request->post('mobile');
        $code = $this->request->post('code');


        //如果是手机号码授权  必须传递 iv 、encryptedData 、 sessionKey参数
        $iv = $this->request->post('iv');
        $encryptedData = $this->request->post('encryptedData');
        $sessionKey = $this->request->post('sessionKey');
        //解密手机号
        if ($sessionKey && $iv && $sessionKey) {
            $pc = new WxBizDataCrypt('wxf789595e37da2838', $sessionKey);
            $result = $pc->decryptData($encryptedData, $iv, $data);
            if ($result == 0) {
                $mobile = json_decode($data, true)['phoneNumber'];
            } else {
                $this->error('手机号解密失败', json_decode($data, true));
            }
        }
        if (!$user_id) {
            $this->error('缺少参数,请求失败', 'error');
        }
        if ($code) {
            $userInfo = Db::name('cms_login_info')
                ->where(['user_id' => $user_id, 'login_state' => 0])->find();
            if (!$userInfo || $code != $userInfo['login_code']) {
                $this->error('验证码输入错误');
            }
        }

        //如果是手机授权，手机号码更新到用户表
        if ($mobile) {
            User::where('id', $user_id)->update([
                'mobile' => $mobile
            ]);
        } else {
            $mobile = User::get($user_id)->mobile;
        }

        $this->success('发布成功', 'success');
    }

    /**
     *  发送验证码
     * @return mixed
     */
    public function sendMessage()
    {
        $mobile = $this->request->post('mobile');
        $user_id = $this->request->post('user_id');
        if (!$mobile || !$user_id) $this->error('参数缺失或格式错误');
        if (!checkPhoneNumberValidate($mobile)) $this->error('手机号格式错误', $mobile);
        $authnum = '';
        //随机生成四位数验证码
        $list = explode(",", "0,1,2,3,4,5,6,7,8,9");
        for ($i = 0; $i < 4; $i++) {
            $randnum = rand(0, 9);
            $authnum .= $list[$randnum];
        }

        $url = 'http://open.ucpaas.com/ol/sms/sendsms';
        $client = new Client();
        $response = $client->request('POST', $url, [
            'json' => [
                'sid' => self::$Ucpass['accountsid'],
                'token' => self::$Ucpass['token'],
                'appid' => self::$Ucpass['appid'],
                'templateid' => self::$Ucpass['templateid'],
                'param' => $authnum,
                'mobile' => $mobile,
                'uid' => $user_id
            ]
        ]);
        if ($response) {
            $result = json_decode($response->getBody(), true);
            $num = '';
            if ($result['code'] == '000000') {
                //查询当前手机号，如果存在更新他的的请求次数与 请求时间
                $getPhone = Db::name('cms_login_info')->where(['login_phone' => $mobile])->find();
                if ($getPhone) {
                    $num = $getPhone['login_num'];
                    ++$num;
                    Db::name('cms_login_info')->update([
                        'login_time' => strtotime($result['create_date']),
                        'login_code' => $authnum,
                        'login_num' => $num,
                        'login_phone' => $mobile,
                        'id' => $getPhone['id'],
                        'login_state' => 0,
                        'user_id' => $user_id
                    ]) ? $this->success('发送成功') : $this->error('发送失败');

                } else {
                    //否则新增当前用户到登陆表
                    Db::name('cms_login_info')->insert([
                        'login_time' => strtotime($result['create_date']),
                        'login_code' => $authnum,
                        'login_num' => 1,
                        'login_phone' => $mobile,
                        'login_state' => 0,
                        'user_id' => $user_id
                    ]) ? $this->success('发送成功') : $this->error('发送失败');
                }
            } else {
                $this->error($result['msg'], $result);
            }
        } else {
            $err = json_decode($response->getBody(), true);
            $this->error($err['msg'], $err);
        }


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
//        $this->success(json_decode($this->request->post('carInfo'),true)['parkingposition']);
        $carInfo = $this->request->post('carInfo');
        $user_id = $this->request->post('user_id');
        if (!$user_id || !$carInfo) {
            $this->error('缺少参数，请求失败', 'error');
        }
//        $carInfo = "{\"brand_name\":\"\\u6807\\u81f4\",\"phone\":\"18683787363\",\"parkingposition\":\"\\u6210\\u90fd\",\"modelsimages\":\"\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png;\\/uploads\\/20181220\\/246477e60375d326878811de4e2544e0.png\",\"models_name\":\"\\u6807\\u81f4408 2018\\u6b3e 1.8L \\u624b\\u52a8\\u9886\\u5148\\u7248\",\"license_plate\":\"\",\"factorytime\":\"\",\"car_licensetime\":\"2015-01-08\",\"kilometres\":\"35\\u4e07\\u516c\\u91cc\",\"emission_standard\":\"1.0T\",\"store_description\":\"\"}";

        $store_id = CompanyStore::get(['user_id' => $user_id])->id;
        $carInfo = json_decode($carInfo, true);
        if ($store_id) {
            $carInfo['store_id'] = $store_id;
        }
        $carInfo['user_id'] = $user_id;
//$this->success($carInfo);
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
