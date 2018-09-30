<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/31
 * Time: 16:32
 */

namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\Controller;
use App\Models\V1\DeviceModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class DeviceController extends Controller
{

    /**
     * 设备列表
     * */
    public function devices(Request $request)
    {
        $keywords   = $request->input('keywords');

        $devices    = DB::table('device as a')
            ->leftJoin('users as b','b.id','=','a.owner')
            ->select('a.*','b.nick_name','b.mobile')
            ->orderBy('device_id','desc')
            ->whereNull('deleted_at');

        if($keywords)
        {
            $devices->where(function($db) use ($keywords)
            {
                $keywords   = "%{$keywords}%";

                $db->where('b.nick_name',"like",$keywords)->orWhere('a.device_sn',"like",$keywords)->orWhere('b.mobile','like',$keywords);
            });
        }

        $devices    = $devices->paginate(20);

        return apiData()->add('devices',$devices)->send();
    }


    /**
     * 设备信息
     * */
    public function get_device_info(Request $request){

        $deviceId   = $request->input('deviceId',0);

        $deviceInfo = DeviceModel::find($deviceId);

        return apiData()->add('deviceInfo',$deviceInfo)->send();
    }


    /*编辑设备信息*/
    public function edit_device(Request $request)
    {
        $deviceId   = $request->input('device_id',0);

        $deviceInfo = $request->all();

        if($deviceId > 0) {
            foreach ($deviceInfo as $key =>$v){
                if($v == null){
                    unset($deviceInfo[$key]);
                }
            }
            DeviceModel::where('device_id',$deviceId)->update($deviceInfo);

        }else{


            $deviceinfo = DeviceModel::where('device_sn',$deviceInfo['device_sn'])->first();

            if($deviceinfo){

                return apiData()->send(2001,"设备编号已存在");
            }

            $deviceInfo['created_at']   = date_time();
            $deviceInfo['updated_at']   = date_time();

            DeviceModel::create($deviceInfo);

        }

        return apiData()->send();
    }


    /*
     * 删除设备
     * */
    public function delete_device(Request $request)
    {

        $deviceId   = $request->input('deviceId');

        DeviceModel::where('device_id',$deviceId)->update(['deleted_at'=>date_time()]);

        return apiData()->send();
    }
}