<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/2/21
 * Time: 11:14
 */

namespace addons\cms\controller\wxapp;

use addons\cms\model\Brand;
use addons\cms\model\CompanyStore;
use addons\cms\model\ModelsInfo;
use addons\cms\model\PayOrder;
use addons\cms\model\User;
use addons\cms\model\Distribution;
use addons\cms\model\StoreLevel;
use addons\cms\model\Config as ConfigModel;
use addons\cms\model\EarningDetailed;
use addons\cms\model\BankInfo;
use addons\cms\model\WithdrawalsRecord;
use think\Cache;
use think\Db;
use think\Exception;
use think\exception\PDOException;

class Shop extends Base
{
    protected $noNeedLogin = '*';

    /**
     * 店铺认证数据接口
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $user_id = $this->request->post('user_id');
        $inviter_user_id = $this->request->post('inviter_user_id');//邀请人user_id

//        if (!$user_id) {
//            $this->error('缺少参数');
//        }

        try {
            //得到品牌列表
            if (!Cache::get('brandCate')) {
                Cache::set('brandCate', $this->getBrandList());
            }
            $brand = Cache::get('brandCate');

            //如果传入邀请人ID，获取邀请人的二维码
            $inviter_code = '';

            if ($inviter_user_id) {
                $inviter_code = User::get($inviter_user_id)->invite_code;

                $inviter_level_id = CompanyStore::getByUser_id($inviter_user_id)->level_id;

            }

            $data = [
                'submit_type' => 'insert',
                'inviter_code' => $inviter_code,
                'store_level_list' => self::getVisibleStoreList(empty($inviter_level_id) ? null : $inviter_level_id),
                'brand_list' => $brand,

            ];

            //是否已经有店铺，并且未通过审核
            $no_pass = CompanyStore::get([
                'user_id' => $user_id,
                'auditstatus' => 'audit_failed'
            ]);

            if ($no_pass) {

                $no_pass = $no_pass->visible(['id', 'cities_name', 'store_name', 'store_address', 'phone', 'store_img',
                    'level_id', 'store_description', 'main_camp', 'business_life', 'bank_card', 'id_card_images',
                    'business_licenseimages', 'real_name'])->toArray();
                $no_pass['id_card_images'] = explode(',', $no_pass['id_card_images']);
                $no_pass['id_card_positive'] = $no_pass['id_card_images'][0];
                $no_pass['id_card_opposite'] = $no_pass['id_card_images'][1];
                unset($no_pass['id_card_images']);

                $up_store_id = Distribution::get(['level_store_id' => $no_pass['id']])->store_id;
                if ($up_store_id) {
                    $data['inviter_code'] = User::get(CompanyStore::get($up_store_id)->user_id)->invite_code;
                }

                $data['submit_type'] = 'update';
                $data['fail_default_value'] = $no_pass;
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }


        $this->success('请求成功', $data);
    }

    /**
     * 品牌列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBrandList()
    {
        $brandList = collection(Brand::field('id,name,brand_initials')->where('pid', 0)->select())->toArray();

        $screen_data = [];
        foreach ($brandList as $k => $v) {
            $screen_data[$v['brand_initials']][] = ['id' => $v['id'], 'name' => $v['name']];
        }

        return $screen_data;
    }

    /**
     * 得到店铺认证类型列表
     * @param null $inviter_level_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getVisibleStoreList($inviter_level_id = null, $upgrade = 0)
    {
        if (!Cache::get('LEVEL')) {
            $store_level = collection(StoreLevel::field('id,partner_rank,money,explain')->select())->toArray();
            Cache::set('LEVEL', $store_level);
        }

        $store_level_list = Cache::get('LEVEL');
        if ($inviter_level_id) {

            foreach ($store_level_list as $k => $v) {

                if ($upgrade) {
                    if ($v['id'] >= $inviter_level_id) {
                        $store_level_list[$k]['condition'] = 'disabled';
                    }

                    continue;
                }

                if ($v['id'] < $inviter_level_id) {
                    $store_level_list[$k]['condition'] = 'disabled';
                }

            }

        }

        return $store_level_list;
    }

    /**
     * 核对填写的邀请码接口
     * @throws \think\exception\DbException
     */
    public function check_the_invitation_code()
    {
        //输入的邀请码
        $code = $this->request->post('code');

        try {
            $inviter = User::getByInvite_code($code);

            if (!$inviter) {
                $this->success('错误的邀请码', ['store_level_list' => $this->getVisibleStoreList(), 'inviter_info' => []]);
            }

            $inviter = $inviter->visible(['id', 'avatar'])->toArray();

            $company_check = CompanyStore::get(['user_id' => $inviter['id'], 'auditstatus' => 'paid_the_money']);

            if (!$company_check) {
                $this->success('该邀请码暂不可用', ['store_level_list' => $this->getVisibleStoreList(), 'inviter_info' => []]);
            }

            $company_info = CompanyStore::getByUser_id($inviter['id'])->visible(['store_name', 'level_id']);

            $inviter['store_name'] = $company_info['store_name'];
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success('已匹配到邀请码', ['store_level_list' => $this->getVisibleStoreList($company_info['level_id']), 'inviter_info' => $inviter]);
    }

    /**
     * 提交审核店铺接口
     * @throws \think\exception\DbException
     */
    public function submit_audit()
    {
        $user_id = $this->request->post('user_id');
        $infos = $this->request->post('auditInfo/a');

        if (!$user_id) {
            $this->error('缺少参数');
        }

        $submit_type = $this->request->post('submit_type');   //表单提交类型【insert/update】
        $infos['user_id'] = $user_id;
        $infos['id_card_images'] = $infos['id_card_positive'] . ',' . $infos['id_card_opposite'];

        if (!check_bankCard($infos['bank_card'])) {
            $this->error('错误的银行卡号');
        }

        Db::startTrans();
        try {
            $check_phone = Db::name('cms_login_info')
                ->where([
                    'user_id' => $user_id,
                    'login_state' => 0,
                    'login_code' => $infos['login_code']
                ])
                ->find();

            if (!$check_phone) {
                throw new Exception('手机验证码输入错误');
            }

            if (!empty($infos['code'])) {
                $inviter = User::get(['invite_code' => $infos['code']])->id;

                if (!$inviter) {
                    throw new Exception('输入了错误的邀请码');
                }

                $check_code = CompanyStore::get(['user_id' => $inviter, 'auditstatus' => 'paid_the_money']);

                if (!$check_code) {
                    throw new Exception('该店铺未实名认证,无效的邀请码');
                }

            }

            $company = new CompanyStore();

            if ($submit_type == 'insert') {
                $result = $company->allowField(true)->save($infos);
            } else {
                $infos['auditstatus'] = 'wait_the_review';
                $infos['reasons_failure'] = null;
                $result = $company->allowField(true)->save($infos, ['id' => CompanyStore::get(['user_id' => $user_id])->id]);
            }

            if ($result) {
                $superior_store_id = empty($inviter) ? 0 : CompanyStore::get(['user_id' => $inviter])->id;
                $my_store_id = CompanyStore::get(['user_id' => $user_id])->id;

                $result = gets('http://apis.juhe.cn/bankcardcore/query?key=c1eb4e7f42babb67e82559c87222472b&bankcard=' . $infos['bank_card']);
                if ($result['error_code'] == 0) {
                    $bank = new BankInfo();
                    $check_bank = BankInfo::get(['store_id' => $my_store_id])->id;
                    if ($check_bank) {
                        $bank->allowField(true)->save($result['result'], ['id' => $check_bank]);
                    } else {
                        $result['result']['store_id'] = $my_store_id;
                        $bank->allowField(true)->save($result['result']);
                    }

                } else {
                    throw new Exception('银行卡信息有误');
                }

                if ($submit_type == 'insert') {
                    Distribution::create([
                        'store_id' => $superior_store_id,
                        'level_store_id' => $my_store_id,
                        'earnings' => 0,
                        'second_earnings' => 0
                    ]);
                } else {
                    Distribution::where('level_store_id', $my_store_id)->setField('store_id', $superior_store_id);
                }

            } else {
                throw new Exception('添加失败');
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('请求成功', 'success');

    }

    /**
     * 我的订单接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function my_order()
    {
        $user_id = $this->request->post('user_id');

        if (!$user_id) {
            $this->error('缺少参数');
        }

        try {

            $to_be_paid = User::field('id,nickname,avatar')
                ->with(['companystoreone' => function ($q) {
                    $q->where('auditstatus', 'neq', 'paid_the_money')->withField('id,store_name,level_id,auditstatus,createtime');
                }])->select($user_id);
            if ($to_be_paid) {
                $level_info = Db::name('store_level')->where('id', $to_be_paid[0]['companystoreone']['level_id'])->field('partner_rank,money')->find();
                $to_be_paid[0]['certification_fee'] = $level_info['money'];
                $to_be_paid[0]['level_name'] = $level_info['partner_rank'];
                $to_be_paid[0]['companystoreone']['createtime'] = format_date($to_be_paid[0]['companystoreone']['createtime']);
                //根据门店状态判断能否支付
                $can_pay = $to_be_paid[0]['companystoreone']['auditstatus'] == 'pass_the_audit' ? 1 : 0;

                $to_be_paid[0]['can_pay'] = $can_pay;
            }

            $paid = PayOrder::field('id,time_end,total_fee')
                ->with([
                    'user' => function ($q) {
                        $q->withField('id,nickname,avatar');
                    },
                    'companyStore' => function ($q) {
                        $q->withField('id,store_name,auditstatus');
                    },
                    'level' => function ($q) {
                        $q->withField('id,partner_rank,money');
                    }
                ])
                ->where([
                    'pay_order.user_id' => $user_id,
                    'pay_type' => ['neq', 'bond']
                ])->order('id desc')->select();

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }


//        if ($paid) {
//            //根据门店等级判断能否升级
//            $can_upgrade = $paid[0]['companystoreone']['level_id'] == 1 ? 0 : 1;
//            $paid[0]['can_upgrade'] = $can_upgrade;
//            $pay_order = PayOrder::get(['user_id' => $user_id, 'pay_type' => '']);
//            $paid[0]['payment_time'] = $pay_order ? date('Y-m-d H:i:s', strtotime($pay_order->time_end)) : '';
//        }

        $this->success('请求成功', ['to_be_paid' => $to_be_paid, 'paid_the_money' => $paid]);

    }

    /**
     * 取消订单
     */
    public function cancellation_order()
    {
        $store_id = $this->request->post('store_id');

        if (!$store_id) {
            $this->error('缺少参数,请求失败', 'error');
        }
        Db::startTrans();
        try {
            $bank_info_id = Db::name('bank_info')->where('store_id', $store_id)->lock(true)->value('id');

            $distribution_id = Distribution::get(['level_store_id' => $store_id])->id;

            if ($distribution_id) {
                Distribution::destroy($distribution_id);
            }

            if ($bank_info_id) {
                Db::name('bank_info')->where('id', $bank_info_id)->delete();
            }
            $res = CompanyStore::destroy($store_id);
            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        !empty($res) ? $this->success('取消成功', 'success') : $this->error('取消失败', 'error');

    }


    /**
     * 店铺详情接口
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function store_detail()
    {
        $user_id = $this->request->post('user_id');

        $store_id = $this->request->post('store_id');

        if (!$user_id) {
            $this->error('缺少参数');
        }

        $is_own = 0;

        $user_store_id = CompanyStore::getByUser_id($user_id)->id;

        if ($user_store_id) {
            if ($user_store_id == $store_id) {
                $is_own = 1;
            }
        }

        $store = CompanyStore::get([
            'user_id' => $user_id,
            'auditstatus' => 'paid_the_money'
        ]);

        $store_info = CompanyStore::field(['id', 'cities_name', 'store_name', 'store_address', 'phone', 'main_camp', 'store_img', 'store_description', 'auditstatus', 'level_id'])
            ->with(['storelevel' => function ($q) {
                $q->withField('id,partner_rank');
            }])
            ->where('user_id', $user_id)->find();

        if (!$store) {
            $this->error('门店未找到或未完成认证');
        }

        $car_list = ModelsInfo::useGlobalScope(false)->field('id,models_name,guide_price,car_licensetime,kilometres,parkingposition,browse_volume,createtime,store_description,factorytime,modelsimages,shelfismenu')
            ->with(['brand' => function ($q) {
                $q->withField('id,name,brand_initials,brand_default_images');
            }])
            ->where('store_id', $store_info['id'])->order('createtime desc')->select();

        if ($car_list) {
            $car_list = collection($car_list)->toArray();
            $default_image = self::$default_image;
            foreach ($car_list as $k => $v) {

                $car_list[$k]['kilometres'] = $v['kilometres'] ? floatval(round($v['kilometres'] / 10000, 2)) . '万公里' : null;
                $car_list[$k]['guide_price'] = $v['guide_price'] ? floatval(round($v['guide_price'] / 10000, 2)) . '万' : null;

                $car_list[$k]['modelsimages'] = !empty($v['modelsimages']) ? explode(',', $v['modelsimages'])[0] : $default_image;

                $car_list[$k]['car_licensetime'] = $v['car_licensetime'] ? date('Y-m', $v['car_licensetime']) : null;

//                $car_list[$k]['factorytime'] = $v['factorytime'] ? date('Y', $v['factorytime']) : '';

            }
        }

        $store_info['car_list'] = $car_list;

        $this->success('请求成功', ['detail' => $store_info, 'is_own' => $is_own]);
    }

    /**
     * 提现收益接口
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function cash_withdrawal()
    {
        $user_id = $this->request->post('user_id');

        if (!$user_id) {
            $this->error('缺少参数');
        }

        try {
            $company_info = CompanyStore::getByUser_id($user_id)->visible(['id', 'bank_card']);

            $all_money = EarningDetailed::getByStore_id($company_info['id'])->available_balance;

            $bank_info = BankInfo::getByStore_id($company_info['id']);

            if (!$bank_info) {
                throw new Exception('未查到银行卡信息');
            }
            $bank_info = $bank_info->hidden(['store_id'])->toArray();

            $bank_info['cardtype'] = $bank_info['cardtype'] == '借记卡' ? '储蓄卡' : '信用卡';

            $bank_info['last_number'] = substr($company_info['bank_card'], -4);

            //服务费率
            $presentation_rate = ConfigModel::getByName('presentation_rate')->value;
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success('请求成功', ['total_money' => $all_money, 'presentation_rate' => $presentation_rate, 'bank_info' => $bank_info]);

    }

    /**
     * 点击确认提现接口
     */
    public function check_money()
    {
        $money = $this->request->post('money');
        $user_id = $this->request->post('user_id');

        if (!$user_id || !$money) {
            $this->error('缺少参数');
        }

        Db::startTrans();
        try {
            if (!is_numeric($money) || $money <= 0) {
                throw new Exception('不合法的类型');
            }

            //提现金额
            $money = floatval($money);

            $store_id = CompanyStore::getByUser_id($user_id)->id;

            $earning_detailed_id = EarningDetailed::getByStore_id($store_id)->id;

            $balance = EarningDetailed::where('id', $earning_detailed_id)->lock(true)->value('available_balance');

            if ($money > $balance) {
                throw new Exception('提现金额不能超过可用余额');
            }

            //费率
            $rate = ConfigModel::getByName('presentation_rate')->value;

            //服务费
            $service_charge = $money * floatval($rate);

            //实际金额
            $actual_amount = $money - $service_charge;

            $res = EarningDetailed::where('store_id', $store_id)->setDec('available_balance', $money);

            if ($res) {
                WithdrawalsRecord::create([
                    'withdrawal_amount' => $money,
                    'store_id' => $store_id,
                    'service_charge' => $service_charge,
                    'actual_amount' => $actual_amount
                ]);
            } else {
                throw new Exception('提现失败');
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('申请提现成功', 'success');
    }


}