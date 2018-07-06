<?php

use Illuminate\Http\Request;


Route::get('/api/speed/index/user','App\Http\Controllers\Speed\IndexController@user');

$api = app('Dingo\Api\Routing\Router');
//$api->version('v1',['prefix'=>"api/apeed",'middleware'=>['apiSign','saveApiData'],'namespace'=>"App\Http\Controllers\Api\V1"],function ($api)

$api->version('v1',['prefix'=>"api/speed",'middleware'=>['saveApiData'],'namespace'=>"App\Http\Controllers\Speed"],function ($api)
{
    $api->any('test',function(){return "helolo";});
    $api->any('index/{action}','IndexController@action');

});
