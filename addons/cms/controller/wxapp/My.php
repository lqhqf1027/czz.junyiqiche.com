<?php

namespace addons\cms\controller\wxapp;

use addons\cms\model\BankInfo;
use addons\cms\model\Collection;
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
use addons\cms\model\WithdrawalsRecord;
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
//            $BuycarModel = $this->isOffer(new \addons\cms\model\BuycarModel, $user_id);
//            $ModelsInfo = $this->isOffer(new \addons\cms\model\ModelsInfo, $user_id);
//            $userInfo['isNewOffer'] = 0;

            $seller = array_merge(self::getQuotedId('ModelsInfo', ['by_user_ids' => $user_id, 'is_see' => 2]), self::getQuotedId('BuycarModel', ['user_ids' => $user_id, 'is_see' => 2]));
            $buyer = array_merge(self::getQuotedId('BuycarModel', ['by_user_ids' => $user_id, 'is_see' => 2]), self::getQuotedId('ModelsInfo', ['user_ids' => $user_id, 'is_see' => 2]));

            $userInfo['isNewOfferSeller'] = $seller ? 1 : 0;
            $userInfo['isNewOfferbuyer'] = $buyer ? 1 : 0;


//            if (!empty($BuycarModel) || !empty($ModelsInfo)) {
//                $userInfo['isNewOffer'] = 1;
//            }
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
     * 我的页面---我是卖家---收到报价/我的报价
     */
    public function myQuoted()
    {
        $user_id = $this->request->post('user_id');

        if (!$user_id) {
            $this->error('缺少参数');
        }

        $not_see = array_merge(self::getQuotedId('ModelsInfo', ['by_user_ids' => $user_id, 'is_see' => 2]), self::getQuotedId('BuycarModel', ['user_ids' => $user_id, 'is_see' => 2]));

        if ($not_see) {
            QuotedPrice::where('id', 'in', $not_see)->setField('is_see', 1);
        }

        $this->success('请求成功', ['QuotedPriceList' => ['receive_quotation' => self::getQuotedInformation(new ModelsInfo(), ['by_user_ids' => $user_id], 'seller'), 'my_quoted' => self::getQuotedInformation(new BuycarModel(), ['user_ids' => $user_id], 'seller'), 'default_phone' => ConfigModel::getByName('default_phone')->value]]);

    }


    /**
     * 我是买家
     */
    public function buyer_quote()
    {
        $user_id = $this->request->post('user_id');

        if (!$user_id) {
            $this->error('缺少参数');
        }

        $not_see = array_merge(self::getQuotedId('BuycarModel', ['by_user_ids' => $user_id, 'is_see' => 2]), self::getQuotedId('ModelsInfo', ['user_ids' => $user_id, 'is_see' => 2]));
        if ($not_see) {
            QuotedPrice::where('id', 'in', $not_see)->setField('is_see', 1);
        }

        $this->success('请求成功', ['QuotedPriceList' => ['receive_quotation' => self::getQuotedInformation(new BuycarModel(), ['by_user_ids' => $user_id], 'buyer', ['user_id' => $user_id]), 'my_quoted' => self::getQuotedInformation(new ModelsInfo(), ['user_ids' => $user_id], 'seller'), 'default_phone' => ConfigModel::getByName('default_phone')->value]]);
    }

    public static function getQuotedInformation($table, $where, $type, $where_model = null)
    {
        $default_image = ConfigModel::getByName('default_picture')->value;

        $elseField = $table == new ModelsInfo() ? ',modelsimages' : '';

        $receive_quotation = collection($table->useGlobalScope(false)->field('id,models_name,car_licensetime,kilometres,parkingposition,guide_price,browse_volume,createtime,shelfismenu' . $elseField)->with(['hasManyQuotedPrice' => function ($q) use ($where) {
            $q->where($where)->order('quotationtime desc')->with(['user' => function ($q) {
                $q->withField('nickname,avatar');
            }]);
        },'brand'=>function ($q){
            $q->withField('brand_default_images');
        }])->where($where_model)->select())->toArray();

        if ($receive_quotation) {

            foreach ($receive_quotation as $k => $v) {

                $receive_quotation[$k]['car_licensetime'] = $v['car_licensetime'] ? date('Y-m', $v['car_licensetime']) : '';
                $receive_quotation[$k]['kilometres'] = $v['kilometres'] ? round($v['kilometres'] / 10000, 2) : 0;
                $receive_quotation[$k]['guide_price'] = $v['guide_price'] ? round($v['guide_price'] / 10000, 2) : 0;
                $receive_quotation[$k]['modelsimages'] = $v['modelsimages'] ? explode(',', $v['modelsimages'])[0] : $default_image;
                $receive_quotation[$k]['createtime'] = format_date($v['createtime']);

                if (!$v['has_many_quoted_price']) {
                    if ($type == 'seller') {
                        unset($receive_quotation[$k]);
                    }
                    continue;
                };

                foreach ($v['has_many_quoted_price'] as $kk => $vv) {
                    $receive_quotation[$k]['has_many_quoted_price'][$kk]['quotationtime_format'] = format_date($vv['quotationtime']);
                    $receive_quotation[$k]['has_many_quoted_price'][$kk]['money'] = round($vv['money'] / 10000, 2);
                }

            }
        }

        return $receive_quotation ? array_values($receive_quotation) : [];
    }

    /**
     * 得到是否有新的报价ID
     * @param $withTable
     * @param $where
     * @return array
     */
    public static function getQuotedId($withTable, $where)
    {
        return QuotedPrice::with([$withTable])
            ->where($where)->column('quoted_price.id');
    }

    /**
     * 我的页面---我想买的---上下架
     */
    public function Buyshelf()
    {
        $id = $this->request->post('id');

        $car_type = $this->request->post('car_type');

        $shelfismenu = $this->request->post('shelfismenu');

        $user_id = $this->request->post('user_id');

        $shelfismenu = $shelfismenu == 0 ? 2 : 1;

        if (!$id || !$shelfismenu) {
            $this->error('缺少参数');
        }

        try {
            $models_name = $car_type == 'sell' ? new ModelsInfo() : new BuycarModel();

            $with_table = $car_type == 'sell' ? 'ModelsInfo' : 'BuycarModel';

            if ($shelfismenu == 1) {

                $check_status = QuotedPrice::field('id,buyer_payment_status,seller_payment_status')
                    ->with([$with_table => function ($q) use ($with_table, $id) {
                        $q->where('id', $id)->withField('id');
                    }])->where([
                        'by_user_ids' => $user_id,
                    ])->select();

                if ($check_status) {

                    foreach ($check_status as $k => $v) {
                        if ($v['buyer_payment_status'] != 'to_be_paid' || $v['seller_payment_status'] != 'to_be_paid') {
                            throw new Exception('该车辆暂不能上架');
                        }
                    }

                }

                //上架
                $models_name->update(['id' => $id, 'shelfismenu' => $shelfismenu]) ? $this->success('上架成功', 'success') : $this->error('上架失败', 'error');
            }

            //下架
            if ($shelfismenu == 2) {

                $models_name->update(['id' => $id, 'shelfismenu' => $shelfismenu]) ? $this->success('下架成功', 'success') : $this->error('下架失败', 'error');

            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
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
                'earning_details' => $first_store,
            ];
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('请求成功', ['data' => $data]);

    }

    /**
     * 提现记录接口
     */
    public function withdrawals_record()
    {

        $store_id = $this->request->post('store_id');

        if (!$store_id) {
            $this->error('缺少参数');
        }

        try {
            $withdrawals_record = WithdrawalsRecord::field('id,withdrawal_amount,createtime,status,store_id')
                ->with(['store' => function ($q) {
                    $q->withField('id,bank_card');
                }])
                ->order('id desc')
                ->where('store_id', $store_id)
                ->select();

            if ($withdrawals_record) {
                foreach ($withdrawals_record as $k => $v) {
                    $withdrawals_record[$k]['bank_info'] = BankInfo::getByStore_id($v['store_id'])->visible(['id', 'bankname']);
                    $withdrawals_record[$k]['store']['bank_card'] = substr($v['store']['bank_card'], -4);
                }
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success('请求成功', ['record' => $withdrawals_record]);
    }


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

            $data = QuotedPrice::where('id', $quoted_id)->field('models_info_id,buy_car_id,buyer_payment_status,seller_payment_status')->lock(true)->find();

            if ($data['buyer_payment_status'] != 'to_be_paid' || $data['buyer_payment_status'] != 'to_be_paid') {
                throw new Exception('一方已支付保证金，暂无法取消订单');
            }

            $update_table = $data['models_info_id'] ? new ModelsInfo() : new BuycarModel();

            $car_id = $data['models_info_id'] ? $data['models_info_id'] : $data['buy_car_id'];

            $field = $data['models_info_id'] ? 'models_info_id' : 'buy_car_id';

            QuotedPrice::where([$field => $car_id])->setField(['deal_status' => 'start_the_deal']);

            $update_table->save(['shelfismenu' => 1], ['id' => $car_id]);
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
