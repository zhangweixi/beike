<?php
namespace APP\Common;

use JWTAuth,JWTFactory;
use Illuminate\Support\Facades\Redis;
use Request;

class ApiToken{

    public $code;
    public $error;
    public $token;
    public $userId;

    /**
     * 创建token
     * @param int $userId 用户ID
     * */
    public function create_token($userId)
    {
        $prev   = 10000000 + $userId;
        $token  = $prev.md5(create_member_number());
        return base64_encode($token);

    }


//    public function parse_token()
//    {
//        $token  = Request::header('token');
//        if($token)
//        {
//            $token  = base64_decode($token);
//            $userId = substr($token,0,8);
//
//        }
//    }

    static function parse_token()
    {

    }
}