<?php

namespace addons\cms\controller\wxapp;

use addons\cms\model\Comment;
use addons\cms\model\Page;
use addons\cms\model\Distribution;
use addons\cms\model\StoreUser;
use addons\cms\model\CompanyStore;
use addons\cms\model\StoreLevel;
use addons\cms\model\Config;
use addons\cms\model\User;
use addons\cms\model\ModelsInfo;
use addons\cms\model\BuycarModel;
use addons\cms\model\Config as ConfigModel;
use addons\cms\model\QuotedPrice;
use addons\cms\model\BrandCate;
use think\Db;

/**
 * 我的
 */
class My extends Base
{

    protected $noNeedLogin = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 我发表的评论
     */
    public function comment()
    {
        $page = (int)$this->request->request('page');
        $commentList = Comment::
        with('archives')
            ->where(['user_id' => $this->auth->id])
            ->order('id desc')
            ->page($page, 10)
            ->select();
        foreach ($commentList as $index => $item) {
            $item->create_date = human_date($item->createtime);
        }

        $this->success('', ['commentList' => $commentList]);
    }

    /**
     * 关于我们
     */
    public function aboutus()
    {
        $pageInfo = Page::getByDiyname('aboutus');
        if (!$pageInfo || $pageInfo['status'] != 'normal') {
            $this->error(__('单页未找到'));
        }
        $this->success('', ['pageInfo' => $pageInfo]);
    }

    /**
     * 我的首页数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $user_id = $this->request->post('user_id');

        $userInfo = User::field('id,nickname,avatar,name,id_card_images')
            ->with(['companystoreone' => function ($q) {
                $q->withField('id,store_qrcode');
            }])->find($user_id);
        $userInfo['isRealName'] = 0;
        if ($userInfo['name'] && $userInfo['id_card_images']) {
            $userInfo['isRealName'] = 1;
        }

        $buyCarId = BuycarModel::where('user_id', $user_id)->column('id');

        $modelInfoId = ModelsInfo::where('user_id', $user_id)->column('id');

        if ($modelInfoId) {
            $newModelInfo = QuotedPrice::where([
                'models_info_id' => ['in', $modelInfoId],
                'is_see' => 2,
                'user_ids' => ['neq', $user_id]
            ])->select();
        }

        if ($buyCarId) {
            $newBuyCar = QuotedPrice::where([
                'buy_car_id' => ['in', $buyCarId],
                'is_see' => 2,
                'user_ids' => ['neq', $user_id]
            ])->select();
        }
        $userInfo['isNewOffer'] = 0;
        if (!empty($newModelInfo) || !empty($newBuyCar)) {
            $userInfo['isNewOffer'] = 1;
        }

        unset($userInfo['name'], $userInfo['id_card_images']);

        $this->success('请求成功', ['userInfo' => $userInfo]);
    }


    public function myStore()
    {
        //等级列表
//        $levelList = collection(StoreLevel::all(function ($q) {
//            $q->field('id,partner_rank,money,explain');
//        }))->toArray();
//
//        foreach ($levelList as $k => $v) {
//            $v['money'] = floatval($v['money']) / 10000;
//
//            $v['money'] = round($v['money'], 1);
//
//            if ($v['money'] >= 1) {
//                $v['money'] = $v['money'] . '万';
//            } else {
//                $v['money'] = ($v['money'] * 10) . '千';
//            }
//
//            $levelList[$k]['money'] = $v['money'];
//
//        }
//
//        //所有可用的邀请码
//        $codeList = CompanyStore::column('invitation_code');
    }

    /**
     * 提交审核店铺接口
     * @throws \think\exception\DbException
     */
    public function submit_audit()
    {

        $arr = [
            'store_name' => '友车友家店铺',
            'cities_name' => '北京',
            'store_address' => '中关村',
            'name' => '情感',
            'phone' => '13548126668',
            'store_description' => '今年开的店铺',
            'business_life' => '3年-5年',
            'main_camp' => '主营：宝马，法拉利，宾利，捷达',
            'bank_card' => '6217003810028413121',
            'code' => '8B9D8C4F'

        ];

//$this->success(json_encode($arr));
        $user_id = $this->request->post('user_id');
        $infos = $this->request->post('auditInfo');
        $infos = "{\"store_name\":\"\\u53cb\\u8f66\\u53cb\\u5bb6\\u5e97\\u94fa\",\"cities_name\":\"\\u5317\\u4eac\",\"store_address\":\"\\u4e2d\\u5173\\u6751\",\"name\":\"\\u60c5\\u611f\",\"phone\":\"13548126668\",\"store_description\":\"\\u4eca\\u5e74\\u5f00\\u7684\\u5e97\\u94fa\",\"business_life\":\"3\\u5e74-5\\u5e74\",\"main_camp\":\"\\u4e3b\\u8425\\uff1a\\u5b9d\\u9a6c\\uff0c\\u6cd5\\u62c9\\u5229\\uff0c\\u5bbe\\u5229\\uff0c\\u6377\\u8fbe\",\"bank_card\":\"6217003810028413121\",\"code\":\"C3308128\"}";
        $infos = json_decode($infos, true);
//        $usersInfo = StoreUser::create([
//            'name' => $infos['name']
//        ]);
        User::update([
            'id' => $user_id,
            'name' => $infos['name']
        ]);

        $company = CompanyStore::create([
            'store_name' => $infos['store_name'],
            'cities_name' => $infos['cities_name'],
            'store_address' => $infos['store_address'],
            'phone' => $infos['phone'],
            'store_description' => $infos['store_description'],
            'business_life' => $infos['business_life'],
            'main_camp' => $infos['main_camp'],
            'invitation_code' => $this->make_coupon_card(),
            'user_id' => $user_id,
        ]);


        if (!empty($infos['code'])) {
            Distribution::create([
                'store_id' => CompanyStore::get(['invitation_code' => $infos['code']])->id,
                'level_store_id' => $company->id,
                'earnings' => 0,
                'second_earnings' => 0
            ]);
        }

        $this->success('请求成功', 'success');

    }


