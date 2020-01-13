<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 15:03
 */

namespace App\Common;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class LoginToken
{
    private $_token;
    private $_userId;


    public function token($token){
        $this->_token = $token;
        return $this;
    }

    public function user($userId){

        $this->_userId = $userId;
        return $this;
    }


    public function create_token()
    {
        $prev       = 10000000 + $this->_userId;
        $token      = $prev.md5(create_member_number());
        $this->_token  = base64_encode($token);
        return $this;
    }

    public function forget()
    {
        Redis::del('u'.$this->_userId);

        DB::table('users')->where('id',$this->_userId)->update(['token'=>'']);
        return $this;
    }


    public function cache()
    {
        Redis::set('u'.$this->_userId,$this->_token);
        DB::table('users')->where('id',$this->_userId)->update(['token'=>$this->_token]);
        return $this;
    }

    public function check()
    {
        $token  = base64_decode($this->_token);
        $userId = substr($token,0,8);
        $userId = $userId - 10000000;

        $cacheToken = Redis::get("u".$userId);

        if($cacheToken == $this->_token){

            return true;
        }
        return false;
    }



    public function get_token()
    {
        return $this->_token;
    }
}
