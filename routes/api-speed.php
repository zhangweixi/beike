<?php

use Illuminate\Http\Request;

$api = app('Dingo\Api\Routing\Router');
//$api->version('v1',['prefix'=>"api/apeed",'middleware'=>['apiSign','saveApiData'],'namespace'=>"App\Http\Controllers\Api\V1"],function ($api)
$api->version('v1',['prefix'=>"api/speed",'middleware'=>['saveApiData'],'namespace'=>"App\Http\Controllers\Speed"],function ($api)
{
    $api->any('test',function(){return "helolo";});
    $api->any('index/{action}','IndexController@action');

});
