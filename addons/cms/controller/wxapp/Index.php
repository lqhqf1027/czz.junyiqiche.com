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
use addons\cms\model\Brand;
use addons\cms\model\User;
use app\common\model\Addon;
use think\Cache;
use think\Db;
use GuzzleHttp\Client;
use think\Config;
use fast\Random;

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
        $bannerList = $modelsInfoList = $buycarModelList = [];
        $list = Block::getBlockList(['name' => 'focus']);
        foreach ($list as $index => $item) {

            $bannerList[] = ['image' => cdnurl($item['image'], true), 'url' => '/', 'title' => $item['title']];
        }

        //推荐店铺
        $storeList = CompanyStore::field('id,store_name,cities_name,main_camp')
            ->withCount(['modelsinfo'])->where('recommend', 1)->select();

        Cache::rm('CAR_LIST');
        if (!Cache::get('CAR_LIST')) {

            Cache::set('CAR_LIST', Carselect::getCarCache());
        }

        $dataList = Cache::get('CAR_LIST')['carList'];

        foreach ($dataList as $v) {
            if ($v['type'] == 'sell') {
                $modelsInfoList[] = $v;
            } else {
                $buycarModelList[] = $v;
            }

        }
        $share = collection(ConfigModel::all(function ($q) {
            $q->where('group', 'shares')->field('name,value');
        }))->toArray();

        $this->success('请求成功', [
            'bannerList' => $bannerList,
            'storeList' => $storeList,
            'carModelList' => [
                'modelsInfoList' => $modelsInfoList,
                'buycarModelList' => $buycarModelList,
//                'clueList' => $clueList
            ],
            'default_image' => ConfigModel::get(['name' => 'default_picture'])->value,
            'share' => [
                $share[0]['name'] => $share[0]['value'],
                $share[1]['name'] => $share[1]['value'],
                $share[2]['name'] => $share[2]['value'],
                $share[3]['name'] => $share[3]['value']
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
    public static function typeCar($modelType, $is_transformation = 0, $where = null, $field = null)
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

        $fields = $field ? $field : 'id,models_name,guide_price,car_licensetime,kilometres,parkingposition,browse_volume,createtime,store_description' . $else;


        $modelsInfoList = collection($modelName->field($fields)
            ->with(['brand' => function ($q) {
                $q->withField('id,name,bfirstletter');
            }])
            ->where($where)->order('createtime desc')->select())->toArray();

        $default_image = self::$default_image;

        foreach ($modelsInfoList as $k => $v) {

            if (!$is_transformation) {
                $modelsInfoList[$k]['kilometres'] = $v['kilometres'] ? ($v['kilometres'] / 10000) . '万公里' : null;
                $modelsInfoList[$k]['guide_price'] = $v['guide_price'] ? ($v['guide_price'] / 10000) . '万' : null;
            }
            if ($field == null) {
                $modelsInfoList[$k]['modelsimages'] = !empty($v['modelsimages']) ? explode(',', $v['modelsimages'])[0] : $default_image;

                $modelsInfoList[$k]['car_licensetime'] = $v['car_licensetime'] ? date('Y', $v['car_licensetime']) : null;
            }
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
     * 获取车辆品牌和车系
     */
    public function getBrand()
    {
        $brandList = Brand::where('pid', 0)->field('id,name,brand_initials,brand_logoimage')->select();
        $seriesList = Brand::where('pid', 'NEQ', 0)->field('id,name,pid')->select();
        $check = [];

        foreach ($brandList as $k => $v) {

            if (in_array($v['brand_initials'], $check)) {

                continue;
            } else {
                $check[] = $v['brand_initials'];
            }

        }

        sort($check);

        foreach ($check as $k => $v) {

            foreach ($brandList as $key => $value) {

                if ($v == $value['brand_initials']) {
                    unset($check[$k]);
                    $check[$v]['brand'] = [
                        'id' => $value['id'],
                        'name' => $value['name']
                    ];
                    foreach ($series as $kk => $vv) {
                        if ($vv['pid'] == $value['id']) {
                            $check[$v]['brand'][] = [
                                'id' => $value['id'],
                                'name' => $value['name']
                            ];
                        }
                    }
                }

            }

        }

        $this->success('请求成功', ['brand' => $check]);

    }


    /**
     * 有默认手机号，点击提交发布车源按钮后，弹框验证接口
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


        // //如果是手机号码授权  必须传递 iv 、encryptedData 、 sessionKey参数
        // $iv = $this->request->post('iv');
        // $encryptedData = $this->request->post('encryptedData');
        // $sessionKey = $this->request->post('sessionKey');
        // //解密手机号
        // if ($sessionKey && $iv && $sessionKey) {
        //     $pc = new WxBizDataCrypt('wxf789595e37da2838', $sessionKey);
        //     $result = $pc->decryptData($encryptedData, $iv, $data);
        //     if ($result == 0) {
        //         $mobile = json_decode($data, true)['phoneNumber'];
        //     } else {
        //         $this->error('手机号解密失败', json_decode($data, true));
        //     }
        // }
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

        User::update([
            'id' => $user_id,
            'mobile' => $mobile
        ]);

        // //如果是手机授权，手机号码更新到用户表
        // if ($mobile) {
        //     User::where('id', $user_id)->update([
        //         'mobile' => $mobile
        //     ]);
        // } else {
        //     $mobile = User::get($user_id)->mobile;
        // }

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
     * 发布车源接口  ->卖车
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
        $carInfo = $this->request->post('carInfo/a');
        $user_id = $this->request->post('user_id');
        $modelsimages = $this->request->post('modelsimages');
        if (!$user_id || !$carInfo) {
            $this->error('缺少参数，请求失败', 'error');
        }
        $store_id = CompanyStore::get(['user_id' => $user_id])->id;

        $carInfo['store_id'] = $store_id;
        $carInfo['user_id'] = $user_id;
        $modelsInfo = new ModelsInfo();
        $modelsInfo->allowField(true)->save($carInfo) ? $this->success('添加成功', 'success') : $this->error('添加失败', 'error');
    }

    /**
     * 上传文件
     * @ApiMethod (POST)
     * @param File $file 文件流
     */
    public function upModelImg()
    {
        $file = $this->request->file('file');
        if (empty($file)) {
            $this->error(__('No file upload or server upload limit exceeded'));
        }

        //判断是否已经存在附件
        $sha1 = $file->hash();

        $upload = Config::get('upload');

        preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int)$upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
        $fileInfo = $file->getInfo();
        $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        $suffix = $suffix ? $suffix : 'file';

        $mimetypeArr = explode(',', strtolower($upload['mimetype']));
        $typeArr = explode('/', $fileInfo['type']);

        //验证文件后缀
        if ($upload['mimetype'] !== '*' &&
            (
                !in_array($suffix, $mimetypeArr)
                || (stripos($typeArr[0] . '/', $upload['mimetype']) !== false && (!in_array($fileInfo['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr)))
            )
        ) {
            $this->error(__('Uploaded file format is limited'));
        }
        $replaceArr = [
            '{year}' => date("Y"),
            '{mon}' => date("m"),
            '{day}' => date("d"),
            '{hour}' => date("H"),
            '{min}' => date("i"),
            '{sec}' => date("s"),
            '{random}' => Random::alnum(16),
            '{random32}' => Random::alnum(32),
            '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
            '{suffix}' => $suffix,
            '{.suffix}' => $suffix ? '.' . $suffix : '',
            '{filemd5}' => md5_file($fileInfo['tmp_name']),
        ];
        $savekey = $upload['savekey'];
        $savekey = str_replace(array_keys($replaceArr), array_values($replaceArr), $savekey);

        $uploadDir = substr($savekey, 0, strripos($savekey, '/') + 1);
        $fileName = substr($savekey, strripos($savekey, '/') + 1);
        //
        $splInfo = $file->validate(['size' => $size])->move(ROOT_PATH . '/public' . $uploadDir, $fileName);
        if ($splInfo) {
            $imagewidth = $imageheight = 0;
            if (in_array($suffix, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'])) {
                $imgInfo = getimagesize($splInfo->getPathname());
                $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
                $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
            }
            $params = array(
                'admin_id' => 0,
                'user_id' => (int)$this->auth->id,
                'filesize' => $fileInfo['size'],
                'imagewidth' => $imagewidth,
                'imageheight' => $imageheight,
                'imagetype' => $suffix,
                'imageframes' => 0,
                'mimetype' => $fileInfo['type'],
                'url' => $uploadDir . $splInfo->getSaveName(),
                'uploadtime' => time(),
                'storage' => 'local',
                'sha1' => $sha1,
            );
//            $attachment = model("attachment");
//            $attachment->data(array_filter($params));
//            $attachment->save();
//            \think\Hook::listen("upload_after", $attachment);
            $this->success(__('Upload successful'), [
                'url' => $uploadDir . $splInfo->getSaveName()
            ]);
        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
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
        $carInfo = $this->request->post('carInfo/a');
        $user_id = $this->request->post('user_id');

        if (!$user_id || !$carInfo) {
            $this->error('缺少参数，请求失败', 'error');
        }
//        $this->success(json_encode($arr));
//        $carInfo = "{\"brand_name\":\"\\u6807\\u81f4\",\"models_name\":\"\\u6807\\u81f4408 2018\\u6b3e 1.8L \\u624b\\u52a8\\u9886\\u5148\\u7248\",\"parkingposition\":\"\\u6210\\u90fd\",\"guide_price\":\"20\\u4e07\\u5143\",\"phone\":\"18683787363\",\"kilometres\":\"\",\"emission_standard\":\"2.5T\",\"license_plate\":\"\",\"store_description\":\"\",\"factorytime\":\"2018-06-05\",\"car_licensetime\":\"2014-01-07\"}";
        $store_id = CompanyStore::get(['user_id' => $user_id])->id;
        if ($store_id) {
            $carInfo['store_id'] = $store_id;
        }
        $carInfo['user_id'] = $user_id;
        $buyModels = new BuycarModel();
        return $buyModels->allowField(true)->save($carInfo) ? $this->success('添加成功', 'success') : $this->error('添加失败', 'error');
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

    public function search()
    {
        $query = $this->request->post('query_criteria');

        $brand_id = BrandCate::where('name', 'like', '%' . $query . '%')->column('id');


        if ($brand_id) {
            $modelInfoList = self::typeCar(1, 1, ['brand_id' => ['in', $brand_id]], 'id,models_name');
            $buyCarList = self::typeCar(2, 1, ['brand_id' => ['in', $brand_id]], 'id,models_name');
        } else {
            $modelInfoList = self::typeCar(1, 1, ['models_name' => ['like', '%' . $query . '%']], 'id,models_name');
            $buyCarList = self::typeCar(2, 1, ['models_name' => ['like', '%' . $query . '%']], 'id,models_name');
        }

//        $all = array_merge($modelInfoList,$buyCarList);

//        $check =$real = [];
//        foreach ($all as $k=>$v){
//            if(!in_array($v['brand']['id'],$check)){
//                   $check[] = $v['brand']['id'];
//                   $real[] = ['id'=>$v['brand']['id'],'name'=>$v['brand']['name'],'carList'=>[['id'=>$v['id'],'models_name'=>$v['models_name']]]];
//            }else{
//                foreach ($real as $key=>$value){
//                       if($v['brand']['id']==$value['id']){
//                           $real[$key]['carList'][] = ['id'=>$v['id'],'models_name'=>$v['models_name']];
//                       }
//                }
//            }
//        }

        if($modelInfoList){
            $modelInfoList = $this->getCarList($modelInfoList);
        }

        if($buyCarList){
            $buyCarList = $this->getCarList($buyCarList);
        }

        $this->success('请求成功',['sell'=>$modelInfoList,'buy'=>$buyCarList]);
    }

    public function getCarList($arr)
    {
        $check =$real = [];
        foreach ($arr as $k=>$v){
            if(!in_array($v['brand']['id'],$check)){
                $check[] = $v['brand']['id'];
                $real[] = ['id'=>$v['brand']['id'],'name'=>$v['brand']['name'],'carList'=>[['models_name'=>$v['models_name']]]];
            }else{
                foreach ($real as $key=>$value){
                    if($v['brand']['id']==$value['id']){
                        foreach ($real[$key]['carList'] as $kk=>$vv){
                            
                        }
                        $real[$key]['carList'][] = ['models_name'=>$v['models_name']];
                    }
                }
            }
        }

        return $real;
    }

}