    /**
     * 支付成功后接口
     * @throws \think\exception\DbException
     */
    public function after_payment()
    {
//        $arr = [
//            'bank_card' => '6217003810028413121',
//            'id_card_images' => [
//                'positive' => '/uploads/20181116/c25d72c53440bd4580923a91e083b189.png',
//                'negative' => '/uploads/20181116/c25d72c53440bd4580923a91e083b189.png',
//            ],
//            'level_id' => 1
//        ];

        $afterInfo = $this->request->post('afterInfo');
//        $this->success(json_encode($arr));

        $afterInfo = "{\"bank_card\":\"6217003810028413121\",\"id_card_images\":{\"positive\":\"\\/uploads\\/20181116\\/c25d72c53440bd4580923a91e083b189.png\",\"negative\":\"\\/uploads\\/20181116\\/c25d72c53440bd4580923a91e083b189.png\"},\"level_id\":1}";

        $user_id = $this->request->post('user_id');

        $afterInfo = json_decode($afterInfo, true);

        $store_id = CompanyStore::get(['user_id' => $user_id])->id;

        $distribution_id = Distribution::get(['level_store_id' => $store_id])->id;

//        $this->success($distribution_id.' -'.$store_id);

        $resultUser = User::update([
            'id' => $user_id,
            'bank_card' => $afterInfo['bank_card'],
            'id_card_images' => $afterInfo['id_card_images']['positive'] . ';' . $afterInfo['id_card_images']['negative']
        ]);

        $resultCompany = CompanyStore::update([
            'id' => $store_id,
            'level_id' => $afterInfo['level_id']
        ]);

        if ($distribution_id) {
            $money = StoreLevel::get($afterInfo['level_id'])->money;
            $gradeOne = Config::where(['group' => 'rate'])->column('value');
            $earnings = floatval($gradeOne[0]) * $money;
            $second_earnings = floatval($gradeOne[1]) * $money;

            $res = Distribution::update([
                'id' => $distribution_id,
                'earnings' => $earnings,
                'second_earnings' => $second_earnings
            ]);

            $resultUser && $resultCompany && $res ? $this->success('请求成功', 'success') : $this->error('请求失败', 'error');

        }

        $resultUser && $resultCompany ? $this->success('请求成功', 'success') : $this->error('请求失败', 'error');

    }

    /**
     * 邀请码
     * @return bool|string
     */
    public function make_coupon_card()
    {
        mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $uuid = substr($charid, 8, 8);

        return $uuid;

    }

