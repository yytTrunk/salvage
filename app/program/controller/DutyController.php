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
        $status_codes = $param['status_codes'];

        $user = User::where(['id' => $user_id])->find();
        $data = [];
        
        if ($user->role == User::ROLE_10) {
            // 值班室
            // $data = Alarm::where('status', '<>', Alarm::STATUS_40)->where('status', '<>', Alarm::STATUS_30)->order('create_time','desc')->select();
            $data = Alarm::where('status', 'in',  $status_codes)->order('create_time','desc')->select();
        } else if ($user->role == User::ROLE_20) {
            // 游艇
            // $data = Alarm::where('status', 'in',  Alarm::STATUS_50 . ',' . Alarm::STATUS_20)->order('create_time','desc')->select();
            $data = Alarm::where('status', 'in',  $status_codes)->order('create_time','desc')->select();
        } else if ($user->role == User::ROLE_30) {
            // 指挥中心
            // $data = Alarm::where('status', 'in',  Alarm::STATUS_50 . ',' . Alarm::STATUS_20 )->order('create_time','desc')->select();
            $data = Alarm::where('status', 'in',  $status_codes)->order('create_time','desc')->select();
        }

        $facility_arrs = Facility::where(['status' => Facility::STATUS_10])->select()->toArray();
        $facility_maps = array();
        array_map(function($item){
            global $facility_maps;
            $facility_maps[ $item['facility_id'] ]= $item['title'];
        }, $facility_arrs);

        if ($data) {
            foreach ($data as $key => $item) {
                global $facility_maps;
                $item->status_code = $item->status;
                $item->status = $item->getStatusName();
                $item->facility_name = empty($facility_maps[$item->BID]) ? "" : $facility_maps[$item->BID];
                $count = AlarmLog::where(['alarm_id' => $item->id])->count();
                // 如果是游艇，需要剔除掉，在值班室就确认为误报的，此时在游艇用户里不能返回展示
                if ($user->role == User::ROLE_20 && $status_codes == Alarm::STATUS_30 && $count < 2) {
                    unset($data[$key]);
                }
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
     * 查询一条告警处理的详细信息
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function alarmDetail(Request $request): Json
    {
        $param = $request->post();
        $alarm_id = $param['alarm_id'];

        $alarm = Alarm::where(['id' => $alarm_id])->find();
        if ($alarm) {
            $facility = Facility::where(['facility_id' => $alarm->BID])->find();


            $alarm->status_code = $alarm->status;
            $alarm->status = $alarm->getStatusName();
            $alarm->facility_name = $facility->title;
            $alarm->log = AlarmLog::where(['alarm_id' => $alarm->id])->select();
        } else {
            return $this->jsonFail('查询记录不存在');
        }

        return $this->jsonSuccess('OK', $alarm);
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

        //传达通知信息到指挥中心，游艇账户
        $user_list = User::where('role','in',User::ROLE_20.','.User::ROLE_30)->select();
        $facility = Facility::where(['facility_id' => $alarm->BID])->find();
        $server = new CommonService();
        foreach ($user_list as $user) {
            if ($user->openid) {
                // $longitude = substr($alarm->longitude,0,5);
                // $longitudeE = substr($alarm->longitude,-1,1);
                // $latitude = substr($alarm->latitude,0,4);
                // $latitudeE = substr($alarm->latitude,-1,1);
                // $address = $longitude.$longitudeE.$latitude.$latitudeE;
                $server->sendMessageToUser($user->openid,'警报', $facility->title);
            }

            if ($user->tel) {
                $server->sendSMS($user->tel, $facility->title);
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


