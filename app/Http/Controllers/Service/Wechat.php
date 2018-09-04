<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/4
 * Time: 11:48
 */

namespace App\Http\Controllers\Service;
use App\Http\Controllers\Controller;

class Wechat extends Controller
{

    public function serve()
    {
        $app = app('wechat.official_account');
        $app->server->push(function($message)
        {
            return "欢迎关注 overtrue！";
        });

        return $app->server->serve();
    }


}