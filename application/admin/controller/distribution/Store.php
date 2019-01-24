<?php

namespace app\admin\controller\distribution;

use app\common\controller\Backend;
use app\admin\model\Cities;
use app\admin\model\CompanyStore;


/**
 * 公司门店
 *
 * @icon fa fa-circle-o
 */
class Store extends Backend
{
    
    /**
     * Store模型对象
     * @var \app\admin\model\CompanyStore
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        
        $storeList = [];
        $disabledIds = [];
        $ids = [];
        $cities = collection(Cities::where('pid', '0')->order("id desc")->field(['id, pid, shortname as name'])->select())->toArray();
        foreach ($cities as $k => $v) {
            $ids[] = $v['id'];
        }
        // print_r($ids);
        // die;
        $cities_all = collection(Cities::where('pid', 'in', $ids)->order("id desc")->field(['id, pid, shortname as name'])->select())->toArray();
        // print_r($cities_all);
        // die;
        // $all = array_merge($cities, $cities_all);
    
        foreach ($cities as $k => $v) {

            $state = ['opened' => true];

            if ($v['pid'] == 0) {
            
                
                $storeList[] = [
                    'id'     => $v['id'],
                    'parent' => '#',
                    'text'   => __($v['name']),
                    'state'  => $state
                ];
            }

            foreach ($cities_all as $key => $value) {
                
                if ($v['id'] == $value['pid']) {
                    
                    $storeList[] = [
                        'id'     => $value['id'],
                        'parent' => $v['id'],
                        'text'   => __($value['name']),
                        'state'  => $state
                    ];
                }
                   
            }
            
        }
        // print_r($storeList);
        // die;
        $this->assignconfig('storeList', $storeList);

        $this->model = new \app\admin\model\CompanyStore;
        $this->view->assign("statussList", $this->model->getStatussList());

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
                    ->with(['cities','storelevel','storeuser'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['cities','storelevel','storeuser'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                
                $row->getRelation('cities')->visible(['shortname']);
				$row->getRelation('storelevel')->visible(['partner_rank']);
				$row->getRelation('storeuser')->visible(['name']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}
