<?php
namespace App\Http\Controllers\Tools;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;



class SystemController extends Controller
{
    //统一的极光推送
    public function jpush_to_admin(Request $request)
    {
        $msg    = base64_decode($request->input('msg'));
        jpush_content("提示",$msg,0,1,config('sys.ADMIN_USER_ID'));
        return "ok";
    }
}