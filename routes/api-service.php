<?php
$api = app('Dingo\Api\Routing\Router');

$api->version('v1',['prefix'=>"api",'middleware'=>['saveApiData'],'namespace'=>"App\Http\Controllers\Service"],function ($api)
{
    $api->post("getMobileCode",     "Mobile@get_mobile_code");      //获取验证码
    $api->post("checkMobileCode",   "Mobile@check_mobile_code");    //检查验证码
    $api->post("mobileCallback",    "Mobile@mobile_callback");      //验证码回调
    $api->post('appConfig',         "App@get_config");              //获得APP配置
    $api->post('socket',            "App@socket");                  //获得APP配置
    $api->post('qiniuToken',        "Qiniu@get_token");             //获取七牛Token
    $api->any('matchCaculate/{action}',"MatchCaculate@action");		//算法系统
    $api->any('wechat/{action}',	"Wechat@action");				//微信
    $api->any('matchGrade/{action}',"MatchGrade@action");			//比赛分数计算方法
    $api->any('mgaonline',          "App@mgaonline");               //AGPS
    $api->any('ads',                'App@ads');                     //广告
});
