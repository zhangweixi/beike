<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/31
 * Time: 17:02
 */

namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\Controller;
use App\Models\V1\CourtModel;
use App\Models\V1\MatchModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class MatchController extends Controller
{

    public function matches(Request $request)
    {
        $matches = MatchModel::orderBy('match_id','desc')->paginate(20);

        return apiData()->add('matches',$matches)->send();
    }



    /**
     * 球场信息
     * */
    public function match_court(Request $request)
    {

        $matchId    = $request->input('matchId');
        $matchInfo  = MatchModel::find($matchId);

        if($matchInfo->court_id == 0)
        {
            return apiData()->send(2001,"本次比赛没有测量球场");
        }


        $courtInfo  = CourtModel::find($matchInfo->court_id);

        $courtGps   = \GuzzleHttp\json_decode($courtInfo->boxs);

        unset($courtGps->center);

        //检查是否有百度地图
        if(!isset($courtGps->baiduGps))
        {

            foreach($courtGps as $gpsType)
            {
                foreach($gpsType as $gps)
                {
                    $gps->lat   = gps_to_gps($gps->lat);
                    $gps->lon   = gps_to_gps($gps->lon);    
                }
            }

            //将GPS转成百度GPS
            $baiduGps   = [];
            $baiduGps['A_D']    = gps_to_bdgps($courtGps->A_D);
            $baiduGps['AF_DE']  = gps_to_bdgps($courtGps->AF_DE);
            $baiduGps['F_E']    = gps_to_bdgps($courtGps->F_E);


            $newGps             = \GuzzleHttp\json_decode($courtInfo->boxs,true);
            $newGps['baiduGps'] = $baiduGps;

            $courtGps->baiduGps = $baiduGps;
            $courtInfo->boxs    = $courtGps;
            
            //更改数据
            //CourtModel::where('court_id',$matchInfo->court_id)->update(['boxs'=>\GuzzleHttp\json_encode($newGps)]);
        }

        return apiData()->add('court',$courtInfo)->send();

    }


    /**
     * 比赛的GPS
     * */
    public function match_gps(Request $request)
    {
        $matchId    = $request->input('matchId');

        $baiduGpsFile    = "match/".$matchId."/bd-gps.json";

        if(!Storage::disk('web')->has($baiduGpsFile)) {

            $allGps     = [];

            $gpsFile    =public_path('uploads/match/'.$matchId."/gps-L.txt");

            if(!file_exists($gpsFile))
            {
                $gpsFile    =public_path('uploads/match/'.$matchId."/gps-R.txt");
            }

            if(!file_exists($gpsFile))
            {
                return apiData()->send(2001,"没有PGS数据");
            }


            $gpsList    = file($gpsFile);
            foreach($gpsList as $gps)
            {
                $gpsInfo    = explode(" ",trim($gps,"\n"));

                if($gpsInfo[0] == 0 || $gpsInfo[1] == 0)
                {
                    continue;
                }

                $lat        = gps_to_gps($gpsInfo[0]);
                $lon        = gps_to_gps($gpsInfo[1]);
                array_push($allGps,['lat'=>$lat,'lon'=>$lon]);
            }


            $allGps = gps_to_bdgps($allGps);

            Storage::disk('web')->put($baiduGpsFile,\GuzzleHttp\json_encode($allGps));
        }

        $points = Storage::disk('web')->get($baiduGpsFile);
        $points = \GuzzleHttp\json_decode($points);

        return apiData()->set_data('points',$points)->send();
    }

}