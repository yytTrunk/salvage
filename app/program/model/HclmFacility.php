<?php
declare (strict_types = 1);

namespace app\program\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class HclmFacility extends Model
{
    /**
     * @var integer 正常
     */
    const STATUS_10 = 10;
    /**
     * @var integer 故障
     */
    const STATUS_20 = 20;

    /**
     * @var integer 报警开
     */
    const ALARM_STATUS_0 = 0;

    /**
     * @var integer 报警关
     */
    const ALARM_STATUS_1 = 1;
}
