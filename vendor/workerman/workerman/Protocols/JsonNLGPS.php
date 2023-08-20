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

        $arr = explode(",", $data);

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