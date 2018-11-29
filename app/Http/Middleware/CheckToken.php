<?php

namespace App\Http\Middleware;

use Closure;
use App\Common\LoginToken;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //检查url是否需要检查token
        $url    = $_SERVER['REQUEST_URI'];
        $url    = substr($url,8);
        $url    = explode("/",$url);
        $ctrl   = $url[0];
        $action = $url[1];

        //没有这个控制器或没有这个方法
        if(!isset(self::$except[$ctrl]) || !in_array($action,self::$except[$ctrl])){

            $token      = $request->header("token");
            $loginToken = new LoginToken();

            if(empty($token) || !$loginToken->token($token)->check()){

                file_put_contents(public_path("logs/token.log"),$_SERVER['REQUEST_URI']."\n",FILE_APPEND);
                return apiData()->send(9001,"token无效");
            }
        }
        return $next($request);
    }

    //过滤的路由
    static $except =[

        'user'=>[
            "login",//登录
        ],
        "match"=>[
            ""
        ],
        'test'=>[
            "test"
        ]
    ];
}
