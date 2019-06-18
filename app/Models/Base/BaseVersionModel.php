<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseVersionModel extends Model
{
    protected $table = "version";
    protected $primaryKey = "id";

    /**
     * 最新OTA版本
     * @param 硬件版本
     * */
    public static function last_ota($hardVersion){

        return self::where('type','device')
            ->where('hard_version',$hardVersion)
            ->orderBy('id','desc')
            ->first();
    }
}
