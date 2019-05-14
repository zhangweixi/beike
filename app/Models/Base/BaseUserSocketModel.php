<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseUserSocketModel extends Model
{
    protected $table = "user_socket";
    protected $timestamp = false;

    /**
     * 创建新连接
     * @param $fd integer
     * */
    public function connect($fd){

        $this->insert([
            "ws_id"         => $fd,
            "created_at"    => now()
        ]);
    }


    /**
     * @param $fd integer socket标记
     * @param $userId integer 用户标记
     * @param $type string 连接类型
     * */
    public function bind($fd,$userId,$type){

        $this->where('ws_id',$fd)->update(['user_id'=>$userId,"type"=>$type]);
    }


    /**
     * @param $fd integer
     *
     * */
    public function unconnect($fd){

        $this->where('ws_id',$fd)->delete();
    }

    public function detail($fd){

        $socketInfo             = $this->where('ws_id',$fd)->first();

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
}
