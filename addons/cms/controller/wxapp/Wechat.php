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
class Wechat extends Base
{


    protected $noNeedLogin = '*';
    protected $appid = '';
    protected $appsecret = '';

    /**
     * 发送小程序模板消息
     * @param $data
     * @return array
     */

    public function sendXcxTemplateMsg($data='')
    {
        $access_token = getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={$access_token}";
        return $this->https_request($url, $data);
    }

    /*
     *小程序模板消息
     *@param uid 用户id
     *$param template_id 模板id
     *@param form_id 表单提交场景下formId(只能用一次)
     *@param emphasis_keyword 消息加密密钥
    */
    public function sendTemplateMessage()
    {
//        $uid,$form_id,$template_id
        $user_id = $this->request->post('user_id');
        $phone = $this->request->post('phone');
        $money = $this->request->post('money');
        $form_id = $this->request->post('formId');
        $openid = self::getUserOpenId($user_id)['openid'];
        if(!$user_id || !$money || $form_id || !checkPhoneNumberValidate($phone)){
            $this->error('缺少参数或手机号格式错误');
        }
        if($phone){
            Db::name('user')->where(['id'=>$user_id])->setField('phone',$phone);
        }
        if ($openid) {
            $temp_msg = array(
                'touser' => "{$openid}",
                'template_id' => "KSNfO5CSLfKZps8Ua-GOS7pzik9hwiOCQLWmzJ-UVko",
                'page' => "/pages/mine/mine",
                'form_id' => "{$form_id}",
                'data' => array(
                    'keyword1' => array(
                        'value' => "test",
                    ),
                    'keyword2' => array(
                        'value' => 'test',
                    ),
                    'keyword3' => array(
                        'value' => date('Y-m-d H:i:s', time()),
                    ),
                    'keyword4' => array(
                        'value' => 120000,
                    ),
                ),
                'emphasis_keyword' => "test"
            );
            $res = $this->sendXcxTemplateMsg(json_encode($temp_msg));
            if($res['errcode']==0){
                $this->success( '报价成功',$res);
            }
            pr($res);
            die;
            exit;
        }
    }

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
    public static function  getUserOpenId($user_id){
        return collection(Third::get(['user_id'=>$user_id]))->toArray();
    }

}