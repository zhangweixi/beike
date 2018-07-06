<?php
namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use function foo\func;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Common\Http;


class App extends Controller{

    public function get_config(Request $request){
        mylogger($request->all());
        return $request->all();

        //1.APP版本
        $versionIos        = "0.0.1";
        $versionAndroid    = "0.0.1";

        //记录用户设备类型
        $userId             = $request->input('userId',0);
        if($userId > 0) //记录设备信息
        {
            $clientInfo     = [
                'user_id'   => $userId,
                'lat'       => $request->input('lat'),
                'lon'       => $request->input('lon'),
                'version'   => $request->header('Client-Version',''),
                'phone'     => $request->input('phone'),
                'created_at'=> date_time()
            ];
            DB::table('user_use_log')->insert($clientInfo);
        }


        return apiData()
            ->set_data('versionIos',$versionIos)
            ->set_data('versionAndroid',$versionAndroid)
            ->set_date('time',date('Y-m-d H:i:s'))
            ->send();
    }


    public function socket()
    {
        $http = new Http();
        $http->host("dev1.api.launchever.cn").url("api/appConfig");
        return ['ok'];
    }



}