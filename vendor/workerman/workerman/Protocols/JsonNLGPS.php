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

        $self = new self();
        $time = $self->convertTime($arr[12]);
        // 正常数据包
        $res = [
            'Len' => $arr[0],
            'Data_Type' => $arr[1],
            'Device_ID' => $arr[2],
            'Latitude' => $arr[4],
            'Longitude' => $arr[6],
            'Time' =>  $time,
            'Vaild_data' => $arr[15],
            'Battery_Capacity' => $arr[17],
            'data' => $recv_buffer
        ];
        return $res;
    }

    public static function encode($data)
    {
        return $data;
    }

    protected function convertTime($input) 
    {
        $month = substr($input, 0, 2);
        $day = substr($input, 2, 2);
        $year = substr($input, 4, 2);
        $hour = substr($input, 7, 2);
        $minute = substr($input, 9, 2);
        $second = substr($input, 11, 2);
        
        // 构建格林威治时间的日期时间对象
        $gmtDateString = "20$year-$month-$day $hour:$minute:$second";
        $gmtDateTime = new \DateTime($gmtDateString, new \DateTimeZone('GMT'));
        
        // 将时区切换到中国标准时间（CST）
        $chinaTimeZone = new \DateTimeZone('Asia/Shanghai');
        $gmtDateTime->setTimezone($chinaTimeZone);
        
        // 格式化输出
        $output = $gmtDateTime->format('Y-m-d H:i:s');
        
        echo "输入格林威治时间：$input\n";
        echo "转换后的中国时间：$output\n";
        return $output;
    }
}