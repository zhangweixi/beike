<?php
namespace App\Http\Controllers\Api\V1;
use App\Common\Jpush;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Service\Court;
use App\Http\Controllers\Service\GPSPoint;
use App\Http\Controllers\Service\MatchCaculate;
use App\Http\Controllers\Service\MatchGrade;
use App\Jobs\AnalysisMatchData;
use App\Jobs\CommonJob;
use App\Models\Base\BaseMatchDataProcessModel;
use App\Models\V1\CourtModel;
use App\Models\V1\MatchModel;
use App\Models\V1\UserModel;
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


    public function test(Request $request)
    {
        if(false)
        {
            $courtdata =  (new Court())->cut_court_to_small_box(246,40,20);
            $courtData      = ['boxs'=>\GuzzleHttp\json_encode($courtdata)];
            CourtModel::where('court_id',246)->update($courtData);
            return "ok";
        }


        if(true){

            //return (new AnalysisMatchData('xx'))->save_matlab_result(1102); //保存射门结果

            //return (new AnalysisMatchData('xx'))->save_direction_result(1104); //转向转身
            //return (new AnalysisMatchData('xx'))->save_run_result(1102); //保存射门结果
            return (new AnalysisMatchData('xx'))->save_pass_and_touch(1104); //保存射门结果

            return (new AnalysisMatchData('xx'))->save_shoot_result(1102); //保存射门结果
            return (new AnalysisMatchData('xx'))->save_dribble_and_backrun(870);

            ////计算比赛分数
            return (new MatchGrade())->get_global_new_grade(10);
            return (new MatchGrade())->get_match_new_grade(677);

            //保存结果比赛结果
            return (new AnalysisMatchData('xx'))->save_pass_and_touch(677);
        }






        return  $res === true ? "ok" : $res;



        $userModel  = new UserModel();
        $latitude   = "31.277551";
        $longitude  = "121.487143";

        //$latitude   = "31.235597";
        //$longitude  = "121.55032";

        $users  = $userModel->get_user_ids_by_geohash($latitude,$longitude,4);

        return $users;
        $courtId    = $request->input('courtId');
        MatchCaculate::call_matlab_court_init($courtId);

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
        //return HexToTime($hex);
        return date_time(HexToTime($hex)/1000);
    }


    public function update_grade()
    {
        $matchGrade = new MatchGrade();
        $globalData = $matchGrade->get_global_new_grade(23);
        DB::table('user_global_ability')->where('user_id',23)->update($globalData);


        $grade  = $matchGrade->get_match_new_grade(870);
        DB::table('match_result')->where('match_id',870)->update($grade);

        return $grade;

    }
}

function floattostr( $val )
{
    preg_match( "#^([\+\-]|)([0-9]*)(\.([0-9]*?)|)(0*)$#", trim($val), $o );
    return $o[1].sprintf('%d',$o[2]).($o[3]!='.'?$o[3]:'');
}