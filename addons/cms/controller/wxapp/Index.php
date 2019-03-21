<?php

namespace addons\cms\controller\wxapp;

use addons\cms\model\AutomotiveInformation;
use addons\cms\model\Block;
use addons\cms\model\Brand;
use addons\cms\model\BrandCate;
use addons\cms\model\BuycarModel;
use addons\cms\model\Clue;
use addons\cms\model\CompanyStore;
use addons\cms\model\Config as ConfigModel;
use addons\cms\model\ModelsInfo;
use addons\cms\model\User;
use addons\cms\model\Message;
use addons\cms\model\InformationCategories;
use addons\cms\model\PayOrder;
use app\common\model\Addon;
use fast\Random;
use GuzzleHttp\Client;
use think\Cache;
use think\Config;
use think\Db;
use think\Exception;

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
        $user_id = $this->request->post('user_id');
        $sell_info = [
            'msg' => '',
            'code' => 0,
        ];

        try {
            $bannerList = $modelsInfoList = $buycarModelList = [];
            $list = Block::getBlockList(['name' => 'focus']);
            foreach ($list as $index => $item) {

                $bannerList[] = ['image' => cdnurl($item['image'], true), 'url' => '/', 'title' => $item['title']];
            }

            $res = CompanyStore::field('id,level_id,auditstatus')
                ->with(['storelevel' => function ($q) {
                    $q->withField('id,max_release_number');
                }])->where([
                    'user_id' => $user_id,
                ])->find();

            if (empty($res)) {
                $sell_info['msg'] = '您暂未认证！';
                $sell_info['code'] = 1;
            } else {
                if ($res['auditstatus'] == 'paid_the_money') {
                    if ($res['storelevel']['max_release_number'] != -1) {
                        $my_release_number = ModelsInfo::where([
                            'user_id' => $user_id,
                            'shelfismenu' => 1
                        ])->count('id');

                        if ($my_release_number >= $res['storelevel']['max_release_number']) {
                            $sell_info['msg'] = '发布卖车已达到限制' . $res['storelevel']['max_release_number'] . '次，想要发布更多请升级店铺';
                            $sell_info['code'] = 2;
                        }
                    }
                } else {
                    $msg = $code = '';
                    switch ($res['auditstatus']) {
                        case 'wait_the_review':
                            $msg = '您的店铺正在等待审核';
                            $code = 3;
                            break;
                        case 'in_the_review':
                            $msg = '您的店铺正在审核中';
                            $code = 3;
                            break;
                        case 'pass_the_audit':
                            $msg = '您的店铺等待付款，付款后即可发布';
                            $code = 4;
                            break;
                        case 'audit_failed':
                            $msg = '审核失败';
                            $code = 5;
                            break;
                    }
                    $sell_info['msg'] = $msg;
                    $sell_info['code'] = $code;

                }

            }

            //推荐店铺
            $storeList = CompanyStore::field('id,store_name,cities_name,main_camp')
                ->withCount(['modelsinfo'])->where('recommend', 1)->select();

            $dataList = Carselect::getCarCache()['carList'];

            foreach ($dataList as $v) {
                if ($v['type'] == 'sell') {
                    $modelsInfoList[] = $v;
                } else {
                    $buycarModelList[] = $v;
                }

            }

            unset($dataList, $res);

            array_multisort(array_column($modelsInfoList, 'browse_volume'), SORT_DESC, $modelsInfoList);
            array_multisort(array_column($buycarModelList, 'browse_volume'), SORT_DESC, $buycarModelList);

            $modelsInfoList = array_slice($modelsInfoList, 0, 15);
            $buycarModelList = array_slice($buycarModelList, 0, 15);

            $unread = 0;
            $message_list = Message::column('use_id');

            foreach ($message_list as $k => $v) {
                if (strpos($v, ',' . $user_id . ',') === false) {
                    $unread = 1;
                    break;
                }
            }

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }


        $this->success('请求成功', [
            'bannerList' => $bannerList,
            'storeList' => $storeList,
            'carModelList' => [
                'modelsInfoList' => $modelsInfoList,
                'buycarModelList' => $buycarModelList,
            ],
            'default_image' => ConfigModel::get(['name' => 'default_picture'])->value,
            'share' => self::get_share(),
            'sell_car_condition' => $sell_info,
//            'buy_car_condition' =>$buy_info,
            'unread' => $unread
        ]);

    }

    /**
     * 获取分享配置
     * @return array
     * @throws \think\exception\DbException
     */
    public static function get_share()
    {
        $share = collection(ConfigModel::all(function ($q) {
            $q->where('group', 'shares')->field('name,value');
        }))->toArray();

        return [
            $share[0]['name'] => $share[0]['value'],
            $share[1]['name'] => $share[1]['value'],
            $share[2]['name'] => $share[2]['value'],
            $share[3]['name'] => $share[3]['value']
        ];
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
        $tables = '';
        switch ($modelType) {
            case '1':
                $modelName = new ModelsInfo();
                $tables = 'models_info';
                break;
            case '2':
                $modelName = new BuycarModel();
                $tables = 'buycar_model';
                break;
            case '3':
                $modelName = new Clue();
                break;
        }

        $else = $modelType == 2 ? '' : ',modelsimages';

        $fields = $field ? $field : 'id,models_name,guide_price,car_licensetime,kilometres,parkingposition,browse_volume,createtime,store_description,factorytime' . $else;

        $modelsInfoList = collection($modelName->field($fields)
            ->with(['brand' => function ($q) {
                $q->withField('id,name,brand_initials,brand_default_images');
            }])
            ->where($where)->order($tables . '.createtime desc')->select())->toArray();

        $default_image = self::$default_image;

        foreach ($modelsInfoList as $k => $v) {
            if (!$is_transformation) {
                $modelsInfoList[$k]['kilometres'] = $v['kilometres'] ? floatval(round($v['kilometres'] / 10000, 2)) . '万公里' : null;
                $modelsInfoList[$k]['guide_price'] = $v['guide_price'] ? floatval(round($v['guide_price'] / 10000, 2)) . '万' : null;
            }
            if ($field == null) {
                $modelsInfoList[$k]['modelsimages'] = !empty($v['modelsimages']) ? explode(',', $v['modelsimages'])[0] : $default_image;

                $modelsInfoList[$k]['car_licensetime'] = $v['car_licensetime'] ? date('Y-m', $v['car_licensetime']) : null;
            }

//            $modelsInfoList[$k]['factorytime'] = $v['factorytime'] ? date('Y', $v['factorytime']) : '';

        }

        return $modelsInfoList;
    }

    /**
     * 获取车辆品牌
     */
    public static function brand()
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

        $transmission = ConfigModel::get(['name' => 'transmission'])->visible(['name', 'value']);

        $transmission['value'] = json_decode($transmission['value'], true);

        $data = [
            'mobile' => $userData['mobile'],
            $transmission['name'] => $transmission['value']
        ];

        $this->success('请求成功', $data);
    }

    /**
     * 获取车辆品牌和车系
     */
    public function getBrand()
    {
        $brandList = Brand::where('pid', 0)->field('id,name,brand_initials,brand_logoimage')->select();
        $check = [];
        $series = [];

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

                if ($value['brand_initials'] == $v) {
                    unset($check[$k]);

                    $seriesList = Brand::where('pid', $value['id'])->field('id,name,pid')->select();

                    foreach ($seriesList as $kk => $vv) {

                        $series[] = [
                            'id' => $vv['id'],
                            'name' => $vv['name'],
                            'pid' => $vv['pid']
                        ];

                    }

                    $check[$v]['brand'][] = [
                        'id' => $value['id'],
                        'name' => $value['name'],
                        'series' => $series
                    ];

                    $series = [];

                }

            }

        }

        //缓冲品牌
        Cache::set('brandCatesList', $check);

        return Cache::get('brandCatesList');
    }

    /**
     * 发布车源接口中的车辆品牌
     */
    public function getBrandCates()
    {
        $user_id = $this->request->post('user_id');

        if (!$user_id) {
            $this->error('缺少参数,请求失败', 'error');
        }
        //用户信息
        $userData = User::where('id', $user_id)->find();

        //得到所有的品牌列表
        if (Cache::get('brandCatesList')) {
            $brand = Cache::get('brandCatesList');
        } else {
            Cache::set('brandCatesList', $this->getBrand());
            $brand = Cache::get('brandCatesList');
        }

        $this->success('请求成功', ['brandList' => $brand, 'mobile' => $userData['mobile']]);
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

        if (!$user_id || !$code || !checkPhoneNumberValidate($mobile)) {
            $this->error('缺少参数或格式错误,请求失败', 'error');
        }

        $userInfo = Db::name('cms_login_info')
            ->where(['user_id' => $user_id, 'login_state' => 0])->find();
        if (!$userInfo || $code != $userInfo['login_code']) {
            $this->error('验证码输入错误');
        }

        User::where('id', $user_id)->setField('mobile', $mobile);

        $this->success('验证成功', 'success');
    }

    /**
     *  发送验证码
     * @return mixed
     */
    public function sendMessage()
    {
        $mobile = $this->request->post('mobile');
        $user_id = $this->request->post('user_id');

        if (!$user_id || !checkPhoneNumberValidate($mobile)) {
            $this->error('缺少参数或格式错误,请求失败', 'error');
        }

        $result = message_send($mobile, '430761', $user_id);

        $result[0] == 'success' ? $this->success($result['msg']) : $this->error($result['msg']);
    }

    /**
     * 发布车源接口  ->卖车
     */
    public function uploadModels()
    {

        $carInfo = $this->request->post('carInfo/a');
        $user_id = $this->request->post('user_id');
        if (!$user_id || !$carInfo) {
            $this->error('缺少参数，请求失败', 'error');
        }
        try {
            $store_id = CompanyStore::getByUser_id($user_id)->id;
            $carInfo['store_id'] = $store_id;
            $carInfo['user_id'] = $user_id;
            $carInfo['browse_volume'] = rand(500, 2000);
//            $carInfo['store_description'] = emoji_encode($carInfo['store_description']);
            $modelsInfo = new ModelsInfo();
            $modelsInfo->allowField(true)->save($carInfo) ? $this->success('添加成功', 'success') : $this->error('添加失败', 'error');
        } catch (Exception $e) {
            $this->success($e->getMessage());
        }
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

        $carInfo = $this->request->post('carInfo/a');
        $user_id = $this->request->post('user_id');

        if (!$user_id || !$carInfo) {
            $this->error('缺少参数，请求失败', 'error');
        }
        try {
            $store_id = CompanyStore::getByUser_id($user_id)->id;
            if ($store_id) {
                $carInfo['store_id'] = $store_id;
            }

            $carInfo['user_id'] = $user_id;
            $carInfo['browse_volume'] = rand(500, 2000);
//            $carInfo['store_description'] = emoji_encode($carInfo['store_description']);
            $buyModels = new BuycarModel();
            $buyModels->allowField(true)->save($carInfo) ? $this->success('添加成功', 'success') : $this->error('添加失败', 'error');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 我有线索
     * @throws \think\exception\DbException
     */
    public function clue()
    {

        $carInfo = $this->request->post('carInfo');
        $user_id = $this->request->post('user_id');
        if (!$user_id || !$carInfo) {
            $this->error('缺少参数，请求失败', 'error');
        }

        $store_id = CompanyStore::get(['user_id' => $user_id])->id;
        $carInfo = json_decode($carInfo, true);
        if ($store_id) {
            $carInfo['store_id'] = $store_id;
        }
        $carInfo['user_id'] = $user_id;
        $clue = new Clue();
        $clue->allowField(true)->save($carInfo) ? $this->success('添加成功', 'success') : $this->error('添加失败', 'error');
    }


    /**
     * 首页搜索接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search()
    {
        //搜索条件
        $query = $this->request->post('query_criteria');

        if (!$query) {
            $this->success('请求成功', ['sell' => [], 'buy' => []]);
        }

        $brand_id = Brand::where('name', 'like', '%' . $query . '%')
            ->where('pid', 0)
            ->column('id');

        if ($brand_id) {
            $modelInfoList = self::typeCar(1, 1, ['brand_id' => ['in', $brand_id]], 'id,models_name');
            $buyCarList = self::typeCar(2, 1, ['brand_id' => ['in', $brand_id]], 'id,models_name');
        } else {
            $modelInfoList = self::typeCar(1, 1, ['models_name' => ['like', '%' . $query . '%']], 'id,models_name');
            $buyCarList = self::typeCar(2, 1, ['models_name' => ['like', '%' . $query . '%']], 'id,models_name');
        }

        if ($modelInfoList) {
            $modelInfoList = $this->getCarList($modelInfoList);
        }

        if ($buyCarList) {
            $buyCarList = $this->getCarList($buyCarList);
        }

        $this->success('请求成功', ['sell' => $modelInfoList, 'buy' => $buyCarList]);
    }

    /**
     * 返回得到品牌对应的车辆数组
     * @param $arr
     * @return array
     */
    public function getCarList($arr)
    {
        $check = $real = [];
        foreach ($arr as $k => $v) {
            if (!in_array($v['brand']['id'], $check)) {
                $check[] = $v['brand']['id'];
                $real[] = ['id' => $v['brand']['id'], 'name' => $v['brand']['name'], 'carList' => [['models_name' => $v['models_name']]]];
            } else {
                foreach ($real as $key => $value) {
                    if ($v['brand']['id'] == $value['id']) {
                        $flag = -1;
                        foreach ($real[$key]['carList'] as $kk => $vv) {
                            if ($v['models_name'] == $vv['models_name']) {
                                $flag = -2;
                            }
                        }
                        if ($flag == -1) {
                            $real[$key]['carList'][] = ['models_name' => $v['models_name']];
                        }
                    }
                }
            }
        }

        return $real;
    }

    /**
     * 资讯列表接口
     * @throws \think\exception\DbException
     */
    public function information_list()
    {
        $res = InformationCategories::field('id,categories_name')
            ->with(['automotive' => function ($q) {
                $q->field('id,title,categories_id,author,coverimage,browse_volume');
            }])->select();

        $this->success('请求成功', ['information' => $res]);
    }

    /**
     * 资讯详情接口
     * @throws \think\exception\DbException
     */
    public function information_details()
    {
        $information_id = $this->request->post('information_id');

        if (!$information_id) {
            $this->error('缺少参数，请求失败', 'error');
        }

        $info = AutomotiveInformation::get($information_id)->hidden(['status']);

        AutomotiveInformation::where('id', $information_id)->setInc('browse_volume', rand(1, 10));

        $this->success('请求成功', ['detail' => $info]);
    }

}
