<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/18
 * Time: 16:56
 */

namespace App\Http\Controllers\Tools;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;



class SystemController extends Controller
{
    //统一的极光推送
    public function jpush_to_admin(Request $request)
    {
        $msg    = $request->input('msg');
        jpush_content("提示",$msg,0,1,config('sys.ADMIN_USER_ID'));
        return "ok";
    }
}