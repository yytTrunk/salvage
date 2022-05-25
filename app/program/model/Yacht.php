<?php
declare (strict_types = 1);

namespace app\program\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Yacht extends Model
{
    /**
     * @var integer 正常
     */
    const STATUS_10 = 10;
    /**
     * @var integer 冻结
     */
    const STATUS_20 = 20;
    /**
     * @var integer 待审核
     */
    const STATUS_30 = 30;

    public function getStatusNames() {
        return [
            self::STATUS_10 => '正常',
            self::STATUS_20 => '冻结',
            self::STATUS_30 => '待审核',
        ];
    }

    public function getStatusName() {
        $status = $this->getStatusNames();
        return isset($status[$this->status]) ? $status[$this->status] :'未知' ;
    }

}
