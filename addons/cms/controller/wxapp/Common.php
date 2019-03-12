<?php

namespace addons\cms\controller\wxapp;

use addons\cms\model\Block;
use addons\cms\model\Channel;
use addons\cms\model\CompanyStore;
use addons\cms\model\FormIds;
use addons\cms\model\ModelsInfo;
use addons\cms\model\BuycarModel;
use addons\cms\model\Clue;
use addons\cms\model\PayOrder;
use addons\cms\model\QuotedPrice;
use addons\cms\model\User;
use addons\cms\model\StoreLevel;
use addons\cms\model\Config as ConfigModel;
use app\common\model\Addon;
use think\Cache;
use think\Config;
use addons\third\model\Third;
use think\Db;
use think\Exception;

/**
 * 公共
 */
class Common extends Base
{

    protected $noNeedLogin = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 初始化
     */
    public function init()
    {

        //配置信息
        $upload = Config::get('upload');
        $upload['cdnurl'] = $upload['cdnurl'] ? $upload['cdnurl'] : cdnurl('', true);
        $upload['uploadurl'] = $upload['uploadurl'] == 'ajax/upload' ? cdnurl('/ajax/upload', true) : $upload['cdnurl'];
        $config = [
            'upload' => $upload
        ];

        $data = [
//            'bannerList'     => $bannerList,
//            'indexTabList'   => $indexTabList,
//            'newsTabList'    => $newsTabList,
//            'productTabList' => $productTabList,
            'config' => $config,
            'default_image' => ConfigModel::get(['name' => 'default_picture'])->value
        ];
        $this->success('', $data);

    }

    /**
     * 车辆详情
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function car_details()
    {
        $car_id = $this->request->post('car_id');

        $type = $this->request->post('type');
        $user_id = $this->request->post('user_id');
        if (!$car_id || !$type || !$user_id) {
            $this->error('缺少参数', 'error');
        }
        $msg = '';
        $is_authentication = -1;
        $modelName = null;
        switch ($type) {
            case 'sell':
                $modelName = new ModelsInfo();
                break;
            case 'buy':
                $modelName = new BuycarModel();
                break;
            case 'clue':
                $modelName = new Clue();
                break;
            default:
                $this->error('传入错误参数', 'error');
                break;
        }

        try {
            $car_id_key = $type == 'buy' ? 'buy_car_id' : 'models_info_id';

            //判断该用户该车辆是否报价
            $isOffer = QuotedPrice::get([$car_id_key => $car_id, 'type' => $type, 'user_ids' => $user_id]);

            $condition = 'emission_standard,id,models_name,car_licensetime,kilometres,guide_price,parkingposition,phone,store_id,user_id,store_description,createtime,factorytime,transmission_case,displacement';

            if ($type == 'sell') {
                $condition = $condition . ',modelsimages';
                $condition = $condition . ',modelsimages';
            }
            $detail = $modelName->field($condition)
                ->with(['brand' => function ($q) {
                    $q->withField('id,name,brand_default_images');
                }, 'publisherUser' => function ($q) {
                    $q->withField('id,nickname,avatar');
                }])
                ->find($car_id);

            if (!$detail) {
                throw new Exception('未匹配到数据');
            }

            $detail = $detail->toArray();

            //访问详情随机1-100增加浏览量
            $modelName->where('id', $car_id)->setInc('browse_volume', rand(1, 100));

            $detail['modelsimages'] = empty($detail['modelsimages']) ? [self::$default_image] : explode(',', $detail['modelsimages']);

            $default_image = collection(ConfigModel::all(function ($q) {
                $q->where('group', 'default_image')->field('name,value');
            }))->toArray();

            $detail['factorytime'] = $detail['factorytime'] ? date('Y', $detail['factorytime']) : '';
            $detail['emission_standard'] = $detail['emission_standard'] ? $detail['emission_standard'] . '次' : '';
            $detail['kilometres'] = $detail['kilometres'] ? round($detail['kilometres'] / 10000, 2) . '万公里' : null;
            $detail['guide_price'] = $detail['guide_price'] ? round($detail['guide_price'] / 10000, 2) . '万' : null;
            $detail['car_licensetime'] = $detail['car_licensetime'] ? date('Y-m-d', intval($detail['car_licensetime'])) : null;
            $detail['isOffer'] = $isOffer ? 1 : 0;
            $detail['createtime'] = format_date($detail['createtime']);
            $detail['user'] = User::get($user_id) ? User::get($user_id)->visible(['id', 'mobile', 'nickname'])->toArray() : ['id' => '', 'mobile' => '', 'nickname' => ''];
            $detail['default'] = [
                $default_image[0]['name'] => $default_image[0]['value'],
                $default_image[1]['name'] => $default_image[1]['value'],
                $default_image[2]['name'] => $default_image[2]['value'],
            ];

            $company = CompanyStore::get(['user_id' => $user_id]);
            if (!$company) {
                $is_authentication = 1;
                $msg = '您未认证店铺';
            } else {
                $status = $company->auditstatus;
                if ($status == 'paid_the_money') {
                    $is_authentication = 0;
                } else {
                    $is_authentication = 2;
                    $msg = '您还未完成店铺认证';
                }
            };


        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success('请求成功', ['can_quote' => ['is_authentication' => $is_authentication, 'msg' => $msg], 'detail' => $detail]);
    }

    /**
     * 创建formId
     * @param $fomrId
     * @return bool
     */
    public static function writeFormId($fomrId, $user_id)
    {

        try {
            $data = FormIds::create(['form_id' => $fomrId, 'user_id' => $user_id, 'status' => 1]);

        } catch (Exception $e) {
            return $e->getMessage();
        }

    }

    /**
     * 根据用户选择的level_id 查出当前等级名称
     * @param $level_id
     * @return mixed
     * @throws \think\exception\DbException
     */
    public static function getLevelStoreName($level_id)
    {
        return StoreLevel::get($level_id);
    }


    /**
     * 获取订单号，from模板消息
     * @param $user_id
     * @return mixed
     * @throws \think\exception\DbException
     */
    public static function getPayOrderNum($user_id, $store_id)
    {
        return PayOrder::get(['user_id' => $user_id, 'store_id' => $store_id]);
    }

    /**
     * 获取formId
     * @param $user_id
     * @return array
     * @throws \think\exception\DbException
     */
    public static function getFormId($user_id)
    {

        return collection(FormIds::all())->toArray();
    }

    /**
     * 检测是否存在重复支付
     * @param $store_id
     * @param $order_number
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function checkPay($store_id, $out_trade_no)
    {
        return collection(PayOrder::with(['pay_status'])->where(['store_id' => $store_id, 'out_trade_no' => $out_trade_no])->select())->toArray();
    }

    /**
     * 发送小程序模板消息
     * @param $data
     * @return array
     */

    public static function sendXcxTemplateMsg($data = '')
    {
        Cache::rm('access_token');
        $access_token = getWxAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={$access_token}";
        return posts($url, $data);
    }

    /**
     * 获取支付店铺等级
     * @param $store_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getLevel($store_id)
    {
        return collection(CompanyStore::with(['storelevel' => function ($q) {
            $q->withField(['partner_rank', 'id']);
        }])->where(['id' => $store_id])->field('id,level_id')->select())->toArray()[0]['storelevel']['partner_rank'];
    }

    /**
     * 获取用户openid
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getOpenid($user_id)
    {
        return Db::name('third')->where(['user_id' => $user_id])->find()['openid'];
    }
}
