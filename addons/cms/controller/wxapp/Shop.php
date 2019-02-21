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

class Shop extends Base
{
    protected $noNeedLogin = '*';

//    public function index()
//    {
//
//    }

    /**
     * 提交审核店铺接口
     * @throws \think\exception\DbException
     */
    public function submit_audit()
    {
        $this->success(1);
        $user_id = $this->request->post('user_id');
        $infos = $this->request->post('auditInfo/a');
        $infos['user_id'] = $user_id;
        $infos['invitation_code'] = $this->make_coupon_card();
$this->success($infos);
        $company = new CompanyStore($infos);

        $company->allowField(true)->save();

        //用户表填入身份证等信息
        $user = User::get($user_id);

        $user->bank_card = $infos['bank_card'];
        $user->id_card_images = $infos['id_card_positive'] . ';' . $infos['id_card_opposite'];
        $user->business_licenseimages = $infos['business_licenseimages'];

        $user->save();

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
}