<?php
declare (strict_types = 1);

namespace app\program\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class HclmAlarm extends Model
{
    //
    /**
     * @var integer 报警中
     */
    const STATUS_10 = 10;
    /**
     * @var integer 接警中
     */
    const STATUS_20 = 20;
    /**
     * @var integer 警报取消
     */
    const STATUS_30 = 30;
    /**
     * @var integer 完成救援
     */
    const STATUS_40 = 40;
    /**
     * @var integer 传达中
     */
    const STATUS_50 = 50;

    public function getStatusNames() {
        return [
            self::STATUS_10 => '报警中',
            self::STATUS_20 => '接警中',
            self::STATUS_30 => '警报取消',
            self::STATUS_40 => '完成救援',
            self::STATUS_50 => '传达中',
        ];
    }

    public function getStatusName() {
        $status = $this->getStatusNames();
        return isset($status[$this->status]) ? $status[$this->status] :'未知' ;
    }
}
