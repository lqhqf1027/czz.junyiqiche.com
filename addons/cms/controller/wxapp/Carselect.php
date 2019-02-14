<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/30
 * Time: 17:22
 */

namespace addons\cms\controller\wxapp;


class Carselect extends Base
{
    protected $noNeedLogin = '*';

    public function index()
    {
        //buy,sell,clue
//        $screening_conditions = $this->request->post('screening_conditions');

//        $type = 0;
//        switch ($screening_conditions) {
//            case 'sell':
//                $type = 1;
//                break;
//            case 'buy':
//                $type = 2;
//                break;
//            case 'clue':
//                $type = 3;
//                break;
//            default:
//                $this->error('不符合要求的参数','error');
//                break;
//        }
//
//        $carList = Index::typeCar($type,1);
//
//        $this->success('请求成功',['carList'=>$carList]);
        $modelsInfoList = Index::typeCar(1);
        $buycarModelList = Index::typeCar(2);
        $clueList = Index::typeCar(3);



        $all = array_merge($modelsInfoList,$buycarModelList,$clueList);

        $cityList = $brandList = $brandNameList = [];
        foreach ($all as $k=>$v){
            if(!in_array($v['parkingposition'],$cityList)){
                $cityList[] = $v['parkingposition'];
            }

            if(!in_array($v['brand']['id'],$brandNameList)){
                $brandNameList[] = $v['brand']['id'];

                if(!$brandList){
                    $brandList[] =['zimu'=>$v['brand']['bfirstletter'],'brand_list'=>[['id'=>$v['brand']['id'],'name'=>$v['brand']['name']]]];
                }else{
                    foreach ($brandList as $key=>$value){
                        if($v['brand']['bfirstletter'] == $value['zimu']){
                            $brandList[$key]['brand_list'][] = ['id'=>$v['brand']['id'],'name'=>$v['brand']['name']];
                        }
                    }
                }



            }

        }

        $arr = [
            'carList'=>[
                'sell'=>$modelsInfoList,
                'buy'=>$buycarModelList,
                'clue'=>$clueList
            ]
        ];
$this->success('请求成功',$arr);

        foreach ($cityList as $k=>$v){
            $cityList[$k] = ['name'=>$v];
        }

        $this->success($brandList);

        $this->success(['a'=>$modelsInfoList,'b'=>$buycarModelList,'c'=>$clueList]);

    }
}