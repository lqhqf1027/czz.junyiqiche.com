<?php

namespace app\admin\controller\merchant;

use app\common\controller\Backend;
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
                    ->with(['storelevel','storeuser'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['storelevel','storeuser'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                
                $row->getRelation('storelevel')->visible(['partner_rank']);
				$row->getRelation('storeuser')->visible(['name']);
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
            ->field('a.id,a.cities_name,a.store_name,a.store_address,a.phone,a.store_img,a.store_description,a.main_camp,a.business_life,
                b.partner_rank,
                c.name as user_name,c.avatar,c.bank_card,c.id_card_images')
            ->where('a.id',$ids)
            ->find();

        //头像
        $avatar = $row['avatar'] == '' ? [] : explode(',', $row['avatar']);
        //身份证
        $id_card_images = $row['id_card_images'] == '' ? [] : explode(',', $row['id_card_images']);
        //店铺展示图
        $store_img = $row['store_img'] == '' ? [] : explode(',', $row['store_img']);

        $this->view->assign(
            [
                'row' => $row,
                'cdnurl' => Config::get('upload')['cdnurl'],
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


}
