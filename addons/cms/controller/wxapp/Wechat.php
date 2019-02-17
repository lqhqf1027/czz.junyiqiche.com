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

class Wechat extends Base
{


    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $model = '';
    //微信授权配置信息
    private $Wxapis;

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

    /** 微信toke
     * @return array|mixed  返回Token
     */
    public static function getWxtoken()
    {

        $appid = Config::get('oauth')['appid'];
        $secret = Config::get('oauth')['appsecret'];
        $token = cache('Token');
        if (!$token['access_token'] || $token['expires_in'] <= time()) {
//            https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret
            $rslt = gets("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . '&secret=' . $secret);
            if ($rslt) {
                $accessArr = array(
                    'access_token' => $rslt['access_token'],
                    'expires_in' => time() + $rslt['expires_in'] - 200
                );
                cache('Token', $accessArr);
                $token = $accessArr;
            }
        }
        return $token;
    }

    /*
     *小程序模板消息
     *@param uid 用户id
     *$param template_id 模板id
     *@param form_id 表单提交场景下formId(只能用一次)
     *@param emphasis_keyword 消息加密密钥
    */
    public function sendOffer()
    {
//        pr(getAccessToken());
//        echo '<br>';
//        pr(self::getWxtoken());die;

//        $uid,$form_id,$template_id
        $user_id = $this->request->post('user_id');
        $phone = $this->request->post('phone');
        $money = $this->request->post('money');
        $form_id = $this->request->post('formId');
        $models_id = $this->request->post('models_id');
        $type = $this->request->post('type');
        $typeModels = $type == 'buy' ? 'buycar_model' : 'models_info'; //转换表名
        $o = self::getModelType($typeModels, $user_id);
        $openid = self::getUserOpenId($o['user_id']);//店铺发布人的openid
        if (!$user_id || !$money || !$type || !$models_id || !$form_id || !checkPhoneNumberValidate($phone)) {
            $this->error('缺少参数或参数格式错误');
        }

        if ($phone) {
            Db::name('user')->where(['id' => $user_id])->setField('mobile', $phone);  //每次执行一次更新手机号操作
        }

        //分配变量复制为车辆类型，根据type值转换对应的表名
        $res = QuotedPrice::create(
            ['user_ids' => $user_id, 'money' => $money, 'models_id' => $models_id, 'type' => $type, 'quotationtime' => time()]
        );

        $typeMod = $type == 'buy' ? 'BuycarModel' : 'ModelsInfo'; //转换表名

        $keyword1 = collection(BuycarModel::with(['brand'])->field('models_name')->select(['id' => $models_id]))->toArray();
        //拼接 template
        $keyword1 = $keyword1[0]['brand']['name'] . ' ' . $keyword1[0]['models_name'];
        $keyword2 = userModel::get(['id' => $user_id]);
        $keyword2 = emoji_decode($keyword2->nickname) . '-' . $keyword2->mobile;

        if ($openid && $res) {

            $temp_msg = array(
                'touser' => "{$openid}",
                'template_id' => "KSNfO5CSLfKZps8Ua-GOS7pzik9hwiOCQLWmzJ-UVko",
                'page' => "/pages/mine/mine",
                'form_id' => "{$form_id}",
                'data' => array(
                    'keyword1' => array(
                        'value' => "{$keyword1}",
                    ),
                    'keyword2' => array(
                        'value' => "{$keyword2}",
                    ),
                    'keyword3' => array(
                        'value' => date('Y-m-d H:i:s', time()),
                    ),
                    'keyword4' => array(
                        'value' => "{$money}",
                    )

                ),
            );

            $res = $this->sendXcxTemplateMsg(json_encode($temp_msg));
            if ($res['errcode'] == 0) {
                $this->success('报价成功', $res);
            }
            $this->error($res['errmsg'], $res['errcode']);

        }
    }

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

    public function uploadsfiles()
    {
//        pr( collection)->toArray());
        $files = $this->request->file('file');
        $data = $this->request->post('carInfo');
        foreach($files as $file){
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
                echo $info->getExtension();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                echo $info->getFilename();
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
//        pr($file);die;
//        pr($this->object_to_array( $files))  ;die;

        // 移动到框架应用根目录/public/uploads/ 目录下
//        foreach ($files as $file) {
//            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
//            if ($info) {
//                // 成功上传后 获取上传信息
//                // 输出 jpg
//                echo $info->getExtension();
//                // 输出 42a79759f284b767dfcb2a0197904287.jpg
//                echo $info->getFilename();
//            } else {
//                // 上传失败获取错误信息
//                echo $file->getError();
//            }
//        }
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
    function object_to_array($obj){
        $_arr=is_object($obj)?get_object_vars($obj):$obj;
        foreach($_arr as $key=>$val){
            $val=(is_array($val))||is_object($val)?object_to_array($val):$val;
            $arr[$key]=$val;
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