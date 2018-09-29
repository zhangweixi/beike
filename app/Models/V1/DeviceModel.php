<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;


class DeviceModel extends Model
{

    protected $table = "device";
    protected $primaryKey = "device_id";

    protected $guarded = [];


    /**
     * @param $pinCode string 设备pin码
     * @return Object
     * */
    public function get_device_info($pinCode)
    {
        return $this->where('pin',$pinCode)
            ->select('device_id','device_sn','produced_at','mac_r','mac_l','bluetooth_r',"bluetooth_l",'pin','owner')
            ->first();
    }


    public function get_device_info_by_sn($deviceSn)
    {
        return $this->where('device_sn',$deviceSn)
            ->select('device_id','device_sn','produced_at','mac_r','mac_l','bluetooth_r',"bluetooth_l",'pin','owner')
            ->first();
    }


    /**
     * 绑定用户
     * @param $deviceSn string 设备ID
     * @param $userId integer 用户ID
     * */
    public function bind_user($deviceSn,$userId)
    {
        $this->where('pin',$deviceSn)->update(['user_id'=>$userId]);

    }


    /**
     * 获得用户已经绑定的设备
     * @param $userId integer 用户ID
     * @return  array
     * */
    public function get_user_devices($userId)
    {
        $devices    = $this->where('owner',$userId)
            ->select('device_id','device_sn','mac_r','mac_l','bluetooth_r','bluetooth_l','pin')
            ->where('deleted_at')->get();

        return $devices??[];
    }

}
