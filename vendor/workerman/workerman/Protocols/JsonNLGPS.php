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
        $longitude = $self->convertLongitude($arr[6]);
        $latitude = $self->convertLatitude($arr[4]);
        // 正常数据包
        $res = [
            'Len' => $arr[0],
            'Data_Type' => $arr[1],
            'Device_ID' => $arr[2],
            'Latitude' => $longitude,
            'Longitude' => $latitude,
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
        $day = substr($input, 0, 2);
        $month = substr($input, 2, 2);
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
        return $output;
    }

    protected function convertLongitude($dms) 
    {
        // 将输入的字符串按照点号分割成度、分和秒的部分
        $parts = explode('.', $dms);
        
        // 确保分和秒的部分存在
        if (count($parts) != 2) {
            return $dms; // 输入格式不正确
        }
        
        $degrees = intval(substr($parts[0], 0, -2)); // 度部分
        $minutes = intval(substr($parts[0], -2)) + floatval($parts[1]) / 10000; // 分部分，转换为小数
        
        // 计算度的浮点数表示
        $decimal = $degrees + $minutes / 60;
        
        return $decimal;
    }

    protected function convertLatitude($dms) {
        // 将输入的字符串按照点号分割成度和分的部分
        $parts = explode('.', $dms);
        
        // 确保分的部分存在
        if (count($parts) != 2) {
            return $dms; // 输入格式不正确
        }
        
        $degrees = intval(substr($parts[0], 0, -2)); // 度部分
        $minutes = floatval(substr($parts[0], -2) . '.' . $parts[1]); // 分部分，转换为小数
        
        // 计算度的浮点数表示
        $decimal = $degrees + $minutes / 60;
        
        return $decimal;
    }
}