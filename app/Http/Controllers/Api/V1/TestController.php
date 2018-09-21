<?php
namespace App\Http\Controllers\Api\V1;
use App\Common\Jpush;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Service\MatchCaculate;
use App\Http\Controllers\Service\MatchGrade;
use App\Models\Base\BaseMatchDataProcessModel;
use App\Models\V1\MatchModel;
use Dingo\Api\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Common\Geohash;
use Maatwebsite\Excel\Facades\Excel;


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

        //略去开头一分钟的数据


        $table      = "user_".$matchInfo->user_id."_gps";
        $colum      = ['lat','lon'];
        $db         = DB::connection('matchdata');
        //略去开头一分钟的数据
        $firstInfo  = $db->table($table)->where('match_id',$matchId)->first();
        //$beginTime  = $firstInfo->timestamp + 5000;
        $beginTime  = 0;

        $minlat     = $db->table($table)->select($colum)->where('match_id',$matchId)->where('lat',"<>",0)->where('timestamp','>',$beginTime)->orderBy('lat','asc')->first();
        $maxlat     = $db->table($table)->select($colum)->where('match_id',$matchId)->where('lat','<>',0)->where('timestamp','>',$beginTime)->orderBy('lat','desc')->first();
        $minlon     = $db->table($table)->select($colum)->where('match_id',$matchId)->where('lon','<>',0)->where('timestamp','>',$beginTime)->orderBy('lon','asc')->first();
        $maxlon     = $db->table($table)->select($colum)->where('match_id',$matchId)->where('lon','<>',0)->where('timestamp','>',$beginTime)->orderBy('lon','desc')->first();

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

    public function gpstest()
    {

        $geohash    = new Geohash();

        //$hash = $geohash->encode(39.98123848, 116.30683690);

        $hash = $geohash->encode(31.1852000000,121.3658720000);

        return $hash;

        //取前缀，前缀约长范围越小
        $prefix = substr($hash, 0, 6);


        //取出相邻八个区域
        $neighbors = $geohash->neighbors($prefix);
        array_push($neighbors, $prefix);

        return $neighbors;
        print_r($neighbors);
    }

    /**
     * 获得形状的角度
     * */
    public function get_shape_angle()
    {

        $pa = [0,0];
        $pb = [5,0];
        $pc = [5,5];

        //$da = bcpow(($pb[0]-$pc[0]),2) + bcpow($pb[1]-$pc[1],2);
        //$da = bcsqrt($da);
        $dc = self::get_distance($pa,$pb);
        $da = self::get_distance($pb,$pc);
        $db = self::get_distance($pa,$pc);
        return [$da,$db,$dc];
        return self::angle("B",$da,$db,$dc);

    }


    public static function get_distance($a,$b)
    {
        $d= bcpow(($a[0]-$b[0]),2) + bcpow($a[1]-$b[1],2);
        return bcsqrt($d);
    }


    /**
     * @param $angle string A,B,C
     * @param $a integer
     * @param $b integer
     * @param $c integer
     * @return integer
     * */
    public static function angle($angle,$a,$b,$c){

        //cosB=(c²+a²-b²)/2ac

        switch ($angle)
        {
            case "A": $A = $a; $B = $b; $C = $c; break;
            case "B": $A = $b; $B = $a; $C = $c; break;
            case "C": $A = $c; $B = $b; $C = $a; break;
        }

        //$cosA = ($B^2 + $C^2 - $A^2)/2$B$C;
        $cosA = ($B*$B + $C*$C - $A*$A) / 2*$B*$C;

        #$cosA = bcdiv(bcsub(bcadd(bcpow($B,2), bcpow($C,2)),bcmul($A,2)),bcmul(2,bcmul($B,$C)));

        return $cosA;
    }


    public function get_grade()
    {
        $matchGrade = new MatchGrade();
        return $matchGrade->get_global_single_option_grade(1,'touchball_num_total',true);
    }


    public function test(Request $request){


        $courtId    = $request->input('courtId');
        MatchCaculate::call_matlab_court_init($courtId);
        return ;
        sleep(10);
        mylogger($request->all());
        return $request->all();
    }


    public function jpush()
    {

        $push   = new Jpush();
        return $push->pushContent("标题","张".date_time(),3001,0,'',['name'=>'zhangweixi','time'=>date_time()]);

    }

    public function hex_to_time(Request $request)
    {
        $hex    = $request->input('hex');
        return date_time(HexToTime($hex)/1000);
    }

}

function floattostr( $val )
{
    preg_match( "#^([\+\-]|)([0-9]*)(\.([0-9]*?)|)(0*)$#", trim($val), $o );
    return $o[1].sprintf('%d',$o[2]).($o[3]!='.'?$o[3]:'');
}