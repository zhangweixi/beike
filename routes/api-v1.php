<?php

use Illuminate\Http\Request;

$api = app('Dingo\Api\Routing\Router');
//,'apiSign'
$api->version('v1',['prefix'=>"api/v1",'middleware'=>['saveApiData'],'namespace'=>"App\Http\Controllers\Api\V1"],function ($api)
{
    $api->post("test","RestFulController@test");
    $api->post("checkCode","RestFulController@check_mobile_code");

    //$api->post('/match/uploadMatchData','MatchController@upload_match_data');
    $api->post('/match/{action}',       'MatchController@action');
    $api->post('/match1/{action}',      'Match1Controller@action');


    $api->post('/user/{action}',        'UserController@action');

    //球场
    $api->post('/court/{action}',       'CourtController@action');

    //设备
    $api->post('/device/{action}',      'DeviceController@action');

    //算法系统
    $api->post('/matlab/{action}',     'MatlabController@action');

    //社区比赛
    $api->post('/sqmatch/{action}',     'ShequMatchController@action');


});
