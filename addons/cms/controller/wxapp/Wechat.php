<?php
/**
 * Created by PhpStorm.
 * User: glen9
 * Date: 2019/2/14
 * Time: 16:22
 */

namespace addons\cms\controller\wxapp;
use fast\wxapp;

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
        /*   $arr = [
           'city'=>[
               ['name'=>'成都'],
               ['name'=>'北京'],
           ],
           'brand'=>[
               ['zimu'=>'A','brand_list'=>
                   ['id'=>1,'name'=>'奥迪'],
                   ['id'=>2,'name'=>'阿斯顿马丁']
               ],
               ['zimu'=>'B','brand_list'=>
                   ['id'=>1,'name'=>'标志'],
                   ['id'=>2,'name'=>'保时捷' ]
               ],
           ],
           'carList'=>[
               'sell'=>[
                   ['id'=>1,'name'=>'大众新捷达2016款'],
                   ['id'=>2,'name'=>'标志2015款']
               ],
               'buy'=>[
                   ['id'=>1,'name'=>'大众新捷达2015款'],
                   ['id'=>2,'name'=>'标志2015款']

               ],
               'clue'=>[
                   ['id'=>1,'name'=>'大众新捷达2015款'],
                   ['id'=>2,'name'=>'标志2015款']
               ],
           ],
       ];*/


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
        $form_id = $this->request->post('formId');
        $openid = $this->request->post('openid');
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


}