<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\V1\MatchModel;
use Dingo\Api\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{

    public function __construct()
    {
        header('Access-Control-Allow-Origin:*');
    }

    public function max_distance(Request $request)
    {

        $matchId    = $request->input('matchId');
        $isgps      = $request->input('isgps',0);

        $matchModel = new MatchModel();
        $matchInfo  = $matchModel->get_match_detail($matchId);

        $table      = "user_".$matchInfo->user_id."_gps";
        $colum      = ['lat','lon'];
        $db         = DB::connection('matchdata');
        $minlat     = $db->table($table)->select($colum)->where('match_id',$matchId)->where('lat',"<>",0)->orderBy('lat','asc')->first();
        $maxlat     = $db->table($table)->select($colum)->where('match_id',$matchId)->where('lat','<>',0)->orderBy('lat','desc')->first();

        $minlon     = $db->table($table)->select($colum)->where('match_id',$matchId)->where('lon','<>',0)->orderBy('lon','asc')->first();
        $maxlon     = $db->table($table)->select($colum)->where('match_id',$matchId)->where('lon','<>',0)->orderBy('lon','desc')->first();

        if($minlat == null)
        {
            return apiData()->send(2001,'没有数据');
        }


        $points       = [
            'minLat'    => $minlat,
            'maxLat'    => $maxlat,
            'minLon'    => $minlon,
            'maxLon'    => $maxlon
        ];

        bcscale(6);
        //return $points;

        if($isgps == 0)//转换成GPS
        {
            foreach($points as $point)
            {
                $point->lat = bcmul($point->lat,100);
                $point->lon = bcmul($point->lon,100);

                $point->lat = gps_to_gps($point->lat);
                $point->lon = gps_to_gps($point->lon);
            }
        }


        //return $points;

        foreach($points as $key => $point)
        {
            $str    = $point->lon.",".$point->lat;
            $url    = "http://api.map.baidu.com/geoconv/v1/?coords={$str}&from=1&to=5&ak=zZSGyxZgUytdiKG135BcnaP6";

            $result = file_get_contents($url);
            $result = \GuzzleHttp\json_decode($result);
            $result = $result->result[0];

            $points[$key]->lat     = $result->y;
            $points[$key]->lon     = $result->x;
        }

        return apiData()->add('points',$points)->send();
    }

}