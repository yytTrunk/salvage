<?php

namespace app\program\server;

use app\program\controller\IndexController;
use app\program\model\Message;
use app\program\model\GpsLog;
use app\program\service\CommonService;
use think\facade\Db;
use think\worker\Server;
use Workerman\Lib\Timer;

use function Sodium\add;

//define('HEARTBEAT_TIME', 55);


class workerLocation extends Server
{
    protected $socket = 'JsonNLGPS://0.0.0.0:998';
    protected $protocol = 'tcp';
    protected $port = '998' ;
    protected $host = '0.0.0.0';


    public function onWorkerReload($worker) {

    }

    public function onWorkerStart($worker) {

    }

    public function onConnect($connection) {
        $ip = $connection->getRemoteIP();
        $contents = "gps device connect. RemoteIp = $ip";
        $commonService = new CommonService();
        $commonService->writeWorkmanLog($contents);
    }

    public function onClose($connection) {

    }


    public function onError($connection, $code, $msg) {
        echo "error [ $code ] $msg\n";
    }

    public function onMessage($connection, $data) {
        $ip = $connection->getRemoteIP();
        $commonService = new CommonService();
        
        // 判断是不是注册包
        if ("R" == $data['Data_Type']) {
            $device_id = $data['Device_ID'];
            $contents = "ip = $ip, deviceId = $device_id, 注册包";
            $commonService->writeWorkmanLog($contents);

            // 24 30 38 2c 52 41 2c 30 2c 31 2c 23 0A
            // 需要平台应答，应答内容固定 $08,RA,0,1,#\n
            $cmd = "\x24\x30\x38\x2c\x52\x41\x2c\x30\x2c\x31\x2c\x23\x0A";
            $connection->send($cmd);
            return;
        }


        // $res = [
        //     'Len' => $arr[0],
        //     'Data_Type' => $arr[1],
        //     'Device_ID' => $arr[2],
        //     'Latitude' => $arr[4],
        //     'Longitude' => $arr[6],
        //     'Time' =>  $arr[12],
        //     'Battery_Capacity' => $arr[17],
        //     'data' => $recv_buffer
        // ];
        $device_id = $data['Device_ID'];
        $origin_data = $data['data'];

        $contents = "ip = $ip, gps device $device_id send msg. origin_msg = $origin_data";
        $commonService->writeWorkmanLog($contents);
 
        // 存储数据库
        $model = new GpsLog();
        $model->device_id = $data['Device_ID'];
        $model->longitude = $data['Longitude'];
        $model->latitude = $data['Latitude'];
        $model->upload_time = $data['Time'];
        $model->battery_capacity = $data['Battery_Capacity'];
        $model->save();
    }
}