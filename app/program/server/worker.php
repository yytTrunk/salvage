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
    }

    public function onClose($connection) {

    }


    public function onError($connection, $code, $msg) {
        echo "error [ $code ] $msg\n";
    }

    public function onMessage($connection ,$data) {
        // dayin
        var_dump($data);
        //警报记录
        if ($data['Radar1_Warm'] == 1 || $data['Radar2_Warm'] == 1 || $data['Radar3_Warm'] == 1 || $data['Radar4_Warm'] == 1) {
            //检测经纬度是否为有效数据
//            if (strpos($data['Longitude'],'E') || strpos($data['Longitude'],'W')) {
//                if (strpos($data['Latitude'],'S') || strpos($data['Latitude'],'N')) {
                    $server = new CommonService();
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
                        // cunchu
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
                        }else{
                            //管理员与值班室发送数据
                            $users = User::where('role','in',User::ROLE_40.','.User::ROLE_10)->where(['status' => 1])->select();
                            foreach ($users as $user) {

                                if ($user->openid) {
                                    $server->sendMessageToUser($user->openid,'警报',$address);
                                }

                                if ($user->tel) {
                                    // $server->sms($user->tel,$address);
                                    $server->sendSMS($user->tel, $address);
                                }

                            }
                        }
//                    }
//                }
//            }
        }

        $res = substr($data['data'],3,8);
        $res .= dechex(01).dechex(00).dechex(01).dechex(01).dechex(01).dechex(01);
        $connection->send($res);
    }
}