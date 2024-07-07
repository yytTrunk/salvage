<?php

namespace app\program\server;

use app\program\service\CommonService;
use think\worker\Server;
use app\program\model\GdsAlarm;
use app\program\model\GdsUser;
use app\program\model\GdsFacility;
use app\program\model\GdsAlarmLog;

use function Sodium\add;

//define('HEARTBEAT_TIME', 55);


class workerGDS extends Server
{
    protected $socket = 'JsonNLGDS://0.0.0.0:990';
    protected $protocol = 'tcp';
    protected $port = '990' ;
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
            $contents = "gds device 心跳 remoteIp = $ip";
            $commonService = new CommonService();
            $commonService->writeWorkmanLog($contents);

        } else {
            $contents = "gds device 有新的连接建立 connect. remoteIp = $ip";
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
        $contents = "gds device 断开连接 remoteIp = $ip";
        $commonService = new CommonService();
        $commonService->writeWorkmanLog($contents);
        if (\think\facade\Cache::has($ip)) {
            \think\facade\Cache::delete($ip);
        }
    }

    public function onError($connection, $code, $msg) {
        $ip = $connection->getRemoteIP();
        $contents = "gds device 连接 $ip error [ $code ] $msg";
        $commonService = new CommonService();
        $commonService->writeWorkmanLog($contents);
    }


    // $res = [
    // 'Device_ID' => $arr[0],
    // 'Alarm' => $arr[1],
    // 'O2' => $arr[2] / 10,
    // 'CO' => $arr[3],
    // 'H2S' => $arr[4] / 10,
    // 'CH4' =>  $arr[5],
    // 'data'
    public function onMessage($connection ,$data) {
        // 打印
        var_dump($data);

        $server = new CommonService();
        $facility_id = $data['Device_ID'];
        $alarm = $data['Alarm'];
        
        $facility = GdsFacility::where(['facility_id' => $facility_id])->find();
        $contents = "";
        if ($facility) {
            $contents = $facility -> title;
        } else {
            $contents = $facility_id;
        }
        $server->writeWorkmanLog("gds device onMessage消息发送方的设备ID=$facility_id    名称为  $contents   alarm = $alarm");

        $project_id = $facility->project_id;
        // Alarm 为 1， 表示需要为异常值
        // 保存数据
        if ($data['Alarm'] == 1) {
            $model = new GdsAlarm();
            $model->alarm = $alarm;

            $model->project_id = $project_id;
            $model->facility_id = $facility_id;
            $model->o2 = $data['O2'];
            $model->co = $data['CO'];
            $model->h2s = $data['H2S'];
            $model->ch4 = $data['CH4'];
            $model->status = GdsAlarm::STATUS_10;
            $model->number = 'JB'.rand(0000,9999).date('Ymd',time());
            $model->save();

            // 查询上一次告警记录,距离这次时间，如果太短，就不重复进行短信通知，默认配置时间间隔 1 分钟。
            $latest_alarm = GdsAlarm::where(['facility_id' => $facility_id])->orderBy('create_time', 'desc')->first();
            $now = time();
            $diff = $now - $latest_alarm->create_time;
            if ($diff > 60) {
                // 触发一次报警命令
                $this->alarm($project_id, $contents);
            } else {
                $server->writeWorkmanLog("与上次触发时间间隔小于 1 分钟，不进行短信告警通知。");
            }
        }
    }

    public function alarm($project_id, $facility_title) {
        $server = new CommonService();
        $users = GdsUser::where(['project_id' => $project_id])->where(['status' => 1])->select();
        foreach ($users as $user) {
            // 发送小程序弹窗告警
            // if ($user->openid) {
            //     $server->sendMessageToUser($user->openid, '警报', $alarm_decode_address);
            // }
            // 发送短信        
            if ($user->tel) {
                // $server->sms($user->tel,$address);
                // 判断是否接收短信告警，1是接收，0是不接收
                if ($user->receive_tel == 1) {
                    $smsResp = $server->sendSMS($user->tel, $facility_title);
                }
            }
        }
    }
}