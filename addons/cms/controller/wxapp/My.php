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
use addons\cms\model\EarningDetailed;
use think\Db;
use Endroid\QrCode\QrCode;

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
        try {
            //auditstatus审核是否通过，审核状态:pass_the_audit=审核通过;audit_failed=审核不通过;wait_for_review=待审核;in_the_review = 审核中；paid_the_money=已认证
            $userInfo = User::field('id,nickname,avatar,invite_code,invitation_code_img')
                ->with(['companystoreone' => function ($q) {
                    $q->withField('id,auditstatus,store_name,level_id');
                }])->find($user_id);
            if (!$userInfo) $this->error('未查询到用户信息');
            //如果已认证通过，更改nickname 为门店名称  paid_the_money
            if ($userInfo['companystoreone']['auditstatus'] == 'paid_the_money') $userInfo['nickname'] = $userInfo['companystoreone']['store_name'];
            $BuycarModel = $this->isOffer(new \addons\cms\model\BuycarModel, $user_id);
            $ModelsInfo = $this->isOffer(new \addons\cms\model\ModelsInfo, $user_id);
            $userInfo['isNewOffer'] = 0;
            if (!empty($BuycarModel) || !empty($ModelsInfo)) {
                $userInfo['isNewOffer'] = 1;
            }
//            $userInfo['storeLevel'] = CompanyStore::with(['storelevel' => function ($q) {
//                $q->withField(['partner_rank,id']);
//            }])->select();
            //如果当前用户的二维码为空
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('请求成功', ['userInfo' => $userInfo]);
    }


    /**
     * 是否报价
     * @param $modelName
     * @param $user_id
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isOffer($modelName, $user_id)
    {
        try {
            $dataId = $modelName::where('user_id', $user_id)->column('id');
            if ($dataId) {
                $newData = QuotedPrice::where([
                    'models_info_id' => ['in', $dataId],
                    'is_see' => 2,
                    'user_ids' => ['neq', $user_id]
                ])->select();
                return $newData;
            }

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

    }

    /**
     * 生成二维码
     * @throws \Endroid\QrCode\Exceptions\ImageTypeInvalidException
     */
    public function setQrcode()
    {

//        $logo = \Think\Config::get('upload')['cdnurl'] . \addons\cms\model\Config::get(['name' => 'site_logo'])->value;
        $user_id = $this->request->post('user_id');
        if (!(int)$user_id) $this->error('参数错误');
        $time = date('Ymd');
        $qrCode = new QrCode();
        $qrCode->setText($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/?user_id=4444')
            ->setSize(150)
            ->setPadding(10)
            ->setErrorCorrection('high')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabel(' ')
            ->setLabelFontSize(10)
            ->setImageType(\Endroid\QrCode\QrCode::IMAGE_TYPE_PNG);
//        $qrCode ->render();die;
        $fileName = DS . 'uploads' . DS . 'qrcode' . DS . $time . '_' . $user_id . '.png';
        $qrCode->save(ROOT_PATH . 'public' . $fileName);
        if ($qrCode) {
            User::update(['id' => 20, 'invitation_code_img' => $fileName]) ? $this->success('创建成功', $fileName) : $this->error('创建失败');
        }
        $this->error('未知错误');
    }

    /**
     * 服务协议
     * @throws \think\exception\DbException
     */
    public function service_agreement()
    {
        $agreement = ConfigModel::get(['group' => 'agreement'])->visible(['name', 'value']);

        $this->success('请求成功', [$agreement['name'] => $agreement['value']]);
    }


    /**
     * 关于车友
     * @throws \think\exception\DbException
     */
    public function about_riders()
    {
        $riders =  ConfigModel::get(['name'=>'about_riders'])->visible(['name','value']);
        $this->success('请求成功',[$riders['name']=>$riders['value']]);
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

            $buyCarList[$k]['shelfismenu'] = $v['shelfismenu'] = 2 ? 0 : 1;

            $buyCarList[$k]['kilometres'] = $v['kilometres'] ? round(($v['kilometres'] / 10000), 2) . '万公里' : null;
            $buyCarList[$k]['guide_price'] = $v['guide_price'] ? round(($v['guide_price'] / 10000), 2) . '万' : null;

            $buyCarList[$k]['car_licensetime'] = $v['car_licensetime'] ? date('Y-m-d', $v['car_licensetime']) : null;
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
        //默认手机号
        $default_phone = ConfigModel::get(['name' => 'default_phone'])->value;

        $quotedPriceId = array_merge($this->getQuotedPriceId($user_id, 'buy'), $this->getQuotedPriceId($user_id, 'sell'));
        if ($quotedPriceId) {
            QuotedPrice::where('id', 'in', $quotedPriceId)->setField('is_see', 1);
        }
        //收到报价
        $ModelsInfoList = collection(QuotedPrice::field('id,user_ids,models_info_id,money,quotationtime,type')
            ->with(['ModelsInfo' => function ($q) use ($user_id) {
                $q->where(['user_id' => $user_id])->withField('id,models_name,guide_price,user_id,shelfismenu,car_licensetime,kilometres,parkingposition,browse_volume,createtime,modelsimages,brand_id');
            },
                'user' => function ($q) {
                    $q->withField('id,nickname,avatar,mobile');
                }])
            ->where(['type' => 'sell'])->select())->toArray();


        foreach ($ModelsInfoList as $k => $v) {

            $ModelsInfoList[$k]['models_info']['modelsimages'] = explode(',', $ModelsInfoList[$k]['models_info']['modelsimages'])[0];

            $ModelsInfoList[$k]['user']['mobile'] = $default_phone;
            $ModelsInfoList[$k]['models_info']['car_licensetime'] = $ModelsInfoList[$k]['models_info']['car_licensetime'] ? date('Y-m', $ModelsInfoList[$k]['models_info']['car_licensetime']) : null;

            $ModelsInfoList[$k]['models_info']['brand_name'] = BrandCate::where('id', $ModelsInfoList[$k]['models_info']['brand_id'])->value('name');
            $ModelsInfoList[$k]['quotationtime'] = $ModelsInfoList[$k]['quotationtime'] ? format_date($ModelsInfoList[$k]['quotationtime']) : null;
            $ModelsInfoList[$k]['money'] = $ModelsInfoList[$k]['money'] ? round(($ModelsInfoList[$k]['money'] / 10000), 2) : null;
            $ModelsInfoList[$k]['models_info']['kilometres'] = $ModelsInfoList[$k]['models_info']['kilometres'] ? round(($ModelsInfoList[$k]['models_info']['kilometres'] / 10000), 2) . '万' : null;
            $ModelsInfoList[$k]['models_info']['guide_price'] = $ModelsInfoList[$k]['models_info']['guide_price'] ? round(($ModelsInfoList[$k]['models_info']['guide_price'] / 10000), 2) : null;

        }
        //我的报价----卖车
        $SellcarModelList = collection(QuotedPrice::field('id,user_ids,money,quotationtime,type,models_info_id')
            ->with(['ModelsInfo' => function ($q) {
                $q->withField('id,models_name,guide_price,shelfismenu,car_licensetime,kilometres,parkingposition,browse_volume,createtime,modelsimages,brand_id');
            },
                'user' => function ($q) {
                    $q->withField('mobile');
                }])
            ->where('user_ids', $user_id)->select())->toArray();

        foreach ($SellcarModelList as $k => $v) {
            $SellcarModelList[$k]['models_info']['car_licensetime'] = $SellcarModelList[$k]['models_info']['car_licensetime'] ? date('Y-m', $SellcarModelList[$k]['models_info']['car_licensetime']) : null;
            $SellcarModelList[$k]['models_info']['modelsimages'] = explode(',', $SellcarModelList[$k]['models_info']['modelsimages'])[0];
            $SellcarModelList[$k]['models_info']['brand_name'] = BrandCate::where('id', $SellcarModelList[$k]['models_info']['brand_id'])->value('name');
            $SellcarModelList[$k]['quotationtime'] = $SellcarModelList[$k]['quotationtime'] ? format_date($SellcarModelList[$k]['quotationtime']) : null;
            $SellcarModelList[$k]['money'] = $SellcarModelList[$k]['money'] ? round(($SellcarModelList[$k]['money'] / 10000), 2) : null;
            $SellcarModelList[$k]['models_info']['kilometres'] = $SellcarModelList[$k]['models_info']['kilometres'] ? round(($SellcarModelList[$k]['models_info']['kilometres'] / 10000), 2) . '万' : null;
            $SellcarModelList[$k]['models_info']['guide_price'] = $SellcarModelList[$k]['models_info']['guide_price'] ? round(($SellcarModelList[$k]['models_info']['guide_price'] / 10000), 2) : null;

            $SellcarModelList[$k]['user']['mobile'] = $default_phone;

        }

        //我的报价---买车
        $BuycarModelList = collection(QuotedPrice::field('id,user_ids,money,quotationtime,type,buy_car_id')
            ->with(['BuycarModel' => function ($q) {
                $q->withField('id,models_name,guide_price,shelfismenu,car_licensetime,kilometres,parkingposition,browse_volume,createtime,brand_id');
            },
                'user' => function ($q) {
                    $q->withField('mobile');
                }])
            ->where('user_ids', $user_id)->select())->toArray();
        //卖车默认---图片
        $default_image = ConfigModel::get(['name' => 'default_picture'])->value;
        foreach ($BuycarModelList as $k => $v) {
            $BuycarModelList[$k]['models_info'] = $v['buycar_model'];
            $BuycarModelList[$k]['models_info']['modelsimages'] = $default_image;
            $BuycarModelList[$k]['models_info']['brand_name'] = BrandCate::where('id', $BuycarModelList[$k]['models_info']['brand_id'])->value('name');
            $BuycarModelList[$k]['quotationtime'] = $BuycarModelList[$k]['quotationtime'] ? format_date($BuycarModelList[$k]['quotationtime']) : null;
            $BuycarModelList[$k]['money'] = $v['money'] ? round(($BuycarModelList[$k]['money'] / 10000), 2) : null;
            $BuycarModelList[$k]['models_info']['kilometres'] = $BuycarModelList[$k]['models_info']['kilometres'] ? round(($BuycarModelList[$k]['models_info']['kilometres'] / 10000), 2) . '万' : null;
            $BuycarModelList[$k]['models_info']['guide_price'] = $BuycarModelList[$k]['models_info']['guide_price'] ? round(($BuycarModelList[$k]['models_info']['guide_price'] / 10000), 2) : null;
            $BuycarModelList[$k]['user']['mobile'] = $default_phone;
            $BuycarModelList[$k]['models_info']['car_licensetime'] = $BuycarModelList[$k]['models_info']['car_licensetime'] ? date('Y-m', $BuycarModelList[$k]['models_info']['car_licensetime']) : null;

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


    /**
     * 我的页面---我的钱包
     */
    public function my_wallet()
    {
        $user_id = $this->request->post('user_id');

        if (!$user_id) {
            $this->error('缺少参数');
        }
        try {
            $user = User::where('id', $user_id)->field('id,nickname,avatar')->find();

            $store_id = CompanyStore::where('user_id', $user_id)->value('id');
            
            $mymoney = EarningDetailed::field('first_earnings,second_earnings,total_earnings')->where('store_id', $store_id)->find()->toArray();

            $first_store = Collection(Distribution::field('level_store_id')->with(['store' => function ($q) {

                    $q->withField('id,store_name,user_id');

                }])->where('store_id', $store_id)->select())->toArray();

            foreach ($first_store as $k => $v) {

                $first_store[$k]['second_count'] = Distribution::where('store_id',$v['level_store_id'])->count();
                $first_store[$k]['second_moneycount'] = Distribution::where('store_id',$v['level_store_id'])->sum('second_earnings');
                $first_store[$k]['user'] = User::field('id,nickname,avatar')->where('id', $v['store']['user_id'])->find();

            }

            $data = [
                'user' => $user,
                'mymoney' => $mymoney,
                'earning_details' => $first_store
            ];
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success('请求成功',['data' => $data]);
       
    }

}
