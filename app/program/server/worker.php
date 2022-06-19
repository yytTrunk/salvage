<?php


namespace app\program\server;


use app\program\controller\IndexController;
use app\program\model\Alarm;
use app\program\model\Message;
use app\program\model\User;
use app\program\service\CommonService;
use think\Cache;
use think\facade\Db;
use think\worker\Server;
use Workerman\Lib\Timer;

use function Sodium\add;

define('HEARTBEAT_TIME', 55);


class worker extends Server
{
//    protected $socket = 'tcp://0.0.0.0:999';
    protected $socket = 'JsonNL://0.0.0.0:999';
    protected $protocol = 'tcp';
    protected $port = '999' ;
    protected $host = '0.0.0.0';


    public function onWorkerReload($worker) {

    }

    public function onWorkerStart($worker) {
        //检测心跳
//        Timer::add(50,function () use ($worker) {
//           $timeNow = time();
//           foreach ($worker->connections as $connection) {
//               if (empty($connection->lastMessageTime)) {
//                   $connection->lastMessageTime = time();
//                    continue;
//               }
//
//               if ($timeNow - $connection->lastMessageTime > HEARTBEAT_TIME) {
//                   $connection->close();
//               }
//           }
//        });
    }

    public function onConnect($connection) {
        $ip = $connection->getRemoteIP();
        if (\think\facade\Cache::has($ip)) {
            // $smsResp = $commonService->sendSMS("1825820361", "11111");
            // $commonService->writeWorkmanLog("发送短信，响应".implode($smsResp));

        } else {
            $contents = "有新的连接建立 remoteIp = $ip";
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
        $contents = "断开连接 remoteIp = $ip";
        $commonService = new CommonService();
        $commonService->writeWorkmanLog($contents);
        if (\think\facade\Cache::has($ip)) {
            \think\facade\Cache::delete($ip);
        }
    }

    public function onError($connection, $code, $msg) {
        $ip = $connection->getRemoteIP();
        $contents = "连接 $ip error [ $code ] $msg";
        $commonService = new CommonService();
        $commonService->writeWorkmanLog($contents);
    }

    public function onMessage($connection ,$data) {
        // 打印
        var_dump($data);

        $server = new CommonService();
        // 16 进制 395b6419， 字符串为 579110025
        $ID = $data['ID'];
        if ($ID == "579110025") {
            // 读取缓存
            if (\think\facade\Cache::has('alarm')) {
                $msgAlarm = dechex(01).dechex(00).dechex(01).dechex(01).dechex(01).dechex(01).("ALARM");

                $server->writeWorkmanLog("触发一次远程报警器");
                $connection->send($msgAlarm);
                \think\facade\Cache::delete('alarm');
                return;
            }
        }

        //警报记录
        if ($data['Radar1_Warm'] == 1 || $data['Radar2_Warm'] == 1 || $data['Radar3_Warm'] == 1 || $data['Radar4_Warm'] == 1) {
            //检测经纬度是否为有效数据
//            if (strpos($data['Longitude'],'E') || strpos($data['Longitude'],'W')) {
//                if (strpos($data['Latitude'],'S') || strpos($data['Latitude'],'N')) {
                    $longitude = substr($data['Longitude'],0,5);
                    $longitudeE = substr($data['Longitude'],-1,1);
                    $latitude = substr($data['Latitude'],0,4);
                    $latitudeE = substr($data['Latitude'],-1,1);
                    $address = $longitude.$longitudeE.$latitude.$latitudeE;
                    //记录发送时间
//                    if (\think\facade\Cache::get('address')) {
                        \think\facade\Cache::set('address',$address,10);
                        $len = strlen($data['Longitude']);
                        $s = substr($data['Longitude'],0,$len-3);
                        $l = substr($data['Longitude'],-1,1);
                        // 存储
                        $model = new Alarm();
                        $model->name = '警报';
                        $model->BID = $data['ID'];
                        $model->longitude = $s.$l;;
                        $model->latitude = $data['Latitude'];
                        $model->status = Alarm::STATUS_10;
                        $model->number = 'JB'.rand(0000,9999).date('Ymd',time());
                        $model->save();
                        if (!$data['Longitude'] || !$data['Latitude'] || !$model->longitude || !$model->latitude) {
                            $model->delete();
                        } else {
                            // 向管理员与值班室发送数据
                            $server->writeWorkmanLog("收到一次报警消息，将进行报警处理，设备 ID=".$data['ID']);
                            $users = User::where('role', 'in', User::ROLE_40.','.User::ROLE_10)->where(['status' => 1])->select();
                            foreach ($users as $user) {
                                // 发送小程序弹窗告警
                                if ($user->openid) {
                                    $server->sendMessageToUser($user->openid, '警报', $address);
                                }
                                // 发送短信        
                                if ($user->tel) {
                                    // $server->sms($user->tel,$address);
                                    $smsResp = $server->sendSMS($user->tel, $address);
                                }
                            }
                            // 写入缓存，用于报警
                            \think\facade\Cache::set('alarm', 1);
                        }
//                    }
//                }
//            }
        }

        // 返回数据
        $res = substr($data['data'], 3, 8);
        $res .= dechex(01).dechex(00).dechex(01).dechex(01).dechex(01).dechex(01);
        $connection->send($res);
    }
}