<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/30
 * Time: 17:22
 */

namespace addons\cms\controller\wxapp;

use think\Cache;
use think\Db;
use addons\cms\model\User;
class Carselect extends Base
{
    protected $noNeedLogin = '*';

    /**
     * 严选车源
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $screen = $this->request->post('screen');

        if (!$screen) {
            $this->error('缺少参数');
        }

        Cache::rm('CAR_LIST');

        if (!Cache::get('CAR_LIST')) {

            Cache::set('CAR_LIST', self::getCarCache());
        }
        $alls = Cache::get('CAR_LIST');

        $carLists = $alls['carList'];


        $city = $this->request->post('city');
        $brand_id = $this->request->post('brand_id');

        $realCarList = [];
        if ($city || $brand_id) {
            foreach ($carLists as $k => $v) {
                if ($city && !$brand_id) {
                    if ($v['parkingposition'] == $city) {
                        $realCarList[] = $v;
                    }
                } else if (!$city && $brand_id) {
                    if ($v['brand']['id'] == $brand_id) {
                        $realCarList[] = $v;
                    }
                } else if ($city && $brand_id) {
                    if ($v['parkingposition'] == $city && $v['brand']['id'] == $brand_id) {
                        $realCarList[] = $v;
                    }
                }
            }
        } else {
            $realCarList = $carLists;
        }

        if ($realCarList && $screen) {
            $field = null;
            switch ($screen) {
                case 1:
                    $field = 'createtime';
                    break;
                case 2:
                    $field = 'kilometres';
                    break;
                case 3:
                    $field = 'guide_price';
                    break;
            }

            //二维数组根据某个字段升序或者降序排列
            $realCarList = list_sort_by($realCarList, $field, $screen == 1 ? 'desc' : 'asc');
        }


        $this->success('请求成功', [
            'city' => $alls['city'],
            'brand' => $alls['brand'],
            'carList' => $realCarList
        ]);
    }

    /**
     * 清除严选车源缓存
     */
    public function rmCacheCar_list()
    {

        Cache::rm('CAR_LIST');
    }

    public static function getCarCache()
    {
        $modelsInfoList = Index::typeCar(1);
        $buyCarModelList = Index::typeCar(2);

        $all = array_merge($modelsInfoList, $buyCarModelList);

        $cityList = $brandList = $brandNameList = [];

        foreach ($all as $k => $v) {
            //去重得到城市
            if (!in_array($v['parkingposition'], $cityList)) {
                $cityList[] = $v['parkingposition'];
            }

            //根据品牌名去重并加入品牌数组
            if (!in_array($v['brand']['id'], $brandNameList)) {
                $brandNameList[] = $v['brand']['id'];

                if (!$brandList) {
                    $brandList[] = ['zimu' => $v['brand']['bfirstletter'], 'brand_list' => [['id' => $v['brand']['id'], 'name' => $v['brand']['name']]]];
                } else {
                    $flag = -1;
                    foreach ($brandList as $key => $value) {
                        if ($v['brand']['bfirstletter'] == $value['zimu']) {
                            $brandList[$key]['brand_list'][] = ['id' => $v['brand']['id'], 'name' => $v['brand']['name']];
                            $flag = -2;
                        }
                    }

                    if ($flag == -1) {
                        $brandList[] = ['zimu' => $v['brand']['bfirstletter'], 'brand_list' => [['id' => $v['brand']['id'], 'name' => $v['brand']['name']]]];
                    }

                }

            }

//            $all[$k][]

        }
        //二维数组根据某个字段a-z顺序排列数组
        array_multisort(array_column($brandList, 'zimu'), SORT_ASC, $brandList);


        foreach ($cityList as $k => $v) {
            $cityList[$k] = ['name' => $v];
        }

        $arr = [
            'city' => $cityList,
            'brand' => $brandList,
            'carList' => $all
        ];

        return $arr;
    }


    public function test()
    {
        User::where('id',5)->setInc('store_id');
    }
}