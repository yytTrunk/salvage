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

//define('HEARTBEAT_TIME', 55);


class workerCamera extends Server
{
    protected $socket = 'JsonNL://0.0.0.0:999';
    protected $protocol = 'tcp';
    protected $port = '999' ;
    protected $host = '0.0.0.0';


    public function onWorkerReload($worker) {

    }

    public function onWorkerStart($worker) {

    }

    public function onConnect($connection) {
        echo "hello"
    }

    public function onClose($connection) {

    }


    public function onError($connection, $code, $msg) {
        echo "error [ $code ] $msg\n";
    }

    public function onMessage($connection ,$data) {
        var_dump($data);
        $connection->send($data);
    }
}