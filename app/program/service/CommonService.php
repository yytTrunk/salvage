<?php
declare (strict_types = 1);

namespace app\program\service;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;


class CommonService
{
    public $appId="wx077c70908ad51e20";
    public $appSecret="613c72472186bc7277a6b8770248eaed";

    /**
     * curl请求
     */
    protected function curl_https($url =''){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);
        if(empty($result)){
            return false;
        }
        return (array)json_decode($result);
    }

    /**
     * post
     * @param $url
     * @param $data
     * @return mixed
     */
    public function send_post($url, $data){
        $headerArray =array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output,true);
    }

    protected function getAccessToken(){
        $appId = $this->appId;
        $appSecret = $this->appSecret;
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appId."&secret=".$appSecret;
        $result = self::curl_https($url);
        $accessToken = $result['access_token']??null;
        if($accessToken){
            return $accessToken;
        }

        return false;
    }

    public function sendMessageToUser($openId = 'oMDMJ5XJ0XfwpnDhQfM3N3M7Su-E',$message = '测试消息',$address = 'N132,E105'){
        $accessToken = self::getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token='.$accessToken;
        $to_time = date("Y年m月d日 H:i:s",time()) ;
        $data = '{
                          "touser": "'.$openId.'",
                          "template_id": "f0KLhAYV_zjNUAlFLhsrgeSW91-kOlj6WEU02xu2Hak",
                          "page": "pages/index/index",
                          "miniprogram_state":"developer",
                          "lang":"zh_CN",
                          "data": {
                              "thing1": {
                                  "value": "'.$message.'"
                              },
                              "thing2": {
                                  "value": "'.$address.'"
                              },
                              "date5": {
                                  "value": "'.$to_time.'"
                              }
                          }
                        }';
        return $this->send_post($url,$data);
    }

    public function sendSMS($tel = '', $message=''){
        $tel = "18258203691";
        $url = 'https://106.ihuyi.com/webservice/sms.php?method=Submit&account=C38501347&password=8ca63faea43eb88621b9f4b800e87894&mobile='.$tel.'&content=新预警通知！报警地址:'.$message.'，请您登陆小程序查看！';

        return $this->curl_https($url);
    }

    /**
     * @param string $tel
     * @param string $message
     * @throws ClientException
     */
    public function sms($tel = '',$message='')
    {
        $accessKeyId = 'LTAIeaTl3FESlOZk';
        $accessSecret = 'vwgqcBq9h2k6xPCxxAcEYCVE3Exo1v'; //
        $signName = '落水人员预警系统'; //配置签名
        $templateCode = 'SMS_229480338';//配置短信模板编号

        //TODO 随机生成一个6位数
        $authCodeMT = mt_rand(100000,999999);
        //TODO 短信模板变量替换JSON串,友情提示:如果JSON中需要带换行符,请参照标准的JSON协议。
        $jsonTemplateParam = json_encode(['address' => $message]);

        //return $jsonTemplateParam;
        AlibabaCloud::accessKeyClient($accessKeyId, $accessSecret)
            ->regionId('cn-hangzhou')
            ->asGlobalClient();
        try {
            $result = AlibabaCloud::rpcRequest()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => [
                        'RegionId' => 'cn-hangzhou',
                        'PhoneNumbers' => $tel,//目标手机号
                        'SignName' => $signName,
                        'TemplateCode' => $templateCode,
                        'TemplateParam' => $jsonTemplateParam,
                    ],
                ])
                ->request();

            $opRes = $result->toArray();


        } catch (ClientException $e) {

        } catch (ServerException $e) {

        }
    }
}
