<?php

namespace App\Models\Base;

use App\Common\WechatTemplate;
use App\Http\Controllers\Service\Wechat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

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

    /**
     * 监控比赛
     * @param $matchId integer 比赛ID
     * */
    public static function join_minitor_match($matchId)
    {
        $matchInfo  = ['id'=>$matchId,'time'=>time()];
        Redis::sadd("matches",\GuzzleHttp\json_encode($matchInfo));


    }

    /**
     * 监控比赛结果
     * */
    public static function minitor_match()
    {
        $matches = Redis::smembers('matches');
        $now     = time();
        $maxTime = 5*60;

        foreach($matches as $matchstr){

            $match = \GuzzleHttp\json_decode($matchstr);

            if($now - $match->time > $maxTime){//如果时间大于5分钟，则提出报警

                Redis::srem("matches",$matchstr);   //移除监控

                $template = (new WechatTemplate())->warningTemplate();
                $template->first = "数据计算异常";
                $template->remark="比赛ID:".$match->id;
                $template->warnType= "数据计算超过5分钟";
                $template->warnTime= date_time();
                $template->openId = config('app.adminOpenId');
                $wechat = new Wechat();
                $wechat->template_message($template)->send();
            }
        }
    }


    /**
     * 移除监控
     * @param $matchId integer 比赛ID
     * */
    public static function remove_minitor_match($matchId){

        $matches = Redis::smembers('matches');

        foreach($matches as $matchstr){

            $match  = \GuzzleHttp\json_decode($matchstr);

            if($match->id == $matchId){

                Redis::srem('matches',$matchstr);
                break;
            }
        }
    }
}
