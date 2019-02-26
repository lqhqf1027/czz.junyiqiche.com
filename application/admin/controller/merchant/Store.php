<?php

namespace app\admin\controller\merchant;

use app\common\controller\Backend;
use app\admin\model\CompanyStore;
use app\admin\model\Distribution;
use think\Config;
use think\Db;

/**
 * 店铺
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
    protected $multiFields = ['recommend'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\CompanyStore;
        $this->view->assign("statussList", $this->model->getStatussList());
        $this->view->assign("auditstatusList", $this->model->getAuditstatusList());
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
                    ->with(['storelevel','user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['storelevel','user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $k => $row) {
                
                $row->getRelation('storelevel')->visible(['partner_rank']);
                $row->getRelation('user')->visible(['name']);
                $list[$k]['count'] = Distribution::where('store_id', $row['id'])->count();
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /** 
     * 审核店铺
     */
    public function auditResult($ids = null)
    {
        $row = Db::name('company_store')->alias('a')
            ->join('store_level b', 'b.id=a.level_id', 'LEFT')
            ->join('user c', 'c.id = a.user_id', 'LEFT')
            ->field('a.id,a.cities_name,a.store_name,a.store_address,a.phone,a.store_img,a.store_description,a.main_camp,a.business_life,a.bank_card,a.id_card_images,a.business_licenseimages,
                b.partner_rank,
                c.name as user_name,c.avatar')
            ->where('a.id',$ids)
            ->find();

        //头像
        $avatar = $row['avatar'] == '' ? [] : explode(',', $row['avatar']);
        //身份证
        $id_card_images = $row['id_card_images'] == '' ? [] : explode(',', $row['id_card_images']);
        //店铺展示图
        $store_img = $row['store_img'] == '' ? [] : explode(',', $row['store_img']);
        //营业执照（多图）
        $business_licenseimages = $row['business_licenseimages'] == '' ? [] : explode(',', $row['business_licenseimages']);

        $this->view->assign(
            [
                'row' => $row,
                'cdnurl' => 'https://czz.junyiqiche.com',
                'avatar' => $avatar,
                'id_card_images' => $id_card_images,
                'store_img' => $store_img,
            ]
        );

        return $this->view->fetch('auditResult');

    }

    /** 
     * 审核店铺----通过
     */
    public function pass()
    {
        if ($this->request->isAjax()) {

            $id = input("id");

            $id = json_decode($id, true);
            
            $result = $this->model->save(['auditstatus' => 'pass_the_audit'], function ($query) use ($id) {
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
     * 审核店铺----未通过
     */
    public function nopass()
    {
        if ($this->request->isAjax()) {

            $id = input("id");
            $text = input("text");

            $id = json_decode($id, true);

            $result = $this->model->save(['auditstatus' => 'audit_failed', 'text' => $text], function ($query) use ($id) {
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
     * 查看店铺推广
     */
    public function storepromotion($ids = null)
    {
        $this->model = model('Distribution');
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);

        $result = Collection($this->model->where('store_id', $ids)->select())->toArray();
        foreach ($result as $k => $v) {
            $level_store_id[] = $v['level_store_id'];
        }
        // pr($level_store_id);
        // die;

        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['store'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['store'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $k => $row) {

                $list[$k]['count'] = Distribution::where('store_id', $row['store']['id'])->count();

            }
            $list = collection($list)->toArray();
            // pr($list);
            // die;
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('level_store_id', $level_store_id);
        return $this->view->fetch();
    }

    /** 
     * 查看下级店铺推广
     */
    public function levelstorepromotion($ids = null)
    {
        $this->model = model('Distribution');
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        
        $level_store_id = $this->model->where('id', $ids)->value('level_store_id');
        // pr($level_store_id);
        // die;
        $result = Collection($this->model->where('store_id', $level_store_id)->select())->toArray();
        foreach ($result as $k => $v) {
            $level_store_ids[] = $v['level_store_id'];
        }
        // pr($level_store_ids);
        // die;
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['store'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['store'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $k => $row) {

            }
            $list = collection($list)->toArray();
            // pr($list);
            // die;
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('level_store_ids', $level_store_ids);
        return $this->view->fetch();
    }


}
