<?php

namespace addons\cms\controller\wxapp;

use addons\cms\model\Archives;
use addons\cms\model\Block;
use addons\cms\model\Channel;
use addons\cms\model\Collection;
use addons\cms\model\Distribution;
use addons\cms\model\StoreUser;
use addons\cms\model\CompanyStore;
use addons\cms\model\StoreLevel;
use addons\cms\model\Config;
use app\common\model\Addon;
use think\Cache;
use think\Db;


/**
 * 首页
 */
class Index extends Base
{

    protected $noNeedLogin = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 首页
     */
    public function index()
    {
       $bannerList = [];
       $list = Block::getBlockList(['name' => 'focus']);
       foreach ($list as $index => $item) {
          
           $bannerList[] = ['image' => cdnurl($item['image'], true), 'url' => '/', 'title' => $item['title']];
       }  

        //等级列表
        $levelList = collection(StoreLevel::all(function ($q) {
            $q->field('id,partner_rank,money,explain');
        }))->toArray();

        foreach ($levelList as $k => $v) {
            $v['money'] = floatval($v['money']) / 10000;

            $v['money'] = round($v['money'], 1);

            if ($v['money'] >= 1) {
                $v['money'] = $v['money'] . '万';
            } else {
                $v['money'] = ($v['money'] * 10) . '千';
            }

            $levelList[$k]['money'] = $v['money'];

        }

        //所有可用的邀请码
        $codeList = CompanyStore::column('invitation_code');

        $this->success('请求成功',['levelList'=>$levelList,'bannerList'=>$bannerList]);

    }


    /**
     * 支付成功后接口
     * @throws \think\exception\DbException
     */
    public function userInfo()
    {

//        $arr = [
//            'name' => 'kp',
//            'phone' => '18683787363',
//            'store_address' => '成都',
//            'bank_card' => '6217003810028413121',
//            'id_card_images' => [
//                'positive' => '/uploads/20181116/c25d72c53440bd4580923a91e083b189.png',
//                'negative' => '/uploads/20181116/c25d72c53440bd4580923a91e083b189.png',
//            ],
//            'level_id' => 2,
//            'parent_id' => 15
//        ];


        $infos = $this->request->post('info');
        $infos = "{\"name\":\"qqqww\",\"phone\":\"18683787363\",\"store_address\":\"\\u6210\\u90fd\",\"bank_card\":\"6217003810028413121\",\"id_card_images\":{\"positive\":\"\\/uploads\\/20181116\\/c25d72c53440bd4580923a91e083b189.png\",\"negative\":\"\\/uploads\\/20181116\\/c25d72c53440bd4580923a91e083b189.png\"},\"level_id\":1,\"parent_id\":3}";
        $infos = json_decode($infos, true);
        $usersInfo = StoreUser::create([
            'name' => $infos['name'],
            'bank_card' => $infos['bank_card'],
            'id_card_images' => $infos['id_card_images']['positive'] . ';' . $infos['id_card_images']['negative']
        ]);

        $company = CompanyStore::create([
            'phone' => $infos['phone'],
            'store_address' => $infos['store_address'],
            'invitation_code' => 'QdU15',
            'store_user_id' => $usersInfo->id,
            'level_id' => $infos['level_id']
        ]);


        if (!empty($infos['parent_id'])) {
            $money = StoreLevel::get($infos['level_id'])->money;
            $gradeOne = Config::where(['group' => 'rate'])->column('value');

            $profit = floatval($gradeOne[0]) * $money;

            $second_profit = floatval($gradeOne[1]) * $money;

            Distribution::create([
                'store_id' => $infos['parent_id'],
                'level_store_id' => $company->id,
                'earnings' => $profit,
                'second_earnings' => $second_profit
            ]);
        }

        $this->success('请求成功', 'success');


    }

    /**
     * 支付后的页面接口
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function homepages()
    {
        $user_id = $this->request->post('user_id');

        $users = StoreUser::field('id,name,avatar')
            ->with(['companystoreone' => function ($q) {
                $q->withField('id,store_qrcode');
            }])->find($user_id);

        $partner = StoreUser::field('id,name,avatar')->with(['companystore' => function ($q) use ($users) {
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

//            unset($partner[$k]['id_card'],$partner[$k]['sex'],$partner[$k]['store_id'],$partner[$k]['bank_card'],
//                $partner[$k]['id_card_images'],$partner[$k]['createtime'],$partner[$k]['updatetime']);
        }
        $users['allProfit'] = $commission;
        $users['memberNumber'] = $people_number;

        $data = [
            'userInfo' => $users,
            'memeberList' => $partner
        ];

        $this->success($data);

    }


}
