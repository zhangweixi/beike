<?php
use Illuminate\Http\Request;

$api = app('Dingo\Api\Routing\Router');

$api->version('v1',['prefix'=>"api",'namespace'=>"App\Http\Controllers\Service"],function ($api)
{

    $api->post("getMobileCode",     "Mobile@get_mobile_code");      //获取验证码
    $api->post("checkMobileCode",   "Mobile@check_mobile_code");    //检查验证码
    $api->post("mobileCallback",    "Mobile@mobile_callback");

});