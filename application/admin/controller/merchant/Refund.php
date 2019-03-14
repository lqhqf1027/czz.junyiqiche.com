<?php

namespace app\admin\controller\merchant;

use app\common\controller\Backend;
use app\admin\model\PayOrder;
/**
 * 店铺认证支付订单管理
 *
 * @icon fa fa-circle-o
 */
class Refund extends Backend
{

    public function _initialize()
    {
        parent::_initialize();
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
        $this->model = new \app\admin\model\QuotedPrice;
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams('out_trade_no');
            $total = $this->model
                    ->with(['user' => function ($q) {
                        $q->withField('id,nickname,avatar,mobile');
                    },'ModelsInfo' => function ($query) {
                        $query->withField('models_name');
                    }])
                    ->where($where)
                    ->where(['buyer_payment_status' => 'confirm_receipt', 'seller_payment_status' => 'confirm_receipt'])
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user' => function ($q) {
                        $q->withField('id,nickname,avatar,mobile');
                    },'ModelsInfo' => function ($query) {
                        $query->withField('models_name');
                    }])
                    ->where($where)
                    ->where(['buyer_payment_status' => 'confirm_receipt', 'seller_payment_status' => 'confirm_receipt'])
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $k => $row) {

                $data = PayOrder::with(['store' => function ($q) {
                    
                    $q->withField(['store_name','bank_card','real_name','phone']);

                }])->where(['trading_models_id' => $row['models_info_id'], 'pay_type' => 'bond'])->select();
                
                foreach ($data as $key => $value) {

                    if ($value['seller_id']) {
                        $list[$k]['sell'] = [
                            'out_trade_no' => $value['out_trade_no'],
                            'total_fee' => $value['total_fee'],
                            'time_end' => $value['time_end'],
                            'bank_card' => $value['store']['bank_card'],
                            'real_name' => $value['store']['real_name'],
                            'phone' => $value['store']['phone'],
                        ];
                    }
                    if ($value['buyers_id']) {
                        $list[$k]['buy'] = [
                            'out_trade_no' => $value['out_trade_no'],
                            'total_fee' => $value['total_fee'],
                            'time_end' => $value['time_end'],
                            'bank_card' => $value['store']['bank_card'],
                            'real_name' => $value['store']['real_name'],
                            'phone' => $value['store']['phone'],
                        ];
                    }
                    
                }   
                
            }
            $list = collection($list)->toArray();
           
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}
