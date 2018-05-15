<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MatchModel extends Model
{


    /**
     * 添加GPS数据
     * */
    public function add_gps_data($gpsData)
    {
        return DB::table('match_gps')->insert($gpsData);
    }


    /**
     * 添加传感仪数据
     * */
    public function add_sensor_data(array $sensorData)
    {
        return DB::table('match_sensor')->insert($sensorData);

    }

}
