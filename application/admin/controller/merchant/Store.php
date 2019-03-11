<?php

namespace app\admin\controller\merchant;

use app\common\controller\Backend;
use app\admin\model\CompanyStore;
use app\admin\model\Distribution;
use app\admin\model\QuotedPrice;
use app\admin\model\ModelsInfo;
use app\admin\model\BuycarModel;
use app\admin\model\StoreLevel;
use think\Config;
use think\Db;
use think\Env;
use GuzzleHttp\Client;
use addons\cms\model\Config as ConfigModel;
use addons\cms\controller\wxapp\Common;
use addons\cms\model\FormIds;
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
                $row->getRelation('user')->visible(['invite_code','invitation_code_img']);
                //邀请店铺数量
                $list[$k]['count'] = Distribution::where('store_id', $row['id'])->count();
                //店铺在售车型数量
                $list[$k]['salecount'] = ModelsInfo::where('store_id', $row['id'])->count();
                //店铺想买车型数量
                $list[$k]['buycount'] = BuycarModel::where('store_id', $row['id'])->count();
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //总店铺
        $store_count = $this->model->count();
        //白银店铺
        $silver_store = $this->model->where('level_id', 3)->count();
        //黄金店铺
        $gold_store = $this->model->where('level_id', 2)->count();
        //铂金店铺
        $platinum_store = $this->model->where('level_id', 1)->count();
        //铂金店铺
        $platinum_store = $this->model->where('level_id', 1)->count();

        //待审核/审核中的店铺
        $wait_store = $this->model->where('auditstatus', 'wait_the_review')->count();
        //审核通过的店铺
        $pass_store = $this->model->where('auditstatus', 'pass_the_audit')->count();
        //认证成功的店铺
        $money_store = $this->model->where('auditstatus', 'paid_the_money')->count();
        //本周增加的店铺
        $start_time = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y'));
        $end_time = mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));

        $increase_store = $this->model->where('createtime', 'between', [$start_time, $end_time])->count();

        $this->view->assign([
            'store_count' => $store_count,
            'silver_store' => $silver_store,
            'gold_store' => $gold_store,
            'platinum_store' => $platinum_store,
            'wait_store' => $wait_store,
            'pass_store' => $pass_store,
            'money_store' => $money_store,
            'increase_store' => $increase_store

        ]);
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
                $storeData = $this->model->where('id', $id)->find();
                $level_name = StoreLevel::where('id', $storeData['level_id'])->value('partner_rank');
                //短信推送
                $Ucpass = [
                    'accountsid' => Env::get('sms.accountsid'),
                    'token' => Env::get('sms.token'),
                    'appid' => Env::get('sms.appid'),
                    'templateid' => '441263',
                ];
                $param = '{' . $storeData['store_name'] . '(' . $level_name . ')}';
            
                $url = 'http://open.ucpaas.com/ol/sms/sendsms';
                $client = new \GuzzleHttp\Client();
                $response = $client->request('POST', $url, [
                    'json' => [
                        'sid' => $Ucpass['accountsid'],
                        'token' => $Ucpass['token'],
                        'appid' => $Ucpass['appid'],
                        'templateid' => $Ucpass['templateid'],
                        'param' => $param,
                        'mobile' => $storeData['phone']
                    ]
                ]);
                
                //模板推送
                //获取formId
                $formId = current(array_values(Common::getFormId($storeData['user_id'])))['form_id']; 
                // pr($formId);
                // pr($storeData['user_id']);
                // die;
                if ($formId) {
                    $keyword1 = $storeData['store_name'];
                    $openid = Common::getOpenid($storeData['user_id']);
                    $temp_msg = array(
                        'touser' => "{$openid}",
                        'template_id' => "B-gukPlG-T-2ydDlkGqh73ArScwp90Nm4blPdJ7fXdw",
                        'page' => "",
                        'form_id' => "{$formId}",
                        'data' => array(
                            'keyword1' => array(
                                'value' => "{$keyword1}",
                            ),
                            'keyword2' => array(
                                'value' => "已通过审核",
                            ),
                            'keyword3' => array(
                                'value' => "店铺实名认证",
                            ),
                            'keyword4' => array(
                                'value' => date('Y-m-d H:i:s', time()),
                            )
                        ),
                    );
                    $res = Common::sendXcxTemplateMsg(json_encode($temp_msg));

                    if ($res['error_code'] == 0) {

                        FormIds::where(['user_id' => $user_id, 'form_id' => $formId])->delete();
                    }
                }
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

    /**
     * 查看店铺在售车型
     */
    public function salemodels($ids = null)
    {
        $this->model = model('ModelsInfo');
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
                $list[$key]['count'] = QuotedPrice::where(['models_info_id' => $row['id']])->count();
            }
            $list = collection($list)->toArray();
    
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('store_id', $ids);
        $store_name = CompanyStore::where('id', $ids)->value('store_name');
        $this->view->assign('store_name', $store_name);
        return $this->view->fetch();
    }

    /**
     * 查看店铺想买车型
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
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('store_id', $ids);
        $store_name = CompanyStore::where('id', $ids)->value('store_name');
        $this->view->assign('store_name', $store_name);
        return $this->view->fetch();
    }

    /**
     * 查看店铺在售车型的报价
     */
    public function salemodelsprice($ids = null)
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

            foreach ($list as $k => $row) {
                
                $list[$k]['store_name'] = CompanyStore::where('user_id', $row['user_ids'])->value('store_name');
            }
            $list = collection($list)->toArray();
    
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('models_info_id', $ids);
        $models_name = ModelsInfo::where('id', $ids)->value('models_name');
        $this->view->assign('models_name', $models_name);
        return $this->view->fetch();
    }

    /**
     * 查看店铺想买车型的报价
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
     * 发送验证码
     */
    public function sendCode()
    {
        //手机号
        $mobile = ConfigModel::get(['name' => 'default_mobile'])->value;
        // pr($mobile);
        // die;
        if ($this->request->isAjax()) {
            //发送验证码
            $result = message_send($mobile, '430761');
            // pr($result);
            // die;
            $result[0] == 'success' ? $this->success($result['msg']) : $this->error($result['msg']);

        }

    }

    /** 
     * 验证验证码
     */
    public function checkCode()
    {
        //手机号
        $mobile = ConfigModel::get(['name' => 'default_mobile'])->value;

        if ($this->request->isAjax()) {
            //店铺id
            $id = input("id");
            $id = json_decode($id, true);
            //输入验证码
            $code = input("text");

            //验证验证码
            $userInfo = Db::name('cms_login_info')
                ->where(['login_phone' => $mobile])->find();
            if (!$userInfo || $code != $userInfo['login_code']) {
                $this->error('验证码输入错误');
            }
            else {

                //通过审核并完成支付
                $result = $this->model->save(['auditstatus' => 'paid_the_money'], function ($query) use ($id) {
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

                $this->success();

            } else {
                $this->error();
            }

        }

    }

}
