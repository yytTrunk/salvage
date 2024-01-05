<?php


namespace app\program\controller;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\program\BaseController;
use app\program\model\HclmUser;
use app\program\model\Yacht;
use think\Request;
use think\response\Json;

class HCLMController extends BaseController
{


     /**
     * 登录
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login(Request $request):Json
    {
        $param = $request->post();
        $tel = $param['tel'];
        $password = $param['password'];
        $code = $param['code'] ?? false;
        if (!$code) {
            return $this->jsonFail('login失败');
        }

        $user = HclmUser::where('user_name','=',$tel)->where(['status' => 1])->find();
        if (!$user) {
            return $this->jsonFail('用户名错误');
        }

        if (md5($password) !== $user->password) {
            return $this->jsonFail('密码错误');
        }

        $openId = $this->getOpenid($code);
        if (!$openId) {
            return $this->jsonFail('openId获取失败');
        }

        $user->openid = $openId;
        if (!$user->save()) {
            return $this->jsonFail('openId失败');
        }

        return $this->jsonSuccess('登录成功',['user' => $user]);
    }

    /**
     * 注册
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function register(Request $request):Json
    {
        $param = $request->post();
        $tel = $param['tel'];
        $authCode = $param['authCode'];
        $password = $param['password'];
        $rePassword = $param['rePassword'];
        // $role = $param['role'];
        if ($password !== $rePassword) {
            return $this->jsonFail('两次密码不一致');
        }

        if (!\think\facade\Cache::get($tel)) {
            return $this->jsonFail('验证码已过期，请重新获取有效验证码！');
        }

        if (\think\facade\Cache::get($tel) !== $authCode) {
            return $this->jsonFail('请输入正确验证码');
        }

        if (HclmUser::where('user_name','=',$tel)->find()) {
            return $this->jsonFail('您已注册，请直接登录');
        }

        $user = new HclmUser();
        $user->user_name = $tel;

        // if ($role == User::ROLE_10) {
        //     $user->name = $param['name'].'-值班室';
        // }elseif ($role == User::ROLE_30) {
        //     $user->name = $param['name'].'-指挥中心';
        // }else{
        //     $user->name = $param['name'].'-游艇';
        // }

        $user->password = md5($password);
        // $user->role = $role;
        $user->tel = $tel;
        $user->status = 1;
        if (!$user->save()) {
            return $this->jsonFail('注册失败');
        }

        return $this->jsonSuccess('注册成功',$user);
    }

    /**
     * 发送验证码
     * @param Request $request
     * @return Json
     * @throws ClientException
     */
    public function sms(Request $request):Json
    {
        $param = $request->post();
        $tel = $param['tel'];

        $accessKeyId = 'LTAIeaTl3FESlOZk';
        $accessSecret = 'vwgqcBq9h2k6xPCxxAcEYCVE3Exo1v'; //
        $signName = '施工限高预警系统'; //配置签名
        $templateCode = 'SMS_132395440';//配置短信模板编号

        //TODO 随机生成一个6位数
        $authCodeMT = mt_rand(100000,999999);
        //TODO 短信模板变量替换JSON串,友情提示:如果JSON中需要带换行符,请参照标准的JSON协议。
        $jsonTemplateParam = json_encode(['code' => $authCodeMT]);

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
            if ($opRes && $opRes['Code'] == "OK"){
                //保存用户接收记录，当天允许查看留言
                \think\facade\Cache::set($tel, $authCodeMT, 60*5);
                return $this->jsonSuccess('发送成功', ['code' => $authCodeMT]);
            }

        } catch (ClientException $e) {
            return $this->jsonFail('发送失败');
        } catch (ServerException $e) {
            return $this->jsonFail('发送失败');
        }
    }

    //获取openid
    protected function getOpenid($code){
        $appId = $this->hclm_appid;
        $appSecret = $this->hclm_appSecret;
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$appId."&secret=".$appSecret."&js_code=".$code."&grant_type=authorization_code";
        $result = self::curl_https($url);
        $openid = $result['openid'] ?? null;
        if($openid){
            return $openid;
        }

        return false;
    }

    // /**
    //  * 获取验证码
    //  * @return Json
    //  */
    // public function getCode()
    // {
    //     $access = self::getAccessToken();
    //     $url = "https://api.weixin.qq.com/wxa/getwxacode?access_token=".$access;
    //     $data = [
    //         'path' => 'pages/index/index',
    //         'width' => 400,
    //     ];

    //     $qrCode = self::posturl($url,json_encode($data));
    //     $fname = time();
    //     $file ="qrcode/".$fname.".jpg";
    //     //图像码写入
    //     file_put_contents($file,$qrCode);
    //     return $this->jsonSuccess('OK',['file' => $file]);
    // }
}