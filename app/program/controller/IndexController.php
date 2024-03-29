<?php
declare (strict_types = 1);

namespace app\program\controller;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\program\BaseController;
use app\program\model\Alarm;
use app\program\model\AlarmLog;
use app\program\model\Facilit;
use app\program\model\Facility;
use app\program\model\User;
use app\program\model\Yacht;
use think\App;
use think\facade\Cache;
use think\Request;
use think\response\Json;

class IndexController extends BaseController
{
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

    }

    public function index():Json
    {
        return $this->jsonSuccess();
    }

    public function defaultLogin(Request $request):Json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $user = User::where(['id' => $user_id])->find();
        if (!$user || !isset($user->openid) || empty($user->openid)) {
            return $this->jsonFail('登录信息已过期，请重新登录');
        }

        $user->role_desc = $user->getRole();
        $user->complete_desc = $user->getComplete();
        //警情数量
        $alarm_list = [];
        if ($user->role == User::ROLE_10) {
            $alarm_list = Alarm::where('status', '<>', Alarm::STATUS_40)->where('status', '<>', Alarm::STATUS_30)->select();
        }

        if ($user->role == User::ROLE_20) {
            $alarm_list = Alarm::where('status', 'in',  Alarm::STATUS_50 . ',' . Alarm::STATUS_20)->select();
        }

        if ($user->role == User::ROLE_30) {
            $alarm_list = Alarm::where('status', 'in',  Alarm::STATUS_50 . ',' . Alarm::STATUS_20 )->select();
        }

        $user->alarm_total = count($alarm_list);

        //警报数量
        $alarm_log_total = Alarm::where('status', '=', Alarm::STATUS_40)->select();
        $user->alarm_log_total = count($alarm_log_total);

        return $this->jsonSuccess('OK',$user);
    }

    //获取openid
    protected function getOpenid($code){
        $appId = $this->appId;
        $appSecret = $this->appSecret;
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$appId."&secret=".$appSecret."&js_code=".$code."&grant_type=authorization_code";
        $result = self::curl_https($url);
        $openid = $result['openid'] ?? null;
        if($openid){
            return $openid;
        }

        return false;
    }

    public function send() {
        $this->sendMessageToUser();
    }

    public function upload() {
        $file = request()->file('file');
        // 上传到本地服务器
        $savename = \think\facade\Filesystem::disk('public')->putFile( 'topic', $file);
        return $this->jsonSuccess('OK',['file' => $savename]);
    }

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

        $user = User::where('user_name','=',$tel)->where(['status' => 1])->find();
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
        $role = $param['role'];
        if ($password !== $rePassword) {
            return $this->jsonFail('两次密码不一致');
        }

        if (!\think\facade\Cache::get($tel)) {
            return $this->jsonFail('验证码已过期，请重新获取有效验证码！');
        }

        if (\think\facade\Cache::get($tel) !== $authCode) {
            return $this->jsonFail('请输入正确验证码');
        }

        if (User::where('user_name','=',$tel)->find()) {
            return $this->jsonFail('您已注册，请直接登录');
        }

        $user = new User();
        $user->user_name = $tel;

        if ($role == User::ROLE_10) {
            $user->name = $param['name'].'-值班室';
        }elseif ($role == User::ROLE_30) {
            $user->name = $param['name'].'-指挥中心';
        }else{
            $user->name = $param['name'].'-游艇';
        }

        $user->password = md5($password);
        $user->role = $role;
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
        $signName = '落水人员预警系统'; //配置签名
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

    public function admin(Request $request):json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $user = User::where(['id' => $user_id])->find();
        if ($user->role != User::ROLE_40) {
            return $this->jsonFail('您暂未拥有管理员权限');
        }

        $user_10 = User::where(['role' => User::ROLE_10])->select();
        $user_20 = User::where(['role' => User::ROLE_20])->select();
        $user_30 = User::where(['role' => User::ROLE_30])->select();
        $alarm_pending = Alarm::where(['status'=>Alarm::STATUS_10])->select();
        foreach ($alarm_pending as $item) {
            $item->status = $item->getStatusName();
            $item->log = AlarmLog::where(['alarm_id' => $item->id])->select();
        }

        $alarm_complete = Alarm::where(['status' => Alarm::STATUS_40])->select();
        foreach ($alarm_complete as $item) {
            $item->status = $item->getStatusName();
            $item->log = AlarmLog::where(['alarm_id' => $item->id])->select();
        }

        $alarm = Alarm::select();
        foreach ($alarm as $item) {
            $item->status = $item->getStatusName();
            $item->log = AlarmLog::where(['alarm_id' => $item->id])->select();
        }

        $alarm_log = AlarmLog::select();
        $facility = Facility::where(['status' => Facility::STATUS_10])->select();
        return $this->jsonSuccess('OK',[
            'user_10' => $user_10,
            'user_20' => $user_20,
            'user_30' => $user_30,
            'alarm_pending' => $alarm_pending,
            'alarm_complete' => $alarm_complete,
            'alarm' => $alarm,
            'alarm_log' => $alarm_log,
            'facility' => $facility,
        ]);
    }

    /**
     * 查询设备
     */
    public function queryAllFacility(Request $request):json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $user = User::where(['id' => $user_id])->find();
        if ($user->role != User::ROLE_40) {
            return $this->jsonFail('您暂未拥有管理员权限');
        }

        $facility = Facility::where(['status' => Facility::STATUS_10])->select();
        return $this->jsonSuccess('OK',[
            'facility' => $facility,
        ]);
    }

    /**
     * 更新设备
     */
    public function updateFacility(Request $request):json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $user = User::where(['id' => $user_id])->find();
        if ($user->role != User::ROLE_40) {
            return $this->jsonFail('您暂未拥有管理员权限');
        }

        $alarm_status = $param['alarm_status'];
        $id = $param['id'];
        $facility = Facility::where(['id' => $id])->find();
        if ($facility == null) {
            return $this->jsonFail('设备不存在');
        }

        $facility->alarm_status = $alarm_status;
        if (!$facility->save()) {
            return $this->jsonFail('更新失败');
        }

        return $this->jsonSuccess('OK');
    }

    public function lockRole30(Request $request):Json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $id = $param['id'];
        $user = User::where(['id' => $user_id])->find();
        if ($user->role != User::ROLE_40) {
            return $this->jsonFail('您暂未拥有管理员权限');
        }

        $user = User::where(['id' => $id])->find();
        if ($user->role != User::ROLE_30 ) {
            return $this->jsonFail('该用户不是指挥中心');
        }

        $user->status = 2;
        if (!$user->save()) {
            return $this->jsonFail('冻结失败');
        }

        return $this->jsonSuccess('冻结指挥中心成功');
    }

    public function unLockRole30(Request $request):Json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $id = $param['id'];
        $user = User::where(['id' => $user_id])->find();
        if ($user->role != User::ROLE_40) {
            return $this->jsonFail('您暂未拥有管理员权限');
        }

        $user = User::where(['id' => $id])->find();
        if ($user->role != User::ROLE_30 ) {
            return $this->jsonFail('该用户不是指挥中心');
        }

        $user->status = 1;
        if (!$user->save()) {
            return $this->jsonFail('解冻失败');
        }

        return $this->jsonSuccess('解冻指挥中心成功');

    }

    public function lockRole20(Request $request):Json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $id = $param['id'];
        $user = User::where(['id' => $user_id])->find();
        if ($user->role != User::ROLE_40) {
            return $this->jsonFail('您暂未拥有管理员权限');
        }

        $user = User::where(['id' => $id])->find();
        if ($user->role != User::ROLE_20 ) {
            return $this->jsonFail('该用户不是游艇');
        }

        $user->status = 2;
        if (!$user->save()) {
            return $this->jsonFail('冻结失败');
        }

        return $this->jsonSuccess('冻结游艇成功');

    }

    public function unLockRole20(Request $request):Json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $id = $param['id'];
        $user = User::where(['id' => $user_id])->find();
        if ($user->role != User::ROLE_40) {
            return $this->jsonFail('您暂未拥有管理员权限');
        }

        $user = User::where(['id' => $id])->find();
        if ($user->role != User::ROLE_20 ) {
            return $this->jsonFail('该用户不是游艇');
        }

        $user->status = 1;
        if (!$user->save()) {
            return $this->jsonFail('解冻失败');
        }

        return $this->jsonSuccess('解冻游艇成功');

    }

    public function lockRole10(Request $request):Json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $id = $param['id'];
        $user = User::where(['id' => $user_id])->find();
        if ($user->role != User::ROLE_40) {
            return $this->jsonFail('您暂未拥有管理员权限');
        }

        $user = User::where(['id' => $id])->find();
        if ($user->role != User::ROLE_10 ) {
            return $this->jsonFail('该用户不是值班室');
        }

        $user->status = 2;
        if (!$user->save()) {
            return $this->jsonFail('冻结失败');
        }

        return $this->jsonSuccess('冻结游艇成功');

    }

    public function unLockRole10(Request $request):Json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $id = $param['id'];
        $user = User::where(['id' => $user_id])->find();
        if ($user->role != User::ROLE_40) {
            return $this->jsonFail('您暂未拥有管理员权限');
        }

        $user = User::where(['id' => $id])->find();
        if ($user->role != User::ROLE_10 ) {
            return $this->jsonFail('该用户不是值班室');
        }

        $user->status = 1;
        if (!$user->save()) {
            return $this->jsonFail('解冻失败');
        }

        return $this->jsonSuccess('解冻游艇成功');

    }

}
