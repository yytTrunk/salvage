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
        $commonService = new CommonService();
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