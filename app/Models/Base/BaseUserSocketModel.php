<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseUserSocketModel extends Model
{
    protected $table = "user_socket";
    protected $timestamp = false;
    protected $guarded = [];

    /**
     * @param $fd integer socket标记
     * @param $userId integer 用户标记
     * @param $type string 连接类型
     * */
    public function bind($fd,$userId,$type){

        //检查这个用户当前有没有

        $newInfo    = [
            "fd"        => $fd,
            "user_id"   => $userId,
            "type"      => $type
        ];

        $this->create($newInfo);
    }


    /**
     * @param $fd integer
     *
     * */
    public function unconnect($fd){

        //检查是不是所有连接都下线了
        $this->where('fd',$fd)->delete();
    }

    public function detail($fd){

        $socketInfo             = $this->where('fd',$fd)->first();
        if(!$socketInfo){

            return false;
        }
        if($socketInfo->user_id > 0){

            $userInfo       = DB::table('users')
                ->select("nick_name",'mobile')
                ->where('id',$socketInfo->user_id)
                ->first();
            $socketInfo->userName   = $userInfo->nick_name;
            $socketInfo->mobile     = $userInfo->mobile;

        }else{

            $socketInfo->userName   = null;
            $socketInfo->mobile     = null;
        }

        return $socketInfo;
    }

    /**
     * 获得ws的连接句柄
     * @param $userId integer
     * @param $type string
     * */
    public static function get_user_fd($userId,$type='app'){

        return self::where('user_id',$userId)->where('type',$type)->value('fd');
    }
}
