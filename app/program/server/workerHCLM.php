<?php

namespace app\program\server;

use app\program\controller\IndexController;
use app\program\model\Message;
use app\program\service\CommonService;
use think\facade\Db;
use think\worker\Server;
use Workerman\Lib\Timer;
use app\program\model\HclmAlarm;
use app\program\model\HclmUser;
use app\program\model\HclmFacility;


use function Sodium\add;

//define('HEARTBEAT_TIME', 55);


class workerHCLM extends Server
{
    protected $socket = 'JsonNLHCLM://0.0.0.0:997';
    protected $protocol = 'tcp';
    protected $port = '997' ;
    protected $host = '0.0.0.0';


    public function onWorkerReload($worker) {

    }

    public function onWorkerStart($worker) {

    }

    public function onConnect($connection) {
        $ip = $connection->getRemoteIP();
        if (\think\facade\Cache::has($ip)) {
            // $smsResp = $commonService->sendSMS("1825820361", "11111");
            // $commonService->writeWorkmanLog("发送短信，响应".implode($smsResp));
            $contents = "hclm device 心跳 remoteIp = $ip";
            $commonService = new CommonService();
            $commonService->writeWorkmanLog($contents);

        } else {
            $contents = "hclm device 有新的连接建立 connect. remoteIp = $ip";
            $commonService = new CommonService();
            $commonService->writeWorkmanLog($contents);
            \think\facade\Cache::set($ip, $ip);
        }
        // echo \think\facade\Cache::get('ip');
        // \think\facade\Cache::delete('ip');
        // $res = bin2hex("ALARM");
        // $connection->send($res);
    }

    public function onClose($connection) {
        $ip = $connection->getRemoteIP();
        $contents = "hclm device 断开连接 remoteIp = $ip";
        $commonService = new CommonService();
        $commonService->writeWorkmanLog($contents);
        if (\think\facade\Cache::has($ip)) {
            \think\facade\Cache::delete($ip);
        }
    }

    public function onError($connection, $code, $msg) {
        $ip = $connection->getRemoteIP();
        $contents = "hclm device 连接 $ip error [ $code ] $msg";
        $commonService = new CommonService();
        $commonService->writeWorkmanLog($contents);
    }

