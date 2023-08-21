<?php


namespace Workerman\Protocols;

class JsonNLGPS
{
    /**
     * input 先于 decode 执行
     */
    public static function input($recv_buffer)
    {
        $data = $recv_buffer;
        $len = strlen($data);

        return $len;
    }

    public static function decode($recv_buffer)
    {
        $data = $recv_buffer;
        var_dump($data);

        $arr = explode(",", $data);
        // 注册报文
        // $0E,R,1000292001,#
        // 需要平台应答，应答内容固定 $08,RA,0,1,#\n
        if ("R" == $arr[1]) {
            $res = [
                'Data_Type' => $arr[1], 
                'Device_ID' => $arr[2],
            ];
            return $res;
        }
        // 正常数据包
        $res = [
            'Len' => $arr[0],
            'Data_Type' => $arr[1],
            'Device_ID' => $arr[2],
            'Latitude' => $arr[4],
            'Longitude' => $arr[6],
            'Time' =>  $arr[12],
            'Battery_Capacity' => $arr[17],
            'data' => $recv_buffer
        ];
        return $res;
    }

    public static function encode($data)
    {
        return $data;
    }
}