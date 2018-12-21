<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

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

        $processInfo   = self::find($userId);
        $num    = $isFinish ? 1 : 0;
        $time   = date_time();

        if(is_null($processInfo)){

            self::insert([
                "user_id"       => $userId,
                "finished_num"  => $num,
                "created_at"    => $time,
                "updated_at"    => $time
            ]);

        }else{

            if($processInfo->finished_num == 4 && $isFinish){ //相当于5种类型数据全部上传完毕

                $processInfo->delete();

            }else{

                $processInfo->updated_at   = $time;
                $processInfo->finished_num = $processInfo->finished_num + $num;
                $processInfo->noticed      = 0;
                $processInfo->save();
            }
        }
    }

    /**
     * 检查传输中断的用户
     * */
    public static function check_match_upload_status()
    {
        $users = self::where('noticed',0)
            ->whereRaw('now() - updated_at > 60')
            ->get();

        foreach($users as $user){

            $userId     = $user->user_id;
            self::where('user_id',$userId)->update(['noticed',1]);

            //极光推送
            jpush_content("异常提醒","您的比赛数据已中断了，请打开APP继续上传",4002,1,$userId);
        }
    }
}
