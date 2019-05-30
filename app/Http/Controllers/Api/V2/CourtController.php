<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;



class CourtController extends Controller
{

    /**
     * 检查单个GPS是否有效
     * */
    public function check_single_gps(Request $request)
    {
        $gpsLat     = $request->input('gpsLat');
        $gpsLon     = $request->input('gpsLon');
        $lat        = $request->input('latitude',0);
        $lon        = $request->input('longitude',0);
        $gpsGroupId = $request->input('gpsGroupId',"");
        $userId     = $request->input('userId');
        $position   = $request->input('position',null);

        $gpsInfo    = ["lat"=>$gpsLat,'lon'=>$gpsLon];

        //仅仅检查GPS
        if(strlen($gpsGroupId) == 0){

            if($gpsInfo['lat'] != 0 && $gpsInfo['lon'] != 0){

                return apiData()->send(200,'GPS有效');
            }

            return apiData()->send(2004,'GPS无效');
        }

        //1手机PGS一直有效,即便设备无效也要存储
        if($gpsInfo['lon'] != 0 && $gpsInfo['lat'] != 0) {

            $gpsInfo['lat'] = gps_to_gps($gpsInfo['lat']);
            $gpsInfo['lon'] = gps_to_gps($gpsInfo['lon']);
            $gpsInfo        = gps_to_bdgps($gpsInfo);

        }else{
            $gpsInfo['lat'] = 0;
            $gpsInfo['lon'] = 0;
        }

        //2存储GPS信息
        $gpsPoint   = [
            "user_id"       => $userId,
            "gps_group_id"  => $gpsGroupId,
            "position"      => $position,
            "mobile_lat"    => $lat,
            "mobile_lon"    => $lon,
            "device_lat"    => $gpsInfo['lat'],
            "device_lon"    => $gpsInfo['lon'],
            'created_at'    => date_time()
        ];

        DB::table('football_court_point')->insert($gpsPoint);

        //3检查手机的GPS和设备的GPS的距离
        $msg    = $gpsInfo['lat'] ? "GPS无效":"偏差". gps_distance($lon,$lat,$gpsInfo['lon'],$gpsInfo['lat']);

        return apiData()->send(200, $msg);
    }

}
