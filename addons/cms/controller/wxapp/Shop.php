<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/2/21
 * Time: 11:14
 */

namespace addons\cms\controller\wxapp;

use addons\cms\model\CompanyStore;
use addons\cms\model\User;
use addons\cms\model\Distribution;
use addons\cms\model\StoreLevel;
use think\Cache;
use think\Db;

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

        //得到品牌列表
        if (!Cache::get('brandCate')) {
            Cache::set('brandCate', Index::brand());
        }
        $brand = Cache::get('brandCate');

        //如果传入邀请人ID，获取邀请人的二维码
        $inviter_code = '';
        Cache::rm('LEVEL');
        //店铺等级
        if (!Cache::get('LEVEL')) {
            $store_level = collection(StoreLevel::field('id,partner_rank,money,explain')->select())->toArray();
            Cache::set('LEVEL', $store_level);
        }

        $store_level_list = Cache::get('LEVEL');
        if ($inviter_user_id) {
            $inviter_code = User::get($inviter_user_id)->invite_code;
            $inviter_level_id = CompanyStore::get(['user_id' => $inviter_user_id])->level_id;

            foreach ($store_level_list as $k => $v) {

                if ($v['id'] < $inviter_level_id) {
                    $store_level_list[$k]['condition'] = 'disabled';
                }

            }

        }

        $data = [
            'submit_type' => 'insert',
            'inviter_code' => $inviter_code,
            'store_level_list' => $store_level_list,
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

        $this->success('请求成功', $data);
    }

    /**
     * 提交审核店铺接口
     * @throws \think\exception\DbException
     */
    public function submit_audit()
    {
        $user_id = $this->request->post('user_id');
        $infos = $this->request->post('auditInfo/a');
        $submit_type = $this->request->post('submit_type');   //表单提交类型【insert/update】
        $infos['user_id'] = $user_id;
        $infos['id_card_images'] = $infos['id_card_positive'] . ',' . $infos['id_card_opposite'];

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
            $inviter = User::where('invite_code', ['neq', User::get($user_id)->invite_code], ['eq', $infos['code']])->find();

            if (!$inviter) {
                $this->error('输入了错误的或者自己的邀请码');
            }

        }

        $company = new CompanyStore();

        if ($submit_type == 'insert') {
            $company->allowField(true)->save($infos);
        } else {
            $infos['auditstatus'] = 'wait_the_review';
            $company->allowField(true)->save($infos, ['id' => CompanyStore::get(['user_id' => $user_id])->id]);
        }


//        if (!empty($infos['code'])) {
//            Distribution::create([
//                'store_id' => CompanyStore::get(['invitation_code' => $infos['code']])->id,
//                'level_store_id' => $company->id,
//                'earnings' => 0,
//                'second_earnings' => 0
//            ]);
//        }

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

        $info = User::field('id,nickname,avatar')
            ->with(['companystoreone' => function ($q) {
                $q->withField('id,level_id,auditstatus');
            }])->find($user_id);

        if ($info) {
            $info['certification_fee'] = Db::name('store_level')->where('id', $info['companystoreone']['level_id'])->value('money');
        }

        $to_be_paid = $paid = [];

        if ($info['companystoreone']['auditstatus'] == 'paid_the_money') {
            $paid[] = $info;
        } else if ($info['companystoreone']['auditstatus']) {
            $to_be_paid[] = $info;
        }

        if ($to_be_paid) {
            $can_pay = $to_be_paid[0]['companystoreone']['auditstatus'] == 'pass_the_audit' ? 1 : 0;

            $to_be_paid[0]['can_pay'] = $can_pay;
        }

        if ($paid) {
            $can_upgrade = $paid[0]['companystoreone']['level_id'] == 1 ? 0 : 1;
            $paid[0]['can_upgrade'] = $can_upgrade;
        }

        $this->success('请求成功', ['to_be_paid' => $to_be_paid, 'paid_the_money' => $paid]);

    }
}