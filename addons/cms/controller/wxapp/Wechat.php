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

    /**
     * 发送小程序模板消息
     * @param $data
     * @return array
     */

    public function sendXcxTemplateMsg($data = '')
    {
        $access_token = self::getWxtoken()['access_token'];
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={$access_token}";
        return $this->https_request($url, $data);
    }



    // 获取 access_token

    private function getAccessToken()
    {
        $appid = Config::get('oauth')['appid'];
        $secret = Config::get('oauth')['appsecret'];
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";

        //$raw = curl_get($url);

        $raw = $this->curl_get_https($url);

        if (strlen($raw) > 0) {

            $data = json_decode($raw, true);

            if (json_last_error() == JSON_ERROR_NONE) {

                if (key_exists('access_token', $data)) {

                    return $data['access_token'];

                } else {

                    return false;

                }

            } else {

                return false;

            }

        } else {

            return false;

        }

    }


    //curl  get会话

    private function curl_get_https($url)
    {

        $curl = curl_init(); // 启动一个CURL会话

        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_HEADER, 0);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在

        $tmpInfo = curl_exec($curl);     //返回api的json对象

        //关闭URL请求

        curl_close($curl);

        return $tmpInfo;    //返回json对象

    }


    private function curl_post_send_information($token, $vars, $second = 120, $aHeader = array())

    {

        $ch = curl_init();

        //超时时间

        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //这里设置代理，如果有的话

        curl_setopt($ch, CURLOPT_URL, 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $token);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (count($aHeader) >= 1) {

            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);

        }

        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);

        $data = curl_exec($ch);

        if ($data) {

            curl_close($ch);

            return $data;

        } else {

            $error = curl_errno($ch);

            curl_close($ch);

            return $error;

        }

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

//
//    function sendOffer()
//    {
//        $access_token = Cache::get("token");
//        if (!$access_token) {
//            $access_token = $this->getAccessToken();
//            Cache::set("token", $access_token, 7200);
//
//        }
//
//        $user_id = $this->request->post('user_id');
//        $phone = $this->request->post('phone');
//        $money = $this->request->post('money');
//        $form_id = $this->request->post('formId');
//        $models_id = $this->request->post('models_id');
//        $type = $this->request->post('type');
//        $typeModels = $type == 'buy' ? 'buycar_model' : 'models_info'; //转换表名
//        if (!$user_id || !$money || !$type || !$models_id || !$form_id || !checkPhoneNumberValidate($phone)) {
//            $this->error('缺少参数或参数格式错误');
//        }
//
//        try {
//            $openid = self::getUserOpenId(self::getModelType($typeModels, $user_id)['user_id']);//店铺发布人的openid
//
//            if ($phone) {
//                Db::name('user')->where(['id' => $user_id])->setField('mobile', $phone);  //每次执行一次更新手机号操作
//            }
//
//            //分配变量复制为车辆类型，根据type值转换对应的表名
//            $res = QuotedPrice::create(
//                ['user_ids' => $user_id, 'money' => $money, 'models_id' => $models_id, 'type' => $type, 'quotationtime' => time()]
//            );
//
//            $typeMod = $type == 'buy' ? 'BuycarModel' : 'ModelsInfo'; //转换表名
//
//            $keyword1 = collection(BuycarModel::with(['brand'])->field('models_name')->select(['id' => $models_id]))->toArray();
//            //拼接 template
//            $keyword1 = $keyword1[0]['brand']['name'] . ' ' . $keyword1[0]['models_name'];
//            $keyword2 = userModel::get(['id' => $user_id]);
//            $keyword2 = emoji_decode($keyword2->nickname) . '-' . $keyword2->mobile;
//
//            if ($openid && $res) {
//                $temp_msg = array(
//                    'touser' => "{$openid}",
//                    'template_id' => "KSNfO5CSLfKZps8Ua-GOS7pzik9hwiOCQLWmzJ-UVko",
//                    'page' => "/pages/mine/mine",
//                    'form_id' => "{$form_id}",
//                    'data' => array(
//                        'keyword1' => array(
//                            'value' => "{$keyword1}",
//                        ),
//                        'keyword2' => array(
//                            'value' => "{$keyword2}",
//                        ),
//                        'keyword3' => array(
//                            'value' => date('Y-m-d H:i:s', time()),
//                        ),
//                        'keyword4' => array(
//                            'value' => "{$money}",
//                        )
//
//                    ),
//                );
//
////                $res = $this->sendXcxTemplateMsg(json_encode($temp_msg));
//                $result = $this->curl_post_send_information($access_token, json_encode($temp_msg));
//
//
//                dump($result);
//                die;
//                pr($res);
//                die;
//                if (isset($res)) {
//
//                }
//                throw  new Exception($this->error('报价失败'));
//            }
//
//        } catch (Exception $e) {
//            $this->error($e->getMessage());
//        }
////        $this->success('报价成功', $res);
//    }

    /**
     * 根据提交车辆type  类型获取用户id 所关联的third表中的openid
     * @param $type  buy->buycar_model  ,  sell->models_info
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public static function getModelType($type, $user_id)
    {

        return Db::name($type)->where(['user_id' => $user_id])->field('user_id,brand_id')->find();//         return self::getUserOpenId($modelsInfo['user_id'])['openid'];

    }



    /**
     * 对象 转 数组
     *
     * @param object $obj 对象
     * @return array
     */
    /**
     * object 转 array
     */
    public function object_to_array($obj)
    {
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($_arr as $key => $val) {
            $val = (is_array($val)) || is_object($val) ? object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }

    /**
     * curl请求
     * @param $url
     * @param null $data
     * @param int $time_out
     * @param string $out_level
     * @param array $headers
     * @return mixed
     */
    public function https_request($url, $data = null, $time_out = 60, $out_level = "s", $headers = array())
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if ($out_level == "s") {
            //超时以秒设置
            curl_setopt($curl, CURLOPT_TIMEOUT, $time_out);//设置超时时间
        } elseif ($out_level == "ms") {
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, $time_out);  //超时毫秒，curl 7.16.2中被加入。从PHP 5.2.3起可使用
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);//如果有header头 就发送header头信息
        }
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
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