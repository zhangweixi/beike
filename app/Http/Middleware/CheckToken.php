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
        if(config('app.checkToken') == false){

            return $next($request);
        }

        //检查url是否需要检查token
        $url    = request_uri();//$_SERVER['REQUEST_URI'];
        $url    = explode("?",$url);
        $url    = $url[0];
        $url    = substr($url,8);
        $url    = explode("/",$url);
        $ctrl   = $url[0];
        $action = $url[1];

        //没有这个控制器或没有这个方法
        if(isset(self::$except[$ctrl]) && in_array($action,self::$except[$ctrl])){

            return $next($request);
        }

        $token      = $request->header("token");
        $loginToken = new LoginToken();
        if($request->input('dev')) {
            return $next($request);
        }
        
        if($token && $loginToken->token($token)->check()){

            return $next($request);
        }

        file_put_contents(public_path("logs/token.log"),$_SERVER['REQUEST_URI']."\n",FILE_APPEND);
        return apiData()->send(9001,"token无效");
    }

    //过滤的路由
    static $except =[

        'user'=>[
            "login",//登录
            "wx_qq_login",
        ],
        "match"=>[
            "upload",
            "saveDataNum"
        ],
        'test'=>[
            "test",
            "index"
        ]
    ];
}