    /**
     * 我的店铺主页接口
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function homepages()
    {
        $user_id = $this->request->post('user_id');

        $users = User::field('id,name,avatar')
            ->with(['companystoreone' => function ($q) {
                $q->withField('id,store_qrcode');
            }])->find($user_id);

        $partner = User::field('id,name,avatar')->with(['companystore' => function ($q) use ($users) {
            $q->with(['sondistribution' => function ($sondistribution) use ($users) {
                $sondistribution->where('store_id', $users['companystoreone']['id']);
            }]);
        }])->select();

        $commission = 0;     //佣金收益
        $people_number = 0;  //总人数

        //去除不是下级店铺的数据
        foreach ($partner as $k => $v) {
            if (empty($v['companystore'][0]['sondistribution'])) {
                unset($partner[$k]);
            }
        }

        $partner = array_values($partner);
//        $this->success($partner);
        foreach ($partner as $k => $v) {
            //得到下1级店铺所有下2级店铺的收益
            $partner[$k]['allProfit'] = Db::name('distribution')
                ->where('store_id', $v['companystore'][0]['id'])
                ->sum('second_earnings');

            //得到下1级店铺所有下2级店铺的数量
            $partner[$k]['memberNumber'] = Db::name('distribution')
                ->where('store_id', $v['companystore'][0]['id'])
                ->count('id');

            $partner[$k]['memberNumber'] = $partner[$k]['memberNumber'] + 1;

            $partner[$k]['allProfit'] = $partner[$k]['allProfit'] + $v['companystore'][0]['sondistribution'][0]['earnings'];

            $commission = $commission + $partner[$k]['allProfit'];
            $people_number += $partner[$k]['memberNumber'];

            unset($partner[$k]['companystore']);

        }
        $users['allProfit'] = $commission;
        $users['memberNumber'] = $people_number;
//        $this->success($partner);
        $data = [
            'userInfo' => $users,
            'memeberList' => $partner
        ];

        $this->success($data);

    }

    /**
     * 我的页面---我想买的
     */
    public function buyCar()
    {
        $user_id = $this->request->post('user_id');

        if (!$user_id) {
            $this->error('缺少参数');
        }

        $buyCarList = collection(BuycarModel::field('id,models_name,guide_price,shelfismenu,car_licensetime,kilometres,parkingposition,browse_volume,createtime')
            ->with(['brand' => function ($q) {
                $q->withField('id,name,bfirstletter');
            }])
            ->order('createtime desc')->where('user_id', $user_id)->select())->toArray();

        $default_image = ConfigModel::get(['name' => 'default_picture'])->value;

        foreach ($buyCarList as $k => $v) {

            $buyCarList[$k]['modelsimages'] = $default_image;

            $buyCarList[$k]['kilometres'] = $v['kilometres'] ? ($v['kilometres'] / 10000) . '万公里' : null;
            $buyCarList[$k]['guide_price'] = $v['guide_price'] ? ($v['guide_price'] / 10000) . '万' : null;

            $buyCarList[$k]['car_licensetime'] = $v['car_licensetime'] ? date('Y', $v['car_licensetime']) : null;
        }

        $this->success('请求成功', ['buyCarList' => $buyCarList]);

    }

