<?php
declare (strict_types = 1);

namespace app\program\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class GdsUser extends Model
{
    // public $role_desc;
    // /**
    //  * @var integer 值班室
    //  */
    // const ROLE_10 = 10;
    // /**
    //  * @var integer 游艇
    //  */
    // const ROLE_20 = 20;
    // /**
    //  * @var integer 指挥中心
    //  */
    // const ROLE_30 = 30;

    /**
     *@var integer 管理员
     */
    const ROLE_40 = 40;

    // /**
    //  *@var integer 待完善
    //  */
    // const COMPLETE_PENDING = 10;

    // /**
    //  *@var integer 已完善
    //  */
    // const COMPLETE_END = 20;


    public function getRoles() {
        return [
            // self::ROLE_10 => '值班室',
            // self::ROLE_20 => '游艇',
            // self::ROLE_30 => '指挥中心',
            self::ROLE_40 => '安全总监'
        ];
    }

    public function getRole() {
        $roles = $this->getRoles();
        return isset($roles[$this->role]) ?$roles[$this->role] : '未知' ;
    }


    // public function getCompletes() {
    //     return [
    //         self::COMPLETE_PENDING => '待完善',
    //         self::COMPLETE_END =>  '已完善',
    //     ];
    // }

    // public function getComplete() {
    //     $complete =  $this->getCompletes();
    //     return isset($complete[$this->is_complete]) ? $complete[$this->is_complete] : '未知' ;
    // }
}
