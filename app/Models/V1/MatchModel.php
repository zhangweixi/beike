<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MatchModel extends Model
{

    private $time;
    public function __construct()
    {
        $this->time = date_time();
    }

    /**
     * 添加设备源数据
     * */
    public function add_match_source_data($data)
    {
        $data['created_at'] = date_time();

        $dataId     = DB::table('match_source_data')->insertGetId($data);

        return $dataId;
    }


    /**
     * 添加GPS数据
     * */
    public function add_gps_data($gpsData)
    {
        $gpsData['created_at']  = $this->time;
        return DB::table('match_gps')->insert($gpsData);
    }


    /**
     * 添加传感仪数据
     * */
    public function add_sensor_data(array $sensorData)
    {
        $sensorData['created_at']  = $this->time;
        return DB::table('match_sensor')->insert($sensorData);

    }

}
