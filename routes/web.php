<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/speed/weixin/{action}','Speed\Weixin@action');

Route::any('/service/upload',"Service\Upload@upload");
Route::any('/service/wechat/{action}',"Service\Wechat@action");


Route::prefix("web")->namespace("Web")->group(function()
{

	Route::any('match/{action}',"MatchController@action");
	Route::any('test/{action}',"TestController@action");

});

//Route::get('/weixin/user','Speed\IndexController@user');
//Route::get('/weixin/get_wx_info','Speed\IndexController@get_wx_info');




//端链接
Route::any('/s/{method}','Web\ShortLinkController@index');

