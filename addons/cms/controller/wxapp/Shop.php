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
use addons\cms\model\User;
use addons\cms\model\Distribution;
use addons\cms\model\StoreLevel;
use addons\cms\model\Config as ConfigModel;
use addons\cms\model\EarningDetailed;
use think\Cache;
use think\Db;
use think\Exception;

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

        if (!$user_id) {
            $this->error('缺少参数');
        }

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
                $inviter_level_id = CompanyStore::get(['user_id' => $inviter_user_id])->level_id;
            }

            $data = [
                'submit_type' => 'insert',
                'inviter_code' => $inviter_code,
                'store_level_list' => $this->getVisibleStoreList(empty($inviter_level_id) ? null : $inviter_level_id),
                'brand_list' => $brand
            ];

            //是否已经有店铺，并且未通过审核
            $no_pass = CompanyStore::get([
                'user_id' => $user_id,
                'auditstatus' => 'audit_failed'
            ]);

            if ($no_pass) {
                $no_pass = $no_pass->visible(['id', 'cities_name', 'store_name', 'store_address', 'phone', 'store_img', 'level_id', 'store_description', 'main_camp', 'business_life', 'bank_card', 'id_card_images', 'business_licenseimages'])->toArray();
                $no_pass['id_card_images'] = explode(',', $no_pass['id_card_images']);
                $no_pass['id_card_positive'] = $no_pass['id_card_images'][0];
                $no_pass['id_card_opposite'] = $no_pass['id_card_images'][1];
                unset($no_pass['id_card_images']);
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
    protected function getVisibleStoreList($inviter_level_id = null)
    {
        if (!Cache::get('LEVEL')) {
            $store_level = collection(StoreLevel::field('id,partner_rank,money,explain')->select())->toArray();
            Cache::set('LEVEL', $store_level);
        }

        $store_level_list = Cache::get('LEVEL');
        if ($inviter_level_id) {

            foreach ($store_level_list as $k => $v) {

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
            $inviter = User::get(['invite_code' => $code]);

            if (!$inviter) {
                $this->success('错误的邀请码', ['store_level_list' => $this->getVisibleStoreList(), 'inviter_info' => []]);
            }

            $inviter = $inviter->visible(['id', 'avatar'])->toArray();

            $company_check = CompanyStore::get(['user_id' => $inviter['id'], 'auditstatus' => 'paid_the_money']);

            if (!$company_check) {
                $this->success('该邀请码暂不可用', ['store_level_list' => $this->getVisibleStoreList(), 'inviter_info' => []]);
            }

            $company_info = CompanyStore::get(['user_id' => $inviter['id']])->visible(['store_name', 'level_id']);

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
                $this->error('手机验证码输入错误');
            }

            if (!empty($infos['code'])) {
                $inviter = User::get(['invite_code' => $infos['code']])->id;

                if (!$inviter) {
                    $this->error('输入了错误的邀请码');
                }

                $check_code = CompanyStore::get(['user_id' => $inviter, 'auditstatus' => 'paid_the_money']);

                if (!$check_code) {
                    $this->error('该店铺未实名认证,无效的邀请码');
                }

            }

            $company = new CompanyStore();

            if ($submit_type == 'insert') {
                $result = $company->allowField(true)->save($infos);
            } else {
                $infos['auditstatus'] = 'wait_the_review';
                $result = $company->allowField(true)->save($infos, ['id' => CompanyStore::get(['user_id' => $user_id])->id]);
            }

            if ($result) {
                $superior_store_id = empty($inviter) ? 0 : CompanyStore::get(['user_id' => $inviter])->id;
                $my_store_id = CompanyStore::get(['user_id' => $user_id])->id;

                $result = gets('http://bankcardsilk.api.juhe.cn/bankcardsilk/query.php?key=e0ebcb671fa0f2e181c2cc967813a9bf&num=' . $infos['bank_card']);

                if ($result['error_code'] == 0) {
                    $check_bank = Db::name('bank_info')->where('store_id', $my_store_id)->find();
                    $result['result']['logo'] = 'http://images.juheapi.com/banklogo/' . $result['result']['logo'];
                    if ($check_bank) {
                        Db::name('bank_info')->where('store_id', $my_store_id)->update($result['result']);
                    } else {
                        $result['result']['store_id'] = $my_store_id;
                        Db::name('bank_info')->insert($result['result']);
                    }

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
                $this->error('添加失败', 'error');
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

        $info = User::field('id,nickname,avatar')
            ->with(['companystoreone' => function ($q) {
                $q->withField('id,store_name,level_id,auditstatus');
            }])->find($user_id);

        if ($info) {
            $info['certification_fee'] = Db::name('store_level')->where('id', $info['companystoreone']['level_id'])->value('money');
        }

        //待支付        已支付
        $to_be_paid = $paid = [];

        if ($info['companystoreone']['auditstatus'] == 'paid_the_money') {
            $paid[] = $info;
        } else if ($info['companystoreone']['auditstatus']) {
            $to_be_paid[] = $info;
        }

        if ($to_be_paid) {
            //根据门店状态判断能否支付
            $can_pay = $to_be_paid[0]['companystoreone']['auditstatus'] == 'pass_the_audit' ? 1 : 0;

            $to_be_paid[0]['can_pay'] = $can_pay;
        }

        if ($paid) {
            //根据门店等级判断能否升级
            $can_upgrade = $paid[0]['companystoreone']['level_id'] == 1 ? 0 : 1;
            $paid[0]['can_upgrade'] = $can_upgrade;
        }

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
            $bank_info_id = Db::name('bank_info')->where('store_id', $store_id)->value('id');

            $distribution_id = Distribution::get(['level_store_id' => $store_id])->id;

            if ($distribution_id) {
                Distribution::destroy($distribution_id);
            }

            if ($bank_info_id) {
                Db::name('bank_info')->where('i', $bank_info_id)->delete();
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
     * 支付成功后接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function after_successful_payment()
    {
        $user_id = $this->request->post('user_id');

        if (!$user_id) {
            $this->error('缺少参数');
        }

        Db::startTrans();
        try {
            $company_info = CompanyStore::field('id')
                ->with(['belongsStoreLevel' => function ($q) {
                    $q->withField('id,money');
                }])->where([
                    'user_id' => $user_id,
                    'auditstatus' => 'paid_the_money'
                ])->find();

            if (!$company_info) {
                $this->error('未知错误');
            }

            //查出收益率
            $rate = ConfigModel::where('group', 'rate')->column('value');

            $check_earning = EarningDetailed::get(['store_id' => $company_info['id']]);

            //如果没有收益明细表，创建
            if (!$check_earning) {
                EarningDetailed::create(['store_id' => $company_info['id']]);
            }

            //能获取的1级收益
            $first_income = $company_info['belongs_store_level']['money'] * floatval($rate[0]);
            //能获取的2级收益
            $second_income = $company_info['belongs_store_level']['money'] * floatval($rate[1]);

            Distribution::where('level_store_id', $company_info['id'])->update([
                'earnings' => $first_income,
                'second_earnings' => $second_income
            ]);

            $up_id = Distribution::get(['level_store_id' => $company_info['id']])->store_id;
            if ($up_id) {
                //加锁查询上级的金额信息
                $up_data = EarningDetailed::field('first_earnings,total_earnings')->where('store_id', $up_id)->lock(true)->select();
                //如果有上级，将上级的收益加入上级收益明细表中
                EarningDetailed::where('store_id', $up_id)
                    ->update(['first_earnings' => $up_data['first_earnings'] + $first_income,
                        'total_earnings' => $up_data['total_earnings'] + $first_income]);

                $up_up_id = Distribution::get(['level_store_id' => $up_id])->store_id;

                if ($up_up_id) {
                    //加锁查询上上级的金额信息
                    $up_up_data = EarningDetailed::field('second_earnings,total_earnings')->where('store_id', $up_id)->lock(true)->select();
                    //如果有上上级，将上级的收益加入上上级收益明细表中
                    EarningDetailed::where('store_id', $up_up_id)
                        ->update(['second_earnings' => $up_up_data['second_earnings'] + $second_income,
                            'total_earnings' => $up_up_data['total_earnings'] + $second_income]);
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('请求成功');
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
        $store_id = $this->request->post('store_id');

        if (!$store_id) {
            $this->error('缺少参数');
        }

        $store_info = CompanyStore::get($store_id)->visible(['id', 'cities_name', 'store_name', 'store_address', 'phone', 'main_camp', 'store_img', 'store_description'])->toArray();

        $car_list = Index::typeCar(1, 0, ['store_id' => $store_id]);

        $store_info['car_list'] = $car_list;

        $this->success('请求成功', ['detail' => $store_info]);
    }

    public function cash_withdrawal()
    {

        $user_id = $this->request->post('user_id');

        $company_id = CompanyStore::get(['user_id' => $user_id])->id;

        $all_money = EarningDetailed::get(['store_id' => $company_id])->total_earnings;

        $bank_info = Db::name('bank_info')
            ->where('store_id', $company_id)
            ->select();

        $this->success('请求成功', ['total_money' => $all_money, 'bank_info' => $bank_info]);

    }


}