<?php

namespace App\Http\Controllers\Api\V1;

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


        return apiData()->set_data('devices',$devices)->send(200,'SUCCESS');
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
}
