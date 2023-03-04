<?php


namespace app\program\controller;


use app\program\BaseController;
use app\program\model\Alarm;
use app\program\model\AlarmLog;
use app\program\model\Facility;
use app\program\model\User;
use app\program\service\CommonService;
use app\Request;
use think\facade\Db;
use think\response\Json;

class DutyController extends BaseController
{
    /**
     * 警报列表
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function alarmLists(Request $request): Json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $user = User::where(['id' => $user_id])->find();
        $data = [];
        if ($user->role == User::ROLE_10) {
            $data = Alarm::where('status', '<>', Alarm::STATUS_40)->where('status', '<>', Alarm::STATUS_30)->order('create_time','desc')->select();
        }

        if ($user->role == User::ROLE_20) {
            $data = Alarm::where('status', 'in',  Alarm::STATUS_50 . ',' . Alarm::STATUS_20)->order('create_time','desc')->select();
        }

        if ($user->role == User::ROLE_30) {
            $data = Alarm::where('status', 'in',  Alarm::STATUS_50 . ',' . Alarm::STATUS_20 )->order('create_time','desc')->select();
        }

        $facility_arrs = Facility::where(['status' => Facility::STATUS_10])->select()->toArray();
        $facility_maps = array();
        array_map(function($item){
            global $facility_maps;
            $facility_maps[ $item['facility_id'] ]= $item['title'];
        }, $facility_arrs);

        if ($data) {
            foreach ($data as $item) {
                global $facility_maps;
                $item->status = $item->getStatusName();
                $item->log = AlarmLog::where(['alarm_id' => $item->id])->select();
                $item->facility_name = empty($facility_maps[$item->BID]) ? "" : $facility_maps[$item->BID];
            }
        }


        return $this->jsonSuccess('OK', $data);
    }

    /**
     * 警报记录
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function alarmLogLists(): Json
    {
        $data = Alarm::where('status', 'in', Alarm::STATUS_30.','.Alarm::STATUS_40)->order('create_time','desc')->select();

        foreach ($data as $item) {
            $item->status = $item->getStatusName();
            $item->log = AlarmLog::where(['alarm_id' => $item->id])->select();
        }
        return $this->jsonSuccess('OK', $data);
    }

    /**
     * 接警
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function acceptAlarm(Request $request): Json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $alarm_id = $param['alarm_id'];
        $user = User::where(['id' => $user_id])->find();
        $alarm = Alarm::where(['id' => $alarm_id])->find();
        if ($alarm->status != Alarm::STATUS_50) {
            return $this->jsonFail('警报暂未传达');
        }

        Db::startTrans();
        $model = new AlarmLog();
        $model->user_id = $user_id;
        $model->alarm_id = $alarm_id;
        $model->log = $user->name . '接警了编号：' . $alarm->number;
        if (!$model->save()) {
            Db::rollback();
            return $this->jsonFail('接警失败');
        }

        //隶属状态为传达中才可接警
        $alarm->status = Alarm::STATUS_20;
        if (!$alarm->save()) {
            Db::rollback();
            return $this->jsonFail('接警失败');
        }

        Db::commit();
        return $this->jsonSuccess('OK');
    }

    /**
     * 取消警报
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cancelAlarm(Request $request): Json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $alarm_id = $param['alarm_id'];
        $user = User::where(['id' => $user_id])->find();
        $alarm = Alarm::where(['id' => $alarm_id])->find();
        Db::startTrans();
        $model = new AlarmLog();
        $model->user_id = $user_id;
        $model->alarm_id = $alarm_id;
        $model->log = $user->name . '取消了警报编号：' . $alarm->number;
        if (!$model->save()) {
            Db::rollback();
            return $this->jsonFail('取消警报失败');
        }

        $alarm->status = Alarm::STATUS_30;

        if (!$alarm->save()) {
            Db::rollback();
            return $this->jsonFail('取消警报失败');
        }

        Db::commit();
        return $this->jsonSuccess('OK');
    }

    /**
     * 传达警报
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function conveyAlarm(Request $request): Json
    {
        $param = $request->post();
        $user_id = $param['user_id']; 
        $alarm_id = $param['alarm_id'];
        $user = User::where(['id' => $user_id])->find();
        $alarm = Alarm::where(['id' => $alarm_id])->find();
        Db::startTrans();
        $model = new AlarmLog();
        $model->user_id = $user_id;
        $model->alarm_id = $alarm_id;
        $model->log = $user->name . '传达了警报编号：' . $alarm->number;
        if (!$model->save()) {
            Db::rollback();
            return $this->jsonFail('传达失败');
        }

        $alarm->status = Alarm::STATUS_50;

        if (!$alarm->save()) {
            Db::rollback();
            return $this->jsonFail('传达失败');
        }

        Db::commit();

        //传达信息到指挥中心，游艇
        $user_list = User::where('role','in',User::ROLE_20.','.User::ROLE_30)->select();
        $server = new CommonService();
        foreach ($user_list as $user) {
            if ($user->openid) {
                $longitude = substr($alarm->longitude,0,5);
                $longitudeE = substr($alarm->longitude,-1,1);
                $latitude = substr($alarm->latitude,0,4);
                $latitudeE = substr($alarm->latitude,-1,1);
                $address = $longitude.$longitudeE.$latitude.$latitudeE;
                $server->sendMessageToUser($user->openid,'警报',$address);

            }

            if ($user->tel) {
                $server->sendSMS($user->tel,$address);
            }

        }


        return $this->jsonSuccess('OK',$user_list);
    }

    /**
     * 完成警报
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function completeAlarm(Request $request):Json
    {
        $param = $request->post();
        $user_id = $param['user_id'];
        $alarm_id = $param['alarm_id'];
        $user = User::where(['id' => $user_id])->find();
        $alarm = Alarm::where(['id' => $alarm_id])->find();
        Db::startTrans();
        $model = new AlarmLog();
        $model->user_id = $user_id;
        $model->alarm_id = $alarm_id;
        $model->log = $user->name . '完成了警报编号：' . $alarm->number;
        if (!$model->save()) {
            Db::rollback();
            return $this->jsonFail('完成失败');
        }

        $alarm->status = Alarm::STATUS_40;

        if (!$alarm->save()) {
            Db::rollback();
            return $this->jsonFail('完成失败');
        }

        Db::commit();


        return $this->jsonSuccess('OK');
    }
}


