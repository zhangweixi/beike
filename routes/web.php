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


Route::get('/weixin/user','Speed\IndexController@user');
Route::get('/weixin/get_wx_info','Speed\IndexController@get_wx_info');

Route::any('/code','Service\Mobile@get_code');//测试获取电话号码

