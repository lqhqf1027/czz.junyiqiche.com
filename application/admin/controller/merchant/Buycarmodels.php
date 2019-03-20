<?php

namespace app\admin\controller\merchant;

use app\common\controller\Backend;
use app\admin\model\BuycarModel;
use app\admin\model\CompanyStore;
use app\admin\model\QuotedPrice;
use app\admin\model\User;

/**
 * 店铺想买的二手车型
 *
 * @icon fa fa-circle-o
 */
class Buycarmodels extends Backend
{
    
    /**
     * Model模型对象
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\User;

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
        // $this->relationSearch = true;
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
                    ->with(['buycar' => function ($q) {

                        $q->with(['store']);

                    }])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model 
                    ->field(['id,nickname,avatar'])
                    ->with(['buycar' => function ($q) {

                        $q->field(['id,store_id,user_id'])->with(['store' => function ($q) {
                            $q->withField(['id,cities_name,store_name,store_address,phone']);
                        }]);

                    }])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $k => $row) {

                $list[$k]['cities_name'] = $row['buycar'][0]['store']['cities_name'];
                $list[$k]['store_name'] = $row['buycar'][0]['store']['store_name'];
                $list[$k]['store_address'] = $row['buycar'][0]['store']['store_address'];
                $list[$k]['phone'] = $row['buycar'][0]['store']['phone'];
                //店铺想买车型数量
                $list[$k]['buycount'] = BuycarModel::where('store_id', $row['buycar'][0]['store']['id'])->count();
                
            }
            $list = collection($list)->toArray();
            // pr($list);
            // die;
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 用户想买车型
     */
    public function buymodels($ids = null)
    {
        $this->model = model('BuycarModel');
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
                    ->with(['brand'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['brand'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $key => $row) {
                
                $row->getRelation('brand')->visible(['name']);
                //收到报价次数
                $list[$key]['count'] = QuotedPrice::where(['buy_car_id' => $row['id']])->count();
                //用户昵称
                $list[$key]['nickname'] = User::where('id', $row['user_id'])->value('nickname');

            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('user_id', $ids);
        $nickname = User::where('id', $ids)->value('nickname');
        $this->view->assign('nickname', $nickname);
        return $this->view->fetch();
    }

    /**
     * 查看用户想买车型的报价
     */
    public function buymodelsprice($ids = null)
    {
        $this->model = model('QuotedPrice');
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
                    ->with(['user' => function ($query) {
                        $query->withField('id,nickname,avatar,mobile');
                    }])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user' => function ($query) {
                        $query->withField('id,nickname,avatar,mobile');
                    }])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('buy_car_id', $ids);
        $models_name = BuycarModel::where('id', $ids)->value('models_name');
        $this->view->assign('models_name', $models_name);
        return $this->view->fetch();
    }

    /** 
     * 确认交易
     */
    public function startdeal()
    {
        $this->model = model('QuotedPrice');
        if ($this->request->isAjax()) {

            $id = $this->request->post('id');

            $data = $this->model->where('id', $id)->field('buy_car_id')->find();

            if ($data['buy_car_id']) {

                $this->model->save(['deal_status' => 'cannot_the_deal'], function ($query) use ($data) {
                    $query->where('buy_car_id', $data['buy_car_id']);
                });
            }
            
            $result = $this->model->save(['deal_status' => 'click_the_deal'], function ($query) use ($id) {
                $query->where('id', $id);
            });
            
            if ($result) {

                $this->success();

            } else {
                $this->error();
            }

        }

    }

    /** 
     * 确认买家保证金是否到账
     */
    public function buyeraccount()
    {
        $this->model = model('QuotedPrice');
        if ($this->request->isAjax()) {

            $id = $this->request->post('id');
        
            $result = $this->model->save(['buyer_payment_status' => 'to_the_account'], function ($query) use ($id) {
                $query->where('id', $id);
            });
            
            if ($result) {

                // $quotedprice = $this->model->where('id', $id)->find();
                // $ModelsInfo = ModelsInfo::where('id', $quotedprice['models_info_id'])->find();
                // $store_name = CompanyStore::where('user_id', $quotedprice['user_ids'])->find();
                // //短信推送
                // $Ucpass = [
                //     'accountsid' => Env::get('sms.accountsid'),
                //     'token' => Env::get('sms.token'),
                //     'appid' => Env::get('sms.appid'),
                //     'templateid' => '442701',
                // ];
                // $param = $store_name['store_name'] . ',' . $ModelsInfo['models_name'] . ',' . $quotedprice['bond'];
            
                // $url = 'http://open.ucpaas.com/ol/sms/sendsms';
                // $client = new \GuzzleHttp\Client();
                // $response = $client->request('POST', $url, [
                //     'json' => [
                //         'sid' => $Ucpass['accountsid'],
                //         'token' => $Ucpass['token'],
                //         'appid' => $Ucpass['appid'],
                //         'templateid' => $Ucpass['templateid'],
                //         'param' => $param,
                //         'mobile' => $store_name['phone']
                //     ]
                // ]);

                $this->success();

            } else {
                $this->error();
            }

        }

    }

    /** 
     * 确认卖家保证金是否到账
     */
    public function selleraccount()
    {
        $this->model = model('QuotedPrice');
        if ($this->request->isAjax()) {

            $id = $this->request->post('id');
        
            $result = $this->model->save(['seller_payment_status' => 'to_the_account'], function ($query) use ($id) {
                $query->where('id', $id);
            });
            
            if ($result) {

                // $quotedprice = $this->model->where('id', $id)->find();
                // $ModelsInfo = ModelsInfo::where('id', $quotedprice['models_info_id'])->find();
                // $store_name = CompanyStore::where('user_id', $quotedprice['by_user_ids'])->value('store_name');
                // //短信推送
                // $Ucpass = [
                //     'accountsid' => Env::get('sms.accountsid'),
                //     'token' => Env::get('sms.token'),
                //     'appid' => Env::get('sms.appid'),
                //     'templateid' => '442701',
                // ];
                // $param = $store_name . ',' . $ModelsInfo['models_name'] . ',' . $quotedprice['bond'];
            
                // $url = 'http://open.ucpaas.com/ol/sms/sendsms';
                // $client = new \GuzzleHttp\Client();
                // $response = $client->request('POST', $url, [
                //     'json' => [
                //         'sid' => $Ucpass['accountsid'],
                //         'token' => $Ucpass['token'],
                //         'appid' => $Ucpass['appid'],
                //         'templateid' => $Ucpass['templateid'],
                //         'param' => $param,
                //         'mobile' => $ModelsInfo['phone']
                //     ]
                // ]);

                $this->success();

            } else {
                $this->error();
            }

        }

    }



}
