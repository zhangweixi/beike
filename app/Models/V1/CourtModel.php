<?php

namespace App\Models\V1;

use App\Common\Geohash;
use Illuminate\Database\Eloquent\Model;
use DB;
class CourtModel extends Model
{

    protected $table        = "football_court";
    protected $primaryKey   = "court_id";
    public  $timestamps = false;//关闭自动维护

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
}