    public function onMessage($connection ,$data) {
        // 打印
        var_dump($data);

        $server = new CommonService();
        // 16 进制 395b6419， 字符串为 579110025
        $ID = $data['ID'];
        
        $facility = HclmFacility::where(['facility_id' => $ID])->find();
        $contents = "";
        if ($facility) {
            $contents = $facility -> title;
        }
        $server->writeWorkmanLog("hclm device onMessage消息发送方的设备ID=$ID    名称为  $contents");
        
        // if ($ID == "579110025") {
        //     // 读取缓存
        //     if (\think\facade\Cache::has('alarm')) {
        //         $server->writeWorkmanLog("hclm device 触发一次远程报警器");
        //         $msgAlarm = dechex(01).dechex(00).dechex(01).dechex(01).dechex(01).dechex(01).("AALARM");
        //         $connection->send($msgAlarm);
        //         \think\facade\Cache::delete('alarm');
        //         return;
        //     }
        // }

        // if ($facility != null && $facility->alarm_status == HclmFacility::ALARM_STATUS_1) {
        //     $server->writeWorkmanLog("hclm device 触发一次手动触发报警ID=".$ID);
        //     $facility->alarm_status = HclmFacility::ALARM_STATUS_0;
        //     // $facility->save();

        //     // 触发一次报警命令
        //     $this->alarm($data);
        //     $msgAlarm2 = dechex(01).dechex(00).dechex(01).dechex(01).dechex(01).dechex(01).("AALARM");
        //     // $connection->send($msgAlarm2);

        //     // 新增一条记录
        //     // $model = new Alarm();
        //     // $model->name = '警报';
        //     // $model->BID = $data['ID'];
        //     // $model->longitude = "117.2423E";
        //     // $model->latitude = "2517.2831N";
        //     // $model->status = Alarm::STATUS_10;
        //     // $model->number = 'JB'.rand(0000,9999).date('Ymd',time());
        //     // $model->save();
        // }

        //警报记录
        if ($data['Radar1_Warm'] == 1 || $data['Radar2_Warm'] == 1 || $data['Radar3_Warm'] == 1 || $data['Radar4_Warm'] == 1) {
            //检测经纬度是否为有效数据
        //    if (strpos($data['Longitude'],'E') || strpos($data['Longitude'],'W')) {
            //    if (strpos($data['Latitude'],'S') || strpos($data['Latitude'],'N')) {
                    $longitude = substr($data['Longitude'],0,5);
                    $longitudeE = substr($data['Longitude'],-1,1);
                    $latitude = substr($data['Latitude'],0,4);
                    $latitudeE = substr($data['Latitude'],-1,1);
                    $address = $longitude.$longitudeE.$latitude.$latitudeE;
                    //记录发送时间
                //    if (\think\facade\Cache::get('address')) {
                        \think\facade\Cache::set('address',$address,10);
                        $len = strlen($data['Longitude']);
                        $s = substr($data['Longitude'],0,$len-3);
                        $l = substr($data['Longitude'],-1,1);
                        // 存储
                        $model = new HclmAlarm();
                        $model->name = '警报';
                        $model->BID = $data['ID'];
                        $model->longitude = $s.$l;;
                        $model->latitude = $data['Latitude'];
                        $model->status = HclmAlarm::STATUS_10;
                        $model->number = 'JB'.rand(0000,9999).date('Ymd',time());
                        $model->save();

                        if (!$data['Longitude'] || !$data['Latitude'] || !$model->longitude || !$model->latitude) {
                            $model->delete();
                        } else {

                            // 触发一次摄像头抓拍
                            $this->cameraCapture(); 

                            // 向管理员与值班室发送数据
                            $server->writeWorkmanLog("收到一次报警消息，将进行报警处理，设备 ID=".$data['ID']);

                            // 触发一次报警命令
                            $this->alarm($data);

                            $msgAlarm = dechex(01).dechex(00).dechex(01).dechex(01).dechex(01).dechex(01).("AALARM");
                            $server->writeWorkmanLog("hclm device 触发一次本地报警器，设备 ID=".$data['ID']);
                            $connection->send($msgAlarm);

                            // 写入缓存，用于报警
                            \think\facade\Cache::set('hclmAlarm', 1);
                        }
                //    }
            //    }
        //    }
        }

        // 返回数据
        $res = substr($data['data'], 3, 8);
        $res .= dechex(01).dechex(00).dechex(01).dechex(01).dechex(01).dechex(01);
        $connection->send($res);
    }

    public function alarm($data) {
        $alarm_decode_address = $data['ID'];
        $facility = HclmFacility::where(['facility_id' => $data['ID']])->find();
        if ($facility) {
            $alarm_decode_address = $facility -> title;
        }

        $server = new CommonService();
        $users = HclmUser::where('role', 'in', HclmUser::ROLE_40)->where(['status' => 1])->select();
        foreach ($users as $user) {
            // 发送小程序弹窗告警
            // if ($user->openid) {
            //     $server->sendMessageToUser($user->openid, '警报', $alarm_decode_address);
            // }
            // 发送短信        
            if ($user->tel) {
                // $server->sms($user->tel,$address);
                $smsResp = $server->sendSMS($user->tel, $alarm_decode_address);
            }
        }
    }

    public function cameraCapture() {
        // 获取token
        $server = new CommonService();
        $result = $server->send_post("https://open.ys7.com/api/lapp/token/get?appKey=eec1f9d9ac8a48ea99c59b889bc2291c&appSecret=9c0f0c0dd74365a4f8e5e152d8c06fc9", "");
        var_dump($result);
        if ($result['code'] == 200) {
            var_dump($result->data);
            // 抓拍
            $data = $result['data'];
            $access_token = $data['accessToken'];

            $server->writeWorkmanLog("获取token成功 = ".$access_token);
        }
    }

}