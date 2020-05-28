<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseVersionModel extends Model
{
    protected $table = "version";
    protected $primaryKey = "id";

    /**
     * 最新OTA版本
     * @param $hardVersion int 硬件版本
     * @param $otaType string OTA类型 wifi:bluetooth
     * */
    public static function last_ota($hardVersion,$otaType){

        return self::where('type','device')
            ->where('hard_version',$hardVersion)
            ->where('ota_type',$otaType)
            ->where('publish', 1)
            ->orderBy('id','desc')
            ->first();

    }
}
