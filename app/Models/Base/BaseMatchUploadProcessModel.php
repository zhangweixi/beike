<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;
use DB;

class BaseMatchUploadProcessModel extends Model
{
    protected $table = "match_upload_process";

    protected $primaryKey = "user_id";


    /**
     * 更新进度
     * @param $userId integer 用户ID
     * @param $isFinish boolean 是否传输结束
     * */
    public static function update_process($userId,$isFinish){
        $processInfo    = DB::table('match_upload_process')->where('user_id',$userId)->first();
        mylogger($userId);
        $num            = $isFinish ? 1 : 0;
        $time           = date_time();

        if(!$processInfo){

            mylogger("no");
            DB::table('match_upload_process')->insert([
                "user_id"       => $userId,
                "finished_num"  => $num,
                "created_at"    => $time,
                "updated_at"    => $time
            ]);

        }else{

            mylogger("yes");
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
}
