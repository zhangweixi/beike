<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\MatchModel;



class MatchController extends Controller
{

    /**
     * 上传比赛数据
     * */
    public function upload_match_data(Request $request)
    {

        $matchId    = $request->input('matchId',0);
        $userId     = $request->input('userId',0);
        $deviceSn   = $request->input('deviceSn','');
        $deviceData = $request->input('deviceData','');

        $dataType   = $request->input('dataType');

        $matchData  = [
            'match_id'  => $matchId,
            'user_id'   => $userId,
            'device_sn' => $deviceSn,
            'created_at'=> date_time()
        ];

        if($dataType == 'gps')
        {
            $this->handle_gps($deviceData,$matchData);

        }elseif($dataType == 'sensor') {

            $this->handle_sensor($deviceData,$matchData);

        }
        return apiData()->send(200,'ok');
    }


    /**
     * 处理GPS信号
     * 每组gps信号由4部分组成
     * 1.类型（4位)
     * 2.时间 (8位)
     * 3.长度（8位)
     * 4.数据 ，位数=3所得的长度
     * */
    public function handle_gps($gps,$matchData)
    {


        //$gps        = "23232323 26010000 29000000 474e474741 2c30302c39392e39392c2c2c2c2c2c2a37320d0a,";
        //$gps       .= "23232323 26010000 29000000 474e474741 2c30302c39392e39392c2c2c2c2c2c2a37320d0a";

        $gps        = str_replace(" ","",$gps);
        $matchModel     = new MatchModel();
        $gpsArray       = explode(',',$gps);
        foreach($gpsArray as $singleData)
        {
            $otherInfo  = [
                'source_data'   => $singleData,
                'status'        => 0,
                'data_key'      => time(),
            ];


            $fullMatchInfo      = array_merge($matchData,$otherInfo);
            $matchModel->add_gps_data($fullMatchInfo);

        }
    }


    public function handle_sensor($sensor,array $matchData)
    {



    }





}
