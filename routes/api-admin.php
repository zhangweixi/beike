<?php
$api = app('Dingo\Api\Routing\Router');

//,'apiSign'

$api->version('v1',['prefix'=>"api/admin",'middleware'=>['saveApiData'],'namespace'=>"App\Http\Controllers\Api\Admin"],function ($api)
{

    $api->any('/admin/{action}',        'AdminController@action');

    $api->any('/device/{action}',       'DeviceController@action');

    $api->any('/user/{action}',         'UserController@action');

    $api->any('/match/{action}',        'MatchController@action');

    $api->any('/court/{action}',        'CourtController@action');

});
