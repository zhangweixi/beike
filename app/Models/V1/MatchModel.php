<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MatchModel extends Model
{

    private $time;
    protected $table = "match";
    protected $primaryKey = "match_id";
    public $timestamps = false;

    public function __construct()
    {
        $this->time = date_time();
    }


    /**
     * 添加个人比赛
     * @param $matchInfo array 比赛信息
     * @return int
     * */
    public function add_match($matchInfo)
    {
        $matchInfo['created_at']    = $this->time;
        $matchInfo['time_begin']    = $this->time;

        $matchId    = DB::table('match')->insertGetId($matchInfo);

        return $matchId;
    }

    /**
     * 结束比赛
     * @param $matchId integer 比赛ID
     * */
    public function finish_match($matchId)
    {
        $time = date('Y-m-d H:i:s',time() + 5);
        $this->where('match_id',$matchId)->update(['time_end'=>$time]);

    }

    /**
     * 修改比赛信息
     * @param $matchId integer 比赛ID
     * @param $matchInfo array 新的比赛信息
     * @return boolean
     * */
    public function update_match($matchId,$matchInfo)
    {

        DB::table('match')->where('match_id',$matchId)->update($matchInfo);

        return true;

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

    /**
     * 获得当前比赛
     * @param $userId integer 用户ID
     * @return mixed
     * */
    public function get_current_match($userId)
    {

        $matchInfo = DB::table('match')
            ->where('user_id',$userId)
            ->orderBy('match_id','desc')
            ->select('match_id','user_id','court_id','time_begin','time_end','end_upload')
            ->first();


        if($matchInfo)
        {
            $matchInfo->time_begin = strtotime($matchInfo->time_begin)*1000;

            //获取最近的一个状态
            $status     = DB::table('match_status')->where('match_id',$matchInfo->match_id)->orderBy('status_id','desc')->first();

            if($status->status == 'pause'){

                $matchInfo->isPause = 1;

            }else{

                $matchInfo->isPause = 0;
            }

            return $matchInfo;

        } else{

            return null;
        }
    }


    /**
     * 获得用户比赛列表
     * */
    public function get_match_list($userId)
    {
        $colums = ["a.match_id",'a.time_begin','a.weather','a.temperature','a.mood','a.time_length','b.grade','position','court_id'];
        $matchs = DB::table('match as a')
            ->leftJoin('match_result as b','b.match_id','=','a.match_id')
            ->select($colums)
            ->where('a.user_id',$userId)
            ->orderBy('a.match_id','desc')
            ->paginate(10);
        return $matchs;
    }

    /*
     * 比赛详情
     * */
    public function get_match_detail($matchId)
    {
        $columns    = [
            "match_id",
            "user_id",
            'court_id',
            "address",
            "weather",
            "temperature",
            "mood",
            "message",
            "time_length",
            "time_begin",
            "time_end",
            "created_mood_at",
            'position',
        ];

        $matchDetail = DB::table('match')
            ->select($columns)
            ->where('match_id',$matchId)
            ->first();
        $court = DB::table('football_court')->where('court_id',$matchDetail->court_id)->select('court_name')->first();
        $matchDetail->address = $court->court_name;
        return $matchDetail;
    }

    public static function user_last_match($userId){

        $lastMatch  = self::where('user_id',$userId)->orderBy('match_id','desc')->first();

        return $lastMatch;

    }
    /**
     * 获得比赛基础结果
     * @param $matchid integer 比赛ID
     * */
    public function get_match_result($matchid)
    {

        $matchResult = DB::table('match_result')->where('match_id',$matchid)->first();
        if($matchResult)
        {
            unset($matchResult->deleted_at);
            unset($matchResult->updated_at);
            unset($matchResult->created_at);
            unset($matchResult->match_id);
        }
        return $matchResult;
    }

    /**
     * @param $matchId integer 比赛ID
     * @param $status string 状态
     * */
    public function log_match_status($matchId,$status)
    {
        $data   = [
            'match_id'  => $matchId,
            'status'    => $status,
            'created_at'=> date_time(),
        ];
        DB::table('match_status')->insert($data);
    }


    /**
     * 修改原始数据
     * */
    public static function update_match_data($dataId,array $data)
    {
        $res = DB::table('match_source_data')
            ->where('match_source_id',$dataId)
            ->update($data);

        return $res;
    }

    public static function get_match_court($matchId){

        $courtInfo  = DB::table('match as a')
            ->leftJoin('football_court as b','b.court_id','=','a.court_id')
            ->where('a.match_id',$matchId)
            ->where('b.court_id',">",0)
            ->select('b.*')
            ->first();

        return $courtInfo;
    }
}
