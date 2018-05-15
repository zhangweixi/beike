<?php
namespace APP\Common;

use JWTAuth,JWTFactory;
use Illuminate\Support\Facades\Redis;
use Request;

class ApiToken{

    public $code;
    public $error;
    public $token;
    public $tokenInfo;

    /**
     * 创建唯一的token
     * @param int $userId 用户ID
     * */
    public function create_only_token($userId)
    {
        //删除该用户以前的token
        //$key = "U_".$userId;
        //Redis::del($key);
        $this->destory_token($userId);
        return $this->create_token($userId);
    }


    /**
     * 创建token
     * @param int $userId 用户ID
     * */
    public function create_token($userId)
    {
        $customClaims = [
            'sub' => ''//
        ];

        $payload    = JWTFactory::make($customClaims);
        $token      = JWTAuth::encode($payload)->get();
        $tokenInfo  = ['token'=>$token,'user_id'=>$userId,'endTime'=>time() + 20 * 24 * 60 * 60];
        $tokenInfo  = json_encode($tokenInfo);
        $key        = 'token_'.md5($token);
        Redis::set($key,$tokenInfo);        //这里存储的是token的值
        Redis::command('SADD',['U_'.$userId,$key]); //这里存储的是token的键,列表 用于清除token 是一个集合
        $this->token = $token;
        header('Authorization:'.$token);
        return $token;
    }



    /**
     * 销毁token
     * @param int $userId 用户ID
     * */
    public function destory_token($userId)
    {
        $key  = "U_".$userId;
        $tokens  = Redis::command('SMEMBERS',[$key]);
        foreach($tokens as $token)
        {
            Redis::del($token);
        }
        Redis::del($key);
    }


    /**
     * 检查token
     * param string $token 要检查的token
     * */
    public function check_token($token)
    {
        if(empty($token))
        {
            $this->code = 9005;
            $this->error= "缺少token";
            return false;
        }


        //2.检查token有效性
        $key = "token_" . md5($token);
        $tokenInfo = Redis::get($key);

        if(!$tokenInfo)
        {
            $this->code = 9006;
            $this->error= "非法token";
            return false;
        }

        $tokenInfo      = \GuzzleHttp\json_decode($tokenInfo);

        $freshTokenTime = 864000 ;//10 * 24 * 60 * 60;
        $surplusTime    = $tokenInfo->endTime - time();
        if($surplusTime <= 0)
        {
            //删除无效的token
            $this->code = 9007;
            $this->error= "token已失效";
            Redis::comand('SREM',['U_'.$tokenInfo->user_id,$key]);
            Redis::del($key);
            return false;
        }

        //小于一定的时间 刷新token
        if($surplusTime < $freshTokenTime)
        {
            $this->destory_token($tokenInfo->user_id);
            $this->create_token($tokenInfo->user_id);
        }
        $this->tokenInfo = $tokenInfo;
        return true;
    }



}