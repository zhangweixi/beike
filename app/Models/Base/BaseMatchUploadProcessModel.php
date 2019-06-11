<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Redis;

class BaseMatchUploadProcessModel extends Model
{
    protected $table = "match_upload_process";

    protected $primaryKey = "user_id";

    public static function init_upload_process($userId){

        $processInfo    = DB::table('match_upload_process')->where('user_id',$userId)->first();

        $time           = date_time();

        if($processInfo){


            $finishedNum   = $processInfo->finished_num;

            if($processInfo->left_num == $processInfo->left_finished_num){
                $leftNum        = 0;
                $totalLeftNum   = 0;
                $finishedNum   -= $processInfo->left_num;
            }

            if($processInfo->right_num == $processInfo->right_finished_num){
                $rightNum       = 0;
                $totalRightNum  = 0;
                $finishedNum   -= $processInfo->right_num;
            }

            DB::table('match_upload_process')
                ->where('user_id',$userId)
                ->update([
                    'updated_at'        => $time,
                    "finished_num"      => $finishedNum,
                    "left_finished_num" => $leftNum,
                    "right_finished_num"=> $rightNum,
                    "left_num"          => $totalLeftNum,
                    "right_num"         => $totalRightNum,
                ]);
        }else{

            DB::table('match_upload_process')->insert([
                "user_id"       => $userId,
                "finished_num"  => 0,
                "created_at"    => $time,
                "updated_at"    => $time
            ]);
        }
    }
    /**
     * 缓存单场比赛的数据上传进度
     * @param $matchId integer
     * @param $type string
     * @param $foot string
     * @param $num integer
     * */
    public static function cache_single_match_progrsss($matchId,$type,$foot,$num){

        Redis::set("upload:{$matchId}:{$type}:{$foot}",$num);
    }

    /**
     * 检查单场比赛的数据是否上传完毕
     * @param $matchId integer
     * @return boolean
     * */
    public static function check_single_match_finish($matchId){

        $gpsL   = Redis::get("upload:{$matchId}:gps:left");
        $senL   = Redis::get("upload:{$matchId}:sensor:left");
        $senR   = Redis::get("upload:{$matchId}:sensor:right");
        $comL   = Redis::get("upload:{$matchId}:compass:left");
        $comR   = Redis::get("upload:{$matchId}:compass:right");

        if(isZero($gpsL) && isZero($senL) && isZero($senR) && isZero($comL)  && isZero($comR)){

            Redis::del("upload:{$matchId}:gps:left");
            Redis::del("upload:{$matchId}:sensor:left");
            Redis::del("upload:{$matchId}:sensor:right");
            Redis::del("upload:{$matchId}:compass:left");
            Redis::del("upload:{$matchId}:compass:right");

            return true;

        }else{

            return false;

        }
    }



    /**
     * 更新进度
     * @param $userId integer 用户ID
     * @param $isFinish boolean 是否传输结束
     * */
    public static function update_process($userId,$isFinish=false){
        $processInfo    = DB::table('match_upload_process')->where('user_id',$userId)->first();

        $num            = $isFinish ? 1 : 0;
        $time           = date_time();

        if(!$processInfo){

            try{
                DB::table('match_upload_process')->insert([
                    "user_id"       => $userId,
                    "finished_num"  => $num,
                    "created_at"    => $time,
                    "updated_at"    => $time
                ]);

            }catch (\Throwable $e){

                self::update_process($userId,$isFinish);
            }

        }else{

            DB::table('match_upload_process')
                ->where('user_id',$userId)
                ->update([
                    'updated_at'    =>$time,
                    "finished_num"  =>$processInfo->finished_num+$num,
                    "noticed"       => 0
                ]);
        }
    }

    /**
     * 更新上传进度V2
     * @param $userId integer
     * @param $foot string
     * @param $number integer
     * */
    public static function update_process_v2($userId,$foot,$number){

        $info   = self::where('user_id',$userId)->first();
        $colum  = $foot."_finished_num";
        $data   = [];
        $data["finished_num"]   = $info->finished_num + $number;
        $data[$colum]           = $info->$colum + $number;
        self::where('user_id',$userId)->update($data);
    }


    /**
     * 检查传输中断的用户
     * */
    public static function check_match_upload_status()
    {
        $now    = time();

        $users = self::where('noticed',0)
            ->whereRaw('now() - updated_at > 60')
            ->get();

        foreach($users as $user){

            if($now - strtotime($user->updated_at) < 60){

                continue;
            }

            $userId     = $user->user_id;
            self::where('user_id',$userId)->update(['noticed'=>1]);

            //极光推送
            jpush_content("异常提醒","您的比赛数据已中断了，请打开APP继续上传",4002,1,$userId);
        }
    }

    /**
     * 检查是否上传完毕
     * @param $userId integer 用户ID
     * @param $delete boolean 是否删除数据
     * @return boolean
     * */
    public static function check_upload_finish($userId,$delete=false){

        $info = self::where('user_id',$userId)->first();

        if($info->finished_num == 5){

            if($delete){

                $info->delete();
            }
            
            return true;
        }
        return false;
    }

    /**
     * 保存数据总量
     * @param $userId integer
     * @param $foot string
     * @param $num integer
     * */
    public static function save_total_num($userId,$foot,$num){

        $info   = self::where('user_id',$userId)->first();
        $newInfo= [];
        $newInfo[$foot."_num"] = $num;

        if($info){

            DB::table('match_upload_process')
                ->where('user_id',$userId)->update($newInfo);

        }else{
            $newInfo1 = [
                "user_id"       => $userId,
                "created_at"    => now(),
                "updated_at"    => now()
            ];

            $newInfo    = array_merge($newInfo,$newInfo1);
            DB::table('match_upload_process')->insert($newInfo);
        }
    }

    public static function get_upload_state($userId){

        return self::where('user_id',$userId)->first();
    }
}
