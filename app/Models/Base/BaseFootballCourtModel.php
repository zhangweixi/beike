<?php

namespace App\Models\Base;

use App\Common\WechatTemplate;
use App\Http\Controllers\Service\MatchCaculate;
use App\Http\Controllers\Service\Wechat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;


class BaseFootballCourtModel extends Model
{
    protected $table = "football_court";
    protected $primaryKey = "court_id";


    public static function delete_court($courtId){

        //$this->where('court_id',$courtId)->update(['deleted_at',date_time()]);
        self::where('court_id',$courtId)->update(['deleted_at'=>date_time()]);

    }

    /**
     * 获得所有监控的球场
     * @return array
     * */
    private static function get_minitor_courts(){

        $courts     = Redis::smembers("courts");

        if($courts){
            foreach($courts as $key => $court){

                $courts[$key]   = \GuzzleHttp\json_decode($court);
            }
        }else{

            $courts = [];
        }

        return $courts;
    }

    /**
     * 加入监控
     * @param $courtId integer 球场ID
     * @param $tryTimes integer 尝试的次数
     * */
    public static function join_minitor_court($courtId,$tryTimes = 1){

        $data   = [
            "id"    => $courtId,
            "time"  => time(),
            "try"   => $tryTimes
        ];

        $data   = \GuzzleHttp\json_encode($data);
        Redis::sadd("courts",$data);
    }

    /**
     * @var $trytimes integer 尝试次数
     * */
    private static $trytimes    = 4;

    /**
     * 监控球场状态
     *
     * */
    public static function minitor_court()
    {
        $courts     = self::get_minitor_courts();
        $now        = time();

        foreach($courts as $court){

            $courtId    = $court->id;

            if($court->try < self::$trytimes && ($now - $court->time) > 60){ //小于尝试测试并且时间已经过了1分钟,再次尝试


                MatchCaculate::call_matlab_court_init($courtId);    //再试

                self::remove_minitor_court($courtId);               //移除

                self::join_minitor_court($courtId,$court->try + 1); //新加

            }elseif($court->try == self::$trytimes){ //已经执行过三次或四次,移除并报警

                self::remove_minitor_court($courtId);

                $template = (new WechatTemplate())->warningTemplate();
                $template->first    = "系统警告";
                $template->remark   = "球场ID:".$courtId;
                $template->warnType = "球场无法计算";
                $template->warnTime = date_time();
                $template->openId   = config('app.adminOpenId');

                $wechat = new Wechat();
                $wechat->template_message($template)->send();
            }
        }
    }



    /**
     * 移除监控
     * @param $courtId integer 球场ID
     * */
    public static function remove_minitor_court($courtId){

        $courts     = self::get_minitor_courts();

        foreach($courts as $court) {

            if($court->id == $courtId){

                Redis::srem("courts",\GuzzleHttp\json_encode($court));
            }
        }
    }

}
