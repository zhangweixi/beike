<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseMatchModel extends Model
{
    protected $table = "match";
    protected $primaryKey = "match_id";

    public static function match_process($matchId,$action){

        DB::table('match_process')->insert(['match_id'=>$matchId,'action'=>$action,'created_at'=>date_time()]);
    }

    /**
     * 删除比赛
     * @param $matchId integer 比赛ID
     * @return boolean
     * */
    public static function delete_match($matchId){

        self::where('match_id',$matchId)->update(['deleted_at'=>date_time()]);

        return true;
    }

    /**
     * 删除比赛结果
     * @param $matchId integer 比赛ID
     * @return true
     * */
    public static function delete_match_result($matchId){

        BaseMatchResultModel::where('match_id',$matchId)->update(['deleted_at'=>date_time()]);

        return true;
    }
}
