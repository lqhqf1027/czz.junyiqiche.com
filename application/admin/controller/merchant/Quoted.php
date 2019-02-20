<?php

namespace app\admin\controller\merchant;

use app\common\controller\Backend;
use app\admin\model\ModelsInfo;
use app\admin\model\BuycarModel;
use app\admin\model\User;

/**
 * 报价管理
 *
 * @icon fa fa-circle-o
 */
class Quoted extends Backend
{
    
    /**
     * Price模型对象
     * @var \app\admin\model\quoted\Price
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\QuotedPrice;
        $this->view->assign("typeList", $this->model->getTypeList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['user', 'ModelsInfo', 'BuycarModel'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user', 'ModelsInfo', 'BuycarModel'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $key => $row) {
                
                $row->getRelation('user')->visible(['nickname','mobile','avatar']);
                
                if ($row['buy_car_id']) {

                    $list[$key]['models_info']['user_id'] = $row['buycar_model']['user_id'];
                    $list[$key]['models_info']['models_name'] = $row['buycar_model']['models_name'];
                    $list[$key]['models_info']['kilometres'] = $row['buycar_model']['kilometres'];
                    $list[$key]['models_info']['license_plate'] = $row['buycar_model']['license_plate'];
                    $list[$key]['models_info']['parkingposition'] = $row['buycar_model']['parkingposition'];
                    
                }  
                
                $by_user = User::where('id', $row['models_info']['user_id'])->find();
                $list[$key]['by_user'] = $by_user;
            }

            // //查询models_info_id存在的
            // $ModelsInfoList = collection($this->model->field('id,user_ids,models_info_id,money,quotationtime,type')
            //     ->with(['ModelsInfo'=>function ($q) {
            //         $q->withField('id,models_name,guide_price,user_id,shelfismenu,car_licensetime,kilometres,parkingposition,browse_volume,createtime,modelsimages,brand_id');
            //     },
            //     'user'=>function ($q){
            //         $q->withField('id,nickname,avatar,mobile');
            //     }])
            //     ->where($where)->order($sort, $order)->limit($offset, $limit)->select())->toArray();

            // //查询buy_car_id存在的
            // $BuycarModelList = collection($this->model->field('id,user_ids,money,quotationtime,type,buy_car_id')
            //     ->with(['BuycarModel'=>function ($q){
            //         $q->withField('id,models_name,guide_price,shelfismenu,car_licensetime,kilometres,parkingposition,browse_volume,createtime,brand_id');
            //     },
            //     'user'=>function ($q){
            //         $q->withField('id,nickname,avatar,mobile');
            // }])
            // ->where($where)->order($sort, $order)->limit($offset, $limit)->select())->toArray();

            // //合并
            // $list = array_merge($ModelsInfoList, $BuycarModelList);
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}
