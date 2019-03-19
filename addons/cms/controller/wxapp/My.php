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
use addons\cms\model\Brand;
use addons\cms\model\EarningDetailed;
use addons\cms\model\Message;
use think\Db;
use Endroid\QrCode\QrCode;
use fast\Random;
use think\Exception;

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
        if (!(int)$user_id) $this->error('参数错误');
        try {
            //auditstatus审核是否通过，审核状态:pass_the_audit=审核通过;audit_failed=审核不通过;wait_for_review=待审核;in_the_review = 审核中；paid_the_money=已认证
            $userInfo = User::field('id,nickname,avatar,invite_code,invitation_code_img')
                ->with(['storeHasMany' => function ($q) {
                    $q->with(['storelevel' => function ($q) {
                        $q->withField(['id', 'partner_rank']);
                    }]);
                }])->find($user_id);
            if (!$userInfo) $this->error('未查询到用户信息');
            //如果已认证通过 auditstatus=>paid_the_money，更改nickname 为门店名称
            if ($userInfo['store_has_many']['auditstatus'] == 'paid_the_money') $userInfo['nickname'] = $userInfo['store_has_many']['store_name'];
            $BuycarModel = $this->isOffer(new \addons\cms\model\BuycarModel, $user_id);
            $ModelsInfo = $this->isOffer(new \addons\cms\model\ModelsInfo, $user_id);
            $userInfo['isNewOffer'] = 0;
            if (!empty($BuycarModel) || !empty($ModelsInfo)) {
                $userInfo['isNewOffer'] = 1;
            }
            //查询邀请码背景图片
            $userInfo['invite_bg_img'] = \addons\cms\model\Config::get(['name' => 'invite_bg_img'])->value;
            //如果当前用户的二维码为空

            $userInfo['unread'] = 0;
            $message_list = Message::column('use_id');

            foreach ($message_list as $k => $v) {
                if (strpos($v, ',' . $user_id . ',') === false) {
                    $userInfo['unread'] = 1;
                    break;
                }
            }

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
        $user_id = $this->request->post('user_id');
        if (!(int)$user_id) $this->error('参数错误');
        $time = date('Ymd');
        $qrCode = new QrCode();
        $qrCode->setText($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/?user_id=' . $user_id)
            ->setSize(150)
            ->setPadding(10)
            ->setErrorCorrection('high')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabel(' ')
            ->setLabelFontSize(10)
            ->setImageType(\Endroid\QrCode\QrCode::IMAGE_TYPE_PNG);
        $fileName = DS . 'uploads' . DS . 'qrcode' . DS . $time . '_' . $user_id . '.png';
        $qrCode->save(ROOT_PATH . 'public' . $fileName);
        if ($qrCode) {
            User::update(['id' => $user_id, 'invitation_code_img' => $fileName]) ? $this->success('创建成功', $fileName) : $this->error('创建失败');
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
        $riders = ConfigModel::get(['name' => 'about_riders'])->visible(['name', 'value']);
        $this->success('请求成功', [$riders['name'] => $riders['value']]);
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
     * 我的页面---我想买的
     */
    public function buyCar()
    {
        $user_id = $this->request->post('user_id');

        if (!$user_id) {
            $this->error('缺少参数');
        }

        $buyCarList = collection(BuycarModel::useGlobalScope(false)->field('id,models_name,guide_price,shelfismenu,car_licensetime,kilometres,parkingposition,browse_volume,createtime')
            ->with(['brand' => function ($q) {
                $q->withField('id,name,brand_initials,brand_default_images');
            }])
            ->order('createtime desc')->where([
                'user_id' => $user_id,
            ])->select())->toArray();

        $default_image = ConfigModel::get(['name' => 'default_picture'])->value;

        foreach ($buyCarList as $k => $v) {

            $buyCarList[$k]['modelsimages'] = $default_image;

            $buyCarList[$k]['shelfismenu'] = $v['shelfismenu'] == 2 ? 0 : 1;

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

        $quotedPriceId = array_merge($this->getQuotedPriceId($user_id, 'buy'), $this->getQuotedPriceId($user_id, 'sell'));
        if ($quotedPriceId) {
            QuotedPrice::where('id', 'in', $quotedPriceId)->setField('is_see', 1);
        }

        //收到报价---卖车
        $ModelsInfoList = $this->ModelsInfo('ModelsInfo', $user_id, ['type' => 'sell']);
        //收到报价---买车
        $BuycarModel = $this->ModelsInfo('BuycarModel', $user_id, ['type' => 'buy']);
        //收到报价合并
        $MyModelsInfoList = array_merge($ModelsInfoList, $BuycarModel);

        //我的报价----卖车
        $SellcarModelList = $this->ModelsInfo('ModelsInfo', null, ['user_ids' => $user_id]);
        //我的报价---买车
        $BuycarModelList = $this->ModelsInfo('BuycarModel', null, ['user_ids' => $user_id]);
        //我的报价合并
        $MyBuycarModelList = array_merge($SellcarModelList, $BuycarModelList);

        //收到报价
        $QuotedPriceList['sell'] = $MyModelsInfoList;
        //我的报价
        $QuotedPriceList['buy'] = $MyBuycarModelList;

        $this->success('请求成功', ['QuotedPriceList' => $QuotedPriceList]);

    }

    /**
     * 我的页面---我的报价信息
     */
    public function ModelsInfo($models, $user_id = null, $where)
    {
        //默认手机号
        $default_phone = ConfigModel::get(['name' => 'default_phone'])->value;
        //卖车默认---图片
        $default_image = ConfigModel::get(['name' => 'default_picture'])->value;

        $field = $user_id == null ? null : ['user_id' => $user_id];
        $modelsimages = $models == 'ModelsInfo' ? ',modelsimages' : '';
        $ModelsInfo = collection(QuotedPrice::field('id,user_ids,models_info_id,money,quotationtime,type,buy_car_id,bond,seller_payment_status,buyer_payment_status,deal_status')
            ->with([$models => function ($q) use ($field, $modelsimages) {

                $q->where($field)->withField('id,models_name,guide_price,user_id,shelfismenu,car_licensetime,kilometres,parkingposition,browse_volume,createtime,brand_id' . $modelsimages);

            },
                'user' => function ($query) {
                    $query->withField('id,nickname,avatar,mobile');
                }])
            ->where($where)->select())->toArray();


        foreach ($ModelsInfo as $k => $v) {

            if ($models == 'BuycarModel') {
                $ModelsInfo[$k]['models_info'] = $v['buycar_model'];
                unset($ModelsInfo[$k]['buycar_model']);
            }

            $ModelsInfo[$k]['models_info']['modelsimages'] = $models == 'ModelsInfo' ? explode(',', $ModelsInfo[$k]['models_info']['modelsimages'])[0] : $default_image;

            $brand_info = Brand::where('id', $ModelsInfo[$k]['models_info']['brand_id'])->field('name,brand_default_images')->find();
            $ModelsInfo[$k]['models_info']['brand_name'] = $brand_info['name'];
            $ModelsInfo[$k]['models_info']['brand_default_images'] = $brand_info['brand_default_images'];

            $ModelsInfo[$k]['quotationtime_format'] = $ModelsInfo[$k]['quotationtime'] ? format_date($ModelsInfo[$k]['quotationtime']) : null;

            $ModelsInfo[$k]['money'] = $ModelsInfo[$k]['money'] ? round(($ModelsInfo[$k]['money'] / 10000), 2) : null;
            $ModelsInfo[$k]['models_info']['kilometres'] = $ModelsInfo[$k]['models_info']['kilometres'] ? round(($ModelsInfo[$k]['models_info']['kilometres'] / 10000), 2) . '万' : null;
            $ModelsInfo[$k]['models_info']['guide_price'] = $ModelsInfo[$k]['models_info']['guide_price'] ? round(($ModelsInfo[$k]['models_info']['guide_price'] / 10000), 2) : null;
            $ModelsInfo[$k]['user']['mobile'] = $default_phone;
            $ModelsInfo[$k]['models_info']['car_licensetime'] = $ModelsInfo[$k]['models_info']['car_licensetime'] ? date('Y-m', $ModelsInfo[$k]['models_info']['car_licensetime']) : null;
            //是否可以取消订单
            if ($ModelsInfo[$k]['seller_payment_status'] == 'to_be_paid' && $ModelsInfo[$k]['buyer_payment_status'] == 'to_be_paid') {

                $ModelsInfo[$k]['cancel_order'] = 1;
            } else {

                $ModelsInfo[$k]['cancel_order'] = 0;
            }

        }

        return $ModelsInfo;
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

        $car_type = $this->request->post('car_type');

        $shelfismenu = $this->request->post('shelfismenu');

        $shelfismenu = $shelfismenu == 0 ? 2 : 1;

        if (!$id || !$shelfismenu) {
            $this->error('缺少参数');
        }

        $models_name = $car_type == 'sell' ? new ModelsInfo() : new BuycarModel();
        //上架
        if ($shelfismenu == 1) {

            $models_name->update(['id' => $id, 'shelfismenu' => $shelfismenu]) ? $this->success('上架成功', 'success') : $this->error('上架失败', 'error');

        }
        //下架
        if ($shelfismenu == 2) {

            $models_name->update(['id' => $id, 'shelfismenu' => $shelfismenu]) ? $this->success('下架成功', 'success') : $this->error('下架失败', 'error');

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
            $user = User::field('id,nickname,avatar')
                ->with(['storeHasMany' => function ($q) {
                    $q->field('id,user_id,store_name')->with(['belongsStoreLevel' => function ($q) {
                        $q->withField('partner_rank');
                    }]);
                }])->find($user_id)->toArray();

            $user['store_has_many'] = $user['store_has_many'][0];
            $store_id = CompanyStore::where('user_id', $user_id)->value('id');

            $mymoney = EarningDetailed::field('first_earnings,second_earnings,total_earnings,available_balance')->where('store_id', $store_id)->find();

            $first_store = Collection(Distribution::field('level_store_id,second_earnings')->with(['store' => function ($q) {

                $q->withField('id,store_name,user_id,level_id');

            }])->where('store_id', $store_id)->select())->toArray();

            foreach ($first_store as $k => $v) {

                $first_store[$k]['second_count'] = Distribution::where('store_id', $v['level_store_id'])->count();
                $first_store[$k]['second_moneycount'] = round(Distribution::where('store_id', $v['level_store_id'])->sum('second_earnings'), 2);
                $first_store[$k]['user'] = User::field('id,nickname,avatar')->where('id', $v['store']['user_id'])->find();
                $first_store[$k]['store']['partner_rank'] = StoreLevel::get($v['store']['level_id'])->partner_rank;
            }

            $data = [
                'user' => $user,
                'mymoney' => $mymoney,
                'earning_details' => $first_store
            ];
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('请求成功', ['data' => $data]);

    }


//    /**
//     * 消息列表接口
//     * @throws \think\exception\DbException
//     */
//    public function message_list()
//    {
//        $user_id = $this->request->post('user_id');
//
//        if (!$user_id) {
//            $this->error('缺少参数,请求失败', 'error');
//        }
//        $message = Message::all(function ($q) {
//            $q->order('createtime desc')->field('id,title,createtime,use_id');
//        });
//
//        foreach ($message as $k => $v) {
//            $v['isRead'] = 0;
//
//            if (strpos($v['use_id'], ',' . $user_id . ',') !== false) {
//                $v['isRead'] = 1;
//            }
//            unset($v['use_id']);
//        }
//
//        $this->success('请求成功', ['message_list' => $message]);
//    }


    /**
     * 消息详情接口
     * @throws \think\exception\DbException
     */
    public function message_details()
    {
        $user_id = $this->request->post('user_id');
        $isRead = $this->request->post('unread');
        if (!$user_id) {
            $this->error('缺少参数,请求失败', 'error');
        }

        $message = Message::field(['id', 'title', 'content', 'analysis', 'createtime', 'use_id'])->order('createtime desc')->select();

        if ($isRead == 1) {
            foreach ($message as $k => $v) {
                if (strpos($v['use_id'], ',' . $user_id . ',') === false) {

                    $update_value = $v['use_id'] ? $user_id . ',' : ',' . $user_id . ',';

                    Message::where('id', $v['id'])->setField('use_id', $v['use_id'] . $update_value);
                }
                unset($message[$k]['use_id']);

            }
        }

        $this->success('请求成功', ['message_details' => $message]);
    }

    /**
     * 取消报价单接口
     */
    public function cancellation_of_quotation()
    {
        $quoted_id = $this->request->post('quoted_id');

        Db::startTrans();
        try {

            if (!$quoted_id) {
                $this->error('缺少参数');
            }
            if (!QuotedPrice::get($quoted_id)) {
                $this->error('该订单已被取消');
            }

            $data = QuotedPrice::where('id', $quoted_id)->value('models_info_id');

            if ($data) {

                QuotedPrice::where(['models_info_id' => $data])->setField(['deal_status' => 'start_the_deal']);

            }

            $res = QuotedPrice::destroy($quoted_id);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        !empty($res) ? $this->success('取消成功') : $this->error('取消失败');
    }

    /**
     * 进入升级店铺接口
     * @throws \think\exception\DbException
     */
    public function upgrade_shop()
    {
        $user_id = $this->request->post('user_id');

        if (!$user_id) {
            $this->error('缺少参数');
        }

        $store_info = CompanyStore::get(['user_id' => $user_id])->visible(['id', 'cities_name', 'store_name', 'store_address', 'phone', 'business_life', 'main_camp', 'bank_card', 'store_img', 'id_card_images', 'business_licenseimages', 'level_id', 'store_description', 'real_name']);

        $store_level_list = Shop::getVisibleStoreList($store_info['level_id'], 1);

        $this->success('请求成功', ['store_info' => $store_info, 'store_level_list' => $store_level_list]);
    }

}
