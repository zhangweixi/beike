<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Redis;

class BaseMatchUploadProcessModel extends Model
{
    protected $table = "match_upload_process";

    protected $primaryKey = "user_id";
    public static function get_upload_process($userId){
        $processInfo    = DB::table('match_upload_process')->where('user_id',$userId)->first();
        return $processInfo;
    }

    /**
     * 初始化上传进度
     * @param $userId integer
     * @param $reset boolean
     * */
    public static function init_upload_process($userId,$reset=false){

        $processInfo    = self::get_upload_process($userId);

        $time           = date_time();

        if($processInfo){

            if($reset){
                $finishedNum    = 0;
                $processInfo->left_finished_num = 0;
                $processInfo->left_num = 0;
                $processInfo->right_finished_num = 0;
                $processInfo->right_num = 0;
            }else{
                $finishedNum   = $processInfo->finished_num;

                //如果当前设备已完成数量和当前设备总量相等，则将已完成量和总量都重置为0
                if($processInfo->left_num == $processInfo->left_finished_num){

                    $finishedNum   -= $processInfo->left_num;
                    $processInfo->left_finished_num = 0;
                    $processInfo->left_num          = 0;
                }

                if($processInfo->right_num == $processInfo->right_finished_num){

                    $finishedNum   -= $processInfo->right_num;
                    $processInfo->right_num             = 0;
                    $processInfo->right_finished_num    = 0;
                }
            }


            $newProgress = [
                'updated_at'        => $time,
                "finished_num"      => $finishedNum,
                "left_finished_num" => $processInfo->left_finished_num,
                "right_finished_num"=> $processInfo->right_finished_num,
                "left_num"          => $processInfo->left_num,
                "right_num"         => $processInfo->right_num,
            ];
            DB::table('match_upload_process')
                ->where('user_id',$userId)
                ->update($newProgress);
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

        //self::reset_uploa_process($userId,$foot);
        $info   = self::get_upload_process($userId);
        $newInfo= [];
        $newInfo[$foot."_num"] = $num;

        if($info){

            $key = $foot."_finished_num";
            $info->$key     = 0;
            $newInfo[$foot."_finished_num"]     = 0;
            $newInfo['finished_num']            = $info->left_finished_num + $info->right_finished_num;
            self::where('user_id',$userId)->update($newInfo);

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

    /**
     * @param $userId int
     * */
    public static function reset_uploa_process($userId,$foot){

        $process    = self::get_upload_process($userId);
        $updateTime = strtotime($process->updated_at);

        if(time() - $updateTime > 10){ //如果更新时间大于20S，说明是一次新的上传，将总的已上传归零

            $upData['finished_num'] = 0;
            $upData['left_num']     = 0;
            $upData['right_num']    = 0;
            $upData['left_finished_num']    = 0;
            $upData['right_finished_num']   = 0;
            self::where('user_id',$userId)->update($upData);
        }

    }
    public static function get_upload_state($userId){

        return self::where('user_id',$userId)->first();
    }
}
