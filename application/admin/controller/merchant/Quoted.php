<?php

namespace app\admin\controller\merchant;

use app\common\controller\Backend;
use app\admin\model\ModelsInfo;
use app\admin\model\BuycarModel;
use app\admin\model\User;
use app\admin\model\QuotedPrice;


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
     * 商家在售
     */
    public function saleCar()
    {
        $this->model = model('User');
        //设置过滤方法
        $this->request->filter(['strip_tags']);

        //查询用户
        $saleData = ModelsInfo::field('id,user_id')->select();
        $userIds = [];
        foreach ($saleData as $k => $v) {
            if (!in_array($v['user_id'],$userIds)) {
                $userIds[] = $v['user_id']; 
            }
        }

        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->where($where)
                    ->where('id', 'in', $userIds)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->where($where)
                    ->where('id', 'in', $userIds)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $key => $row) {
                //上架销售车辆台数
                $list[$key]['salecount'] = ModelsInfo::where(['user_id' => $row['id'], 'shelfismenu' => 1])->count();

            }

            $list = collection($list)->toArray();
    
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 有人想买
     */
    public function buyCar()
    {
        $this->model = model('User');
        //设置过滤方法
        $this->request->filter(['strip_tags']);

        //查询用户
        $saleData = BuycarModel::field('user_id')->select();
        $userIds = [];
        foreach ($saleData as $k => $v) {
            if (!in_array($v['user_id'],$userIds)) {
                $userIds[] = $v['user_id']; 
            }
        }

        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->where($where)
                    ->where('id', 'in', $userIds)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->where($where)
                    ->order($sort, $order)
                    ->where('id', 'in', $userIds)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $key => $row) {
                
                //上架销售车辆台数
                $list[$key]['salecount'] = BuycarModel::where(['user_id' => $row['id'], 'shelfismenu' => 1])->count();
                
            }

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    
    /**
     * 商家在售----上架车型
     */
    public function salemodel($ids = null)
    {
        $this->model = model('ModelsInfo');
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        // pr($ids);
        // die;
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['brand'])
                    ->where($where)
                    ->where(['shelfismenu' => 1])
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['brand'])
                    ->where($where)
                    ->where(['shelfismenu' => 1])
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $key => $row) {
                
                //收到报价次数
                $list[$key]['quotecount'] = QuotedPrice::where(['models_info_id' => $row['id']])->count();

            }
            $list = collection($list)->toArray();
           
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('user_id', $ids);

        return $this->view->fetch();
    }

    /**
     * 有人想买----上架车型
     */
    public function buymodel($ids = null)
    {
        $this->model = model('BuycarModel');
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['brand'])
                    ->where($where)
                    ->where(['shelfismenu' => 1])
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['brand'])
                    ->where($where)
                    ->where(['shelfismenu' => 1])
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $key => $row) {
                
                //收到报价次数
                $list[$key]['quotecount'] = QuotedPrice::where(['buy_car_id' => $row['id']])->count();

            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('user_id', $ids);
        return $this->view->fetch();
    }

    /**
     * 商家在售----上架车型报价
     */
    public function salequoted($ids = null)
    {
        $this->model = model('QuotedPrice');
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
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

            foreach ($list as $key => $row) {
                
                
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('models_info_id', $ids);
        return $this->view->fetch();
    }

    /**
     * 有人想买----上架车型报价
     */
    public function buyquoted($ids = null)
    {
        $this->model = model('QuotedPrice');
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
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

            foreach ($list as $key => $row) {
                
                
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('buy_car_id', $ids);
        return $this->view->fetch();
    }

}
