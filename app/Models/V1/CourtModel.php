<?php

namespace App\Models\V1;

use App\Common\Geohash;
use App\Http\Controllers\Service\GPSPoint;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Http\Controllers\Service\Court;


class CourtModel extends Model
{

    protected $table        = "football_court";
    protected $primaryKey   = "court_id";
    public  $timestamps = false;//关闭自动维护
    protected $guarded = [];

    /*
     * 添加足球场
     * */
    public function add_court($courtData)
    {
        $courtData['created_at']    = date_time();
        $courtId    = DB::table('football_court')->insertGetId($courtData);

        return $courtId;
    }

    /**
     * @param $userId integer 用户ID
     * @return integer
     * */
    public function add_empty_court($userId)
    {
        $courtData  = [
            'user_id'       => $userId,
            'gps_group_id'  => 0
        ];

        return $this->add_court($courtData);
    }


    /**
     * 获得用户的球场
     * @param $userId integer 用户ID
     * */
    public static function get_courts($userId){

        $courts = self::select("court_id","court_name","gps_group_id")->where('user_id',$userId)->paginate(10);

        return $courts;
    }

    /**
     * 获得附近的球场
     * @param $userId integer 用户ID
     * @param $type boolean 是否是公共球场 类型有三种，0：获取自己的，1：获取公共的，2：获取所有的
     * @param $lat float 纬度
     * @param $lon float 经度
     * */
    public static function get_nearby_court($userId,$type,$lat,$lon,$all=false){

        $geohash    = new Geohash();
        $hash       = $geohash->encode($lat,$lon);
        $hash       = substr($hash,0,5);
        $area       = $geohash->neighbors($hash);
        if($type == 0){

            $db     = self::where('user_id',$userId);

        }elseif($type == 1){

            $db     = self::where('user_id',"<>",$userId)->where('public',1);

        }else{

            $db     = self::where(function($db) use ($userId)
            {
                $db->where('user_id',$userId)->orWhere('public',1);
            });
        }


        $courts = $db->where('court_name',"<>",'')->where(function($db) use ($area)
        {
            foreach($area as $hash)
            {
                $db->orWhere('geohash','like',$hash."%");
            }
        })
        ->select('court_id','court_name','public')
        ->get();

        return $courts;
    }

    /**
     * 初始化一个新球场
     * @param $courtId integer
     * @param $points array 球场的几个点,必须要的点a,b,c,d,a1,d1
     * a------------------f--------------------a1
     * |                  |                    |
     * |                  |                    |
     * b                  |                    |
     * |                  |                    |
     * |                  |                    |
     * c                  |                    |
     * |                  |                    |
     * |                  |                    |
     * d------------------e-------------------d1
     * */
    public static function init_new_court($courtId,array $points){

        //判断球场是否是顺时针
        $courtInfo  = $points;

        $pa     = explode(",",$points['p_a']);
        $pd     = explode(",",$points['p_d']);
        $pd1    = explode(",",$points['p_d1']);
        $pa1    = explode(",",$points['p_a1']);

        $PA     = new GPSPoint($pa[0],$pa[1]);
        $PD     = new GPSPoint($pd[0],$pd[1]);
        $PD1    = new GPSPoint($pd1[0],$pd1[1]);
        $PA1    = new GPSPoint($pa1[0],$pa1[1]);

        $isClockWise                = Court::judge_court_is_clockwise($PA,$PD,$PD1);;
        $courtInfo['is_clockwise']  = $isClockWise ? 1 : 0;

        $courtInfo['width']         = round(gps_distance($PA->lon,$PA->lat,$PD->lon,$PD->lat),2);
        $courtInfo['length']        = round(gps_distance($PA->lon,$PA->lat,$PA1->lon,$PA1->lat),2);


        //创建geohash值
        $lat    = ($PD->lat + $PA1->lat) / 2;
        $lon    = ($PD->lon + $PA1->lon) / 2;
        $courtInfo['geohash']   = (new Geohash())->encode($lat,$lon);

        self::where('court_id',$courtId)->update($courtInfo);
    }
}
