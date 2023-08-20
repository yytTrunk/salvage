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

        $res = [
            // 'ID' => $ID,
            // 'GPS' => $gps_state,
            // 'Time' => $time,
            // 'Latitude' => $latitude,
            // 'Longitude' => $longitude,
            // 'Alarm_Cnt' => $Alarm_Cnt,
            // 'Radar1_Warm' => $Radar1_Warm,
            // 'Radar1_Cnt' => $Radar1_Cnt,
            // 'Radar2_Warm' => $Radar2_Warm,
            // 'Radar2_Cnt' => $Radar2_Cnt,
            // 'Radar3_Warm' => $Radar3_Warm,
            // 'Radar3_Cnt' => $Radar3_Cnt,
            // 'Radar4_Warm' => $Radar4_Warm,
            // 'Radar4_Cnt' => $Radar4_Cnt,
            'data' => $recv_buffer
        ];
        return $res;
    }

    public static function encode($data)
    {
        return $data;
    }
}