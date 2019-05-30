<?php

namespace App\Models\V1;

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
     * @param $owner string 所属
     * */
    public static function get_courts($userId=0,$owner="self"){

        $db = self::select("court_id","court_name","gps_group_id")
            ->where('court_name',"<>","");

        if($owner == 'self'){

            $db->where('user_id',$userId);

        }elseif($owner == 'other'){

            $db->where('user_id',"<>",$userId);
        }

        $courts = $db->paginate(10);

        return $courts;
    }
}