    /**
     * 我的页面---我的报价
     */
    public function myQuoted()
    {
        $user_id = $this->request->post('user_id');

        if (!$user_id) {
            $this->error('缺少参数');
        }
        
        $quotedPriceId = array_merge($this->getQuotedPriceId($user_id,'buy'),$this->getQuotedPriceId($user_id,'sell'));
        if($quotedPriceId){
            QuotedPrice::where('id','in',$quotedPriceId)->setField('is_see',1);
        }
        //收到报价
        $ModelsInfoList = collection(QuotedPrice::field('id,user_ids,models_info_id,money,quotationtime,type')
            ->with(['ModelsInfo'=>function ($q) use ($user_id){
                $q->where(['user_id'=>$user_id])->withField('id,models_name,guide_price,user_id,shelfismenu,car_licensetime,kilometres,parkingposition,browse_volume,createtime,modelsimages,brand_id');
            },
            'user'=>function ($q){
                $q->withField('id,nickname,avatar,mobile');
            }])
        ->where(['type'=>'sell'])->select())->toArray();


        foreach ($ModelsInfoList as $k => $v) {

            $ModelsInfoList[$k]['models_info']['modelsimages'] = explode(',', $ModelsInfoList[$k]['models_info']['modelsimages'])[0];

            $ModelsInfoList[$k]['models_info']['brand_name'] = BrandCate::where('id', $ModelsInfoList[$k]['models_info']['brand_id'])->value('name');
            $ModelsInfoList[$k]['quotationtime'] = $ModelsInfoList[$k]['quotationtime'] ? date('Y', $ModelsInfoList[$k]['quotationtime']) : null;
            $ModelsInfoList[$k]['money'] = $ModelsInfoList[$k]['money'] ? ($ModelsInfoList[$k]['money'] / 10000) : null;
            $ModelsInfoList[$k]['models_info']['kilometres'] = $ModelsInfoList[$k]['models_info']['kilometres'] ? ($ModelsInfoList[$k]['models_info']['kilometres'] / 10000) . '万' : null;
            $ModelsInfoList[$k]['models_info']['guide_price'] = $ModelsInfoList[$k]['models_info']['guide_price'] ? ($ModelsInfoList[$k]['models_info']['guide_price'] / 10000) : null;
            
        }
        //我的报价----卖车
        $SellcarModelList = collection(QuotedPrice::field('id,user_ids,money,quotationtime,type,models_info_id')
            ->with(['ModelsInfo'=>function ($q){
                $q->withField('id,models_name,guide_price,shelfismenu,car_licensetime,kilometres,parkingposition,browse_volume,createtime,modelsimages,brand_id');
            },
            'user'=>function ($q){
                $q->withField('mobile');
        }])
        ->where('user_ids', $user_id)->select())->toArray();

        foreach ($SellcarModelList as $k=>$v){
            $SellcarModelList[$k]['models_info']['modelsimages'] = explode(',', $SellcarModelList[$k]['models_info']['modelsimages'])[0];
            $SellcarModelList[$k]['models_info']['brand_name'] = BrandCate::where('id', $SellcarModelList[$k]['models_info']['brand_id'])->value('name');
            $SellcarModelList[$k]['quotationtime'] = $SellcarModelList[$k]['quotationtime'] ? date('Y', $SellcarModelList[$k]['quotationtime']) : null;
            $SellcarModelList[$k]['money'] = $SellcarModelList[$k]['money'] ? ($SellcarModelList[$k]['money'] / 10000) : null;
            $SellcarModelList[$k]['models_info']['kilometres'] = $SellcarModelList[$k]['models_info']['kilometres'] ? ($SellcarModelList[$k]['models_info']['kilometres'] / 10000) . '万' : null;
            $SellcarModelList[$k]['models_info']['guide_price'] = $SellcarModelList[$k]['models_info']['guide_price'] ? ($SellcarModelList[$k]['models_info']['guide_price'] / 10000) : null;
        }

        //我的报价---买车
        $BuycarModelList = collection(QuotedPrice::field('id,user_ids,money,quotationtime,type,buy_car_id')
            ->with(['BuycarModel'=>function ($q){
                $q->withField('id,models_name,guide_price,shelfismenu,car_licensetime,kilometres,parkingposition,browse_volume,createtime,brand_id');
            },
            'user'=>function ($q){
                $q->withField('mobile');
        }])
        ->where('user_ids', $user_id)->select())->toArray();
        //卖车默认---图片
        $default_image = ConfigModel::get(['name'=>'default_picture'])->value;
        foreach ($BuycarModelList as $k=>$v){
            $BuycarModelList[$k]['models_info']  = $v['buycar_model'];
            $BuycarModelList[$k]['models_info']['modelsimages'] = $default_image;
            $BuycarModelList[$k]['models_info']['brand_name'] = BrandCate::where('id', $BuycarModelList[$k]['models_info']['brand_id'])->value('name');
            $BuycarModelList[$k]['quotationtime'] = $BuycarModelList[$k]['quotationtime'] ? date('Y', $BuycarModelList[$k]['quotationtime']) : null;
            $BuycarModelList[$k]['money'] = $v['money'] ? ($BuycarModelList[$k]['money'] / 10000) : null;
            $BuycarModelList[$k]['models_info']['kilometres'] = $BuycarModelList[$k]['models_info']['kilometres'] ? ($BuycarModelList[$k]['models_info']['kilometres'] / 10000) . '万' : null;
            $BuycarModelList[$k]['models_info']['guide_price'] = $BuycarModelList[$k]['models_info']['guide_price'] ? ($BuycarModelList[$k]['models_info']['guide_price'] / 10000) : null;
            
            unset($BuycarModelList[$k]['buycar_model']);
        }
        //我的报价合并
        $MyBuycarModelList = array_merge($SellcarModelList, $BuycarModelList);
        
        //收到报价
        $QuotedPriceList['sell'] = $ModelsInfoList;
        //我的报价
        $QuotedPriceList['buy'] = $MyBuycarModelList;

        $this->success('请求成功', ['QuotedPriceList' => $QuotedPriceList]);

    }

    public function getQuotedPriceId($user_id, $type)
    {
        $table = $type == 'sell' ? 'models_info' : 'buycar_model';
        $join = $type == 'sell' ? 'models_info_id' : 'buy_car_id';
        return Db::name($table)
            ->alias('a')
            ->join('quoted_price b', 'a.id = b.' . $join)
            ->where([
                'a.user_id' => $user_id,
                'b.user_ids' => ['neq', $user_id],
                'b.is_see' => 2
            ])->column('b.id');
    }

    /**
     * 我的页面---我想买的---上下架
     */
    public function Buyshelf()
    {
        $id = $this->request->post('id');

        $shelfismenu = $this->request->post('shelfismenu');

        $shelfismenu = $shelfismenu == 0 ? 2 : 1;

        if (!$id || !$shelfismenu) {
            $this->error('缺少参数');
        }
        //上架
        if ($shelfismenu == 1) {

            BuycarModel::update(['id' => $id, 'shelfismenu' => $shelfismenu]) ? $this->success('上架成功', 'success') : $this->error('上架失败', 'error');

        }
        //下架
        if ($shelfismenu == 2) {

            BuycarModel::update(['id' => $id, 'shelfismenu' => $shelfismenu]) ? $this->success('下架成功', 'success') : $this->error('下架失败', 'error');

        }

    }


}
