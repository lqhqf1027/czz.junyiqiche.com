<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/30
 * Time: 17:22
 */

namespace addons\cms\controller\wxapp;
use think\Cache;

class Carselect extends Base
{
    protected $noNeedLogin = '*';

    public function index()
    {

        $modelsInfoList = Index::typeCar(1);
        $buycarModelList = Index::typeCar(2);


        $all = array_merge($modelsInfoList, $buycarModelList);
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

        }
        //二维数组根据某个字段a-z顺序排列数组
        array_multisort(array_column($brandList, 'zimu'), SORT_ASC, $brandList);

        foreach ($cityList as $k => $v) {
            $cityList[$k] = ['name' => $v];
        }

        $arr = [
            'city' => $cityList,
            'brand' => $brandList,
            'carList' => [
                'sell' => $modelsInfoList,
//                'buy' => $buycarModelList,
            ]
        ];

        $this->success('请求成功', $arr);

    }

    /**
     * 清除严选车源缓存
     */
    public  function rmCacheCar_list(){

        Cache::rm('CAR_LIST');
    }
}