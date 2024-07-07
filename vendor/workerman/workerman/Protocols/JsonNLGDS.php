<?php


namespace Workerman\Protocols;

class JsonNLGDS
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
        // 001#0:1:209:10:10:10:/
        $data = $recv_buffer;
        var_dump($data);

        $arr = explode(":", $data);
        if (count($arr) < 6) {
            return 0; 
        }

        $res = [
            'Device_ID' => $arr[0],
            'Alarm' => $arr[1],
            'O2' => $arr[2] / 10,
            'CO' => $arr[3],
            'H2S' => $arr[4] / 10,
            'CH4' =>  $arr[5],
            'data' => $recv_buffer
        ];
        return $res;


        // // 注册报文
        // // $0E,R,1000292001,#
        // // 需要平台应答，应答内容固定 $08,RA,0,1,#\n
        // if ("R" == $arr[1]) {
        //     $res = [
        //         'Data_Type' => $arr[1], 
        //         'Device_ID' => $arr[2],
        //     ];
        //     return $res;
        // }

        // $self = new self();
        // $device_id = $self->convertTime($arr[0]);
        // $longitude = $self->convertLongitude($arr[6]);
        // $latitude = $self->convertLatitude($arr[4]);
        // // 正常数据包
        // $res = [
        //     'Len' => $arr[0],
        //     'Data_Type' => $arr[1],
        //     'Device_ID' => $arr[2],
        //     'Latitude' => $latitude,
        //     'Longitude' => $longitude,
        //     'Time' =>  $time,
        //     'Vaild_data' => $arr[15],
        //     'Battery_Capacity' => $arr[17],
        //     'data' => $recv_buffer
        // ];
        // return $res;
    }

    public static function encode($data)
    {
        return $data;
    }
}