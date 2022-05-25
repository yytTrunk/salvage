<?php
declare (strict_types = 1);

namespace app\program\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Facility extends Model
{
    /**
     * @var integer 正常
     */
    const STATUS_10 = 10;
    /**
     * @var integer 故障
     */
    const STATUS_20 = 20;

}
