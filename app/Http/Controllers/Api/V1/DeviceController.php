<?php

namespace App\Http\Controllers\Api\V1;

use App\Common\MobileMassege;
use App\Models\Base\BaseUserModel;
use App\Models\Base\BaseVersionModel;
use App\Models\Base\LogsModel;
use App\Models\V1\MessageModel;
use App\Models\V1\UserModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\V1\DeviceModel;
use DB;


class DeviceController extends Controller
{
    /**
     * 绑定用户
     * */
    public function bind_user(Request $request)
    {
        $userId     = $request->input('userId');
        $deviceSn   = $request->input('deviceSn');

        $deviceModel    = new DeviceModel();

        //1.检查该设备是否被绑定过
        $deviceInfo     = $deviceModel->get_device_info_by_sn($deviceSn);
        if(!$deviceInfo)
        {
            return apiData()->send(4001,"设备不存在");
        }


        //2.如果绑定过，检查是否时当前用户绑定的
        if($deviceInfo && $deviceInfo->owner != $userId && $deviceInfo->owner > 0)
        {
            return apiData()->send(4002,"设备已经被他人绑定");
        }


        if($deviceInfo->owner == $userId)
        {
            return apiData()->send(4003,"你已经绑定过该设备啦");
        }

        $deviceModel->where('device_sn',$deviceSn)->update(['owner'=>$userId]);

        DB::table('users')->where('id',$userId)->update(['device_sn'=>$deviceSn]);

        return apiData()->send(200,'绑定成功');
    }

    /**
     * 获得设备信息
     * */
    public function get_device_info(Request $request)
    {
        $pinCode        = $request->input('pinCode');
        $deviceSn       = $request->input('deviceSn');
        $deviceModel    = new DeviceModel();

        if($deviceSn)
        {
            $deviceInfo     = $deviceModel->get_device_info_by_sn($deviceSn);

        }elseif($pinCode){

            $deviceInfo     = $deviceModel->get_device_info($pinCode);

        }else{

            $deviceInfo = null;
        }

        if(!$deviceInfo)
        {
            return apiData()->send(2001,'设备不存在');
        }
        return apiData()
            ->set_data('deviceInfo',$deviceInfo)
            ->send(200,'success');
    }

    /**
     * 获取用户的设备信息
     * */
    public function get_user_device(Request $request)
    {
        $userId         = $request->input('userId');
        $deviceModel    = new DeviceModel();
        $devices        = $deviceModel->get_user_devices($userId);

        foreach($devices as $device)
        {
            $lastOta               = BaseVersionModel::last_ota($device->hard_version);
            $device->OTAFile       = url($lastOta->file);
            $device->oldSoftVersion= $device->soft_version;
            $device->lastSoftVersion= $lastOta->id;
            $device->firmwareType  = $lastOta->firmware_type;
            unset($device->hard_version);
            unset($device->soft_version);
        }

        return apiData()->set_data('devices',$devices)->send(200,'SUCCESS');
    }

    /**
     * 升级设备
     * */
    public function upgrade_device(Request $request){

        $deviceId   = $request->input('deviceId');
        $softVersion= $request->input('softVersion');
        DeviceModel::where('device_id',$deviceId)->update(['soft_version'=>$softVersion]);

        return apiData()->send();
    }

    /**
     * 解绑设备
     * */
    public function unbind_user(Request $request)
    {
        $userId     = $request->input('userId');
        $deviceSn   = $request->input('deviceSn');
        $deviceModel= new DeviceModel();
        $deviceInfo = $deviceModel->get_device_info_by_sn($deviceSn);

        if($deviceInfo->owner == $userId)
        {
            $deviceModel->where('device_sn',$deviceSn)->update(['owner'=>0]);
            DB::table('users')->where('id',$userId)->update(['device_sn'=>""]);

            return apiData()->send(200,'解绑成功');
        }


        return apiData()->send(4001,'权限不足,不能解绑');

    }


    public function send_device_sn(Request $request){

        $userId     = $request->input('userId');

        //获得设备信息
        $userInfo   = UserModel::find($userId);
        $deviceSn   = $userInfo->device_sn;

        if($deviceSn == ''){

            return apiData()->send(4001,'您没有绑定设备');
        }

        //存入消息中心
        (new MessageModel())->add_message("系统通知","您的设备编号为：{$deviceSn}，可用此编号进行设备绑定",MessageModel::TYPE_SYSTEM,$userId,0);

        //发送短信

        //检查最近获取时间

        $log    = LogsModel::where(['type'=>'send_device_sn','user_id'=>$userId])->orderBy('id','desc')->first();
        if($log && (time() - strtotime($log->created_at)) < 7 * 24 * 60 *60){

            return apiData()->send(4002,"请到消息中心查看");
        }

        (new MobileMassege())->send_device_sn_message($userInfo->mobile,$deviceSn);

        LogsModel::insert(['user_id'=>$userId,'type'=>"send_device_sn",'created_at'=>date_time()]);

        return apiData()->send(200,'已发送');
    }
}
