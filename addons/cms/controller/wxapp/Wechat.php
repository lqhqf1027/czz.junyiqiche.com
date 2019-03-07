<?php
/**
 * Created by PhpStorm.
 * User: glen9
 * Date: 2019/2/14
 * Time: 16:22
 */

namespace addons\cms\controller\wxapp;

use fast\wxapp;
use think\Db;
use addons\cms\model\User as userModel;
use addons\cms\model\ModelsInfo;
use addons\cms\model\BuycarModel;
use addons\cms\model\QuotedPrice;
use addons\third\model\Third;
use wechat\Wx;
use think\Config;
use think\Exception;
use think\Cache;
use GuzzleHttp\Client;

class Wechat extends Base
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $model = '';
    public function _initialize()
    {
        parent::_initialize();

    }

    /*
     *报价
     *@param uid 用户id
     *$param template_id 模板id
     *@param form_id 表单提交场景下formId(只能用一次)
     *@param emphasis_keyword 消息加密密钥
    */
    public function sendOffer()
    {
        $user_id = $this->request->post('user_id');
        $phone = $this->request->post('phone');
        $money = $this->request->post('money');
        $models_id = $this->request->post('models_id');
        $type = $this->request->post('type');

        $typeModels = $type == 'buy' ? new \addons\cms\model\BuycarModel : new \addons\cms\model\ModelsInfo; //转换表名
        $modelsInfo = collection($typeModels->with(['brand'])->select(['id' => $models_id]))->toArray();
        $modelsInfo = $modelsInfo[0]['brand']['name'] . ' ' . $modelsInfo[0]['models_name'];  //拼接品牌、车型
        $newPone = substr($phone, 7);//手机尾号4位数
        $param = "{$modelsInfo},{$newPone}";

        $result = sendOffers($user_id, $phone, $money, $models_id, $type, '432305',$param);

        return $result[0]=='success'?$this->success($result['msg']):$this->error($result['msg']);
//        if (!$user_id || !$money || !$type || !$models_id || !checkPhoneNumberValidate($phone)) {
//            $this->error('缺少参数或参数格式错误');
//        }
//        try {
//            $merchantsPhone = trim($typeModels->get(['id' => $models_id])->phone);//商户的手机号

//            if ($phone) {
//                Db::name('user')->where(['id' => $user_id])->setField('mobile', $phone);  //每次执行一次更新手机号操作
//            }
//            $newPone = substr($phone, 7);//手机尾号4位数
//            $url = 'http://open.ucpaas.com/ol/sms/sendsms';
////            return "{$modelsInfo},{$newPone}";
//            $client = new Client();
//            $response = $client->request('POST', $url, [
//                'json' => [
//                    'sid' => self::$Ucpass['accountsid'],
//                    'token' => self::$Ucpass['token'],
//                    'appid' => self::$Ucpass['appid'],
//                    'templateid' => self::$Ucpass['templateid']['sendOffer'],
//                    'param' => "{$modelsInfo},{$newPone}",  //参数
//                    'mobile' => $merchantsPhone,
//                    'uid' => $user_id
//                ]
//            ]);
//            if ($response) {
//                $result = json_decode($response->getBody(), true);
//                if ($result['code'] == '000000') { //发送成功
//                    $field = $type == 'buy' ? 'buy_car_id' : 'models_info_id';
//                    $res = QuotedPrice::create(
//                        ['user_ids' => $user_id, 'money' => $money, $field => $models_id, 'type' => $type, 'quotationtime' => time(),'is_see'=>2]
//                    ) ? $this->success('报价成功', '') : $this->error('报价失败', '');
//                }
//                $this->error('短信通知失败');
//            }
//            $this->error('短信通知失败');
//        } catch (Exception $e) {
//            $this->error($e->getMessage());
//        }


    }

    /**
     * 根据用户id 查询Third表中的openid
     * @param $user_id
     * @return array
     * @throws \think\exception\DbException
     */
    public static function getUserOpenId($user_id)
    {
        return Third::get(['user_id' => $user_id])->openid;
    }

}