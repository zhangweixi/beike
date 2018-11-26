<?php
$api = app('Dingo\Api\Routing\Router');
//$middleware = ['saveApiData','apiSign','checkToken'];
$middleware = ['saveApiData','checkToken'];
$api->version('v1',['prefix'=>"api/v1","middleware"=>$middleware,'namespace'=>"App\Http\Controllers\Api\V1"],function ($api)
{
    $api->any('/match/{action}',       'MatchController@action');

    $api->post('/user/{action}',        'UserController@action');

    //球场
    $api->post('/court/{action}',       'CourtController@action');

    //设备
    $api->post('/device/{action}',      'DeviceController@action');

    //算法系统
    $api->post('/matlab/{action}',     'MatlabController@action');

    //社区比赛
    $api->post('/sqmatch/{action}',     'ShequMatchController@action');

    //朋友
    $api->post('/friend/{action}',      'FriendController@action');


    $api->any('/test/{action}',         'TestController@action');

});
