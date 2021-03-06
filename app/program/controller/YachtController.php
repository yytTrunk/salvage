<?php
declare (strict_types = 1);

namespace app\program\controller;

use app\program\BaseController;
use app\program\model\User;
use app\program\model\Yacht;
use think\App;
use think\Request;
use think\response\Json;

class YachtController extends BaseController
{
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

    }

    public function add(Request $request):Json
    {
        $param = $request->post();
        if (!isset($param['name'])) {
            return  $this->jsonFail('请输入游艇名称');
        }

        if (!isset($param['link_name'])) {
            return $this->jsonFail('请输入联系人');
        }

        if (!isset($param['link_tel'])) {
            return $this->jsonFail('请输入联系号码');
        }

        if (!isset($param['img'])) {
            return $this->jsonFail('请上传游艇图片');
        }

        if (Yacht::where(['name' => $param['name']])->find()) {
            return $this->jsonFail('该游艇已创建');
        }

        $yacht = new Yacht();
        $yacht->name = $param['name'];
        $yacht->link_name = $param['link_name'];
        $yacht->link_tel = $param['link_tel'];
        $yacht->img = $param['img'];
        $yacht->user_id = $param['user_id'];
        if (!$yacht->save()) {
            return $this->jsonFail('添加失败');
        }

        return $this->jsonSuccess('添加成功',$yacht);
    }

    public function delete(Request $request) :Json
    {
        $param = $request->post();
        if (!isset($param['id'])) {
            return  $this->jsonFail('请选择删除游艇');
        }

        if (!Yacht::where(['user_id' => $param['user_id'],'id' => $param['id']])->delete()){
            return $this->jsonFail('删除失败');
        }

        return $this->jsonSuccess('删除成功');
    }

    public function lists(Request $request):Json
    {
        $param = $request->post();
        $data = Yacht::where(['user_id' => $param['user_id']])->select();
        foreach ($data as $item) {
            $item->status = $item->getStatusName();
        }

        return $this->jsonSuccess('OK',$data);
    }

    public function lockYacht(Request $request):Json
    {
        $param = $request->post();
        if (!Yacht::where(['id' => $param['id']])->update(['status' => 20])){
            return $this->jsonFail('冻结失败');
        }

        return $this->jsonSuccess('冻结成功');
    }

    public function modifyYacht(Request $request):Json
    {

        if ($request->isPost()) {
            $param = $request->post();
            $user_id = $param['user_id'];
            $yacht = Yacht::where(['user_id' => $user_id])->find();
            if ($yacht) {
                //修改
                $yacht->name = $param['name'];
                $yacht->link_name = $param['link_name'];
                $yacht->link_tel = $param['link_tel'];
                $yacht->img = $param['img'];
               if (!$yacht->save()) {
                   return $this->jsonFail('修改失败');
               }

               return $this->jsonSuccess('修改成功');

            }
            //添加
            $yacht = new Yacht();
            $yacht->name = $param['name'];
            $yacht->link_name = $param['link_name'];
            $yacht->link_tel = $param['link_tel'];
            $yacht->img = $param['img'];
            $yacht->user_id = $user_id;
            if (!$yacht->save()) {
                return $this->jsonFail('完善失败');
            }

            return $this->jsonSuccess('完善成功',$yacht);
        }

        $param = $request->get();
        $user_id = $param['user_id'];
        $yacht = Yacht::where(['user_id' => $user_id])->find();

        return $this->jsonSuccess('OK',$yacht);
    }

    public function unLockYacht(Request $request):Json
    {
        $param = $request->post();
        if (!Yacht::where(['id' => $param['id']])->update(['status' => 10])){
            return $this->jsonFail('解冻失败');
        }

        return $this->jsonSuccess('解冻成功');
    }

}
