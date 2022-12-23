<?php


namespace Workerman\Protocols;

class JsonNL
{
    public static function input($recv_buffer)
    {
        $data = $recv_buffer;
        $len = strlen($data);
        for ($i=0; $i<$len; $i++) {
            // 转化为16进制
            $dump[] = sprintf("%s", sprintf("%02x", ord($data[$i])));
        }

        // 如果id为 579110025， 为报警器设备，直接放行 
        // echo $dump[4];
        // echo $dump[5];
        // echo $dump[6];
        // echo $dump[7];

        // if ($len >= 8 && $dump[4]=="35" && $dump[5]="36" && $dump[6]="37" && $dump[7]="38") {
        //     echo "222";
        //     // return $len;
        // }
        // echo "333";

        // 比较消息长度是否合法
        $msg_len = hexdec($dump[0]);
        if (strlen($recv_buffer) < $msg_len) {
            return 0 ;
        } else {
            return $msg_len;
        }
    }

    public static function decode($recv_buffer)
    {
        $data = $recv_buffer;
        $len = strlen($data);
        $dump = [];
        $test = '';
        for ($i=0;$i<$len;$i++){
            $dump[] = sprintf("%s",sprintf("%02x",ord($data[$i])));
            if ($i>=12&&$i<16) {
                $test .= $data[$i];
            }
        }

////        $a = hexdec('01234567');
////        $test = hexdec($test);
////        var_dump($test);
//        var_dump($dump);
        $ID = '';
        for ($i = 4;$i<8;$i++) {
            $ID .= hexdec($dump[$i]);
        }

        echo $ID;

        $res = '';
        $gps_state = hexdec($dump[34]);
        $time = '';


        //时间
        for ($i = 35 ;$i<45;$i++) {
            $time.= chr(hexdec($dump[$i]));
        }

        //位置
        $latitude = '';
        for ($i = 45 ;$i<55;$i++) {
            $latitude.= chr(hexdec($dump[$i]));
        }

        $longitude = '';
        for ($i = 55 ;$i<=65;$i++) {
            $longitude.= chr(hexdec($dump[$i]));
        }

        //设备报警状态
        $Radar1_Warm = hexdec($dump[28]);
        $Radar2_Warm = hexdec($dump[29]);
        $Radar3_Warm = hexdec($dump[30]);
        $Radar4_Warm = hexdec($dump[31]);
        $Alarm_Cnt  = hexdec($dump[32]);
        //报警时间
        $Radar1_Cnt = '';
        for ($i = 12;$i<16;$i++) {
            $Radar1_Cnt .= hexdec($dump[$i]);
        }

        $Radar2_Cnt = '';
        for ($i = 16;$i<20;$i++) {
            $Radar2_Cnt .= hexdec($dump[$i]);
        }

        $Radar3_Cnt = '';
        for ($i = 20;$i<24;$i++) {
            $Radar3_Cnt .= hexdec($dump[$i]);
        }

        $Radar4_Cnt = '';
        for ($i = 24;$i<28;$i++) {
            $Radar4_Cnt .= hexdec($dump[$i]);
        }

        
        $res = [
            'ID' => $ID,
            'GPS' => $gps_state,
            'Time' => $time,
            'Latitude' => $latitude,
            'Longitude' => $longitude,
            'Alarm_Cnt' => $Alarm_Cnt,
            'Radar1_Warm' => $Radar1_Warm,
            'Radar1_Cnt' => $Radar1_Cnt,
            'Radar2_Warm' => $Radar2_Warm,
            'Radar2_Cnt' => $Radar2_Cnt,
            'Radar3_Warm' => $Radar3_Warm,
            'Radar3_Cnt' => $Radar3_Cnt,
            'Radar4_Warm' => $Radar4_Warm,
            'Radar4_Cnt' => $Radar4_Cnt,
            'data' => $recv_buffer
        ];
        return $res;
    }

    public static function encode($data)
    {
        return $data;
    }
}