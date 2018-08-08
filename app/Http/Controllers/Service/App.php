<?php
namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Common\Http;
use App\Common\Geohash;


class App extends Controller{

    public function get_config(Request $request){

        //1.APP版本
        $versionIos        = "0.0.1";
        $versionAndroid    = "0.0.1";

        //记录用户设备类型
        $userId             = $request->input('userId',0);
        if($userId > 0) //记录设备信息
        {
            $lat            = $request->input('lat',0);
            $lon            = $request->input('lon',0);

            $clientInfo     = [
                'user_id'   => $userId,
                'lat'       => $lat,
                'lon'       => $lon,
                'version'   => $request->header('Client-Version',''),
                'phone'     => $request->input('phone'),
                'created_at'=> date_time()
            ];
            DB::table('user_use_log')->insert($clientInfo);

            //如果用户的地理位置为空，把本次置位置置入其中，如果不是空，则要判断是否是常用地
            if($lat != 0)
            {
                $geohash    = new Geohash();
                $gpshash    = $geohash->encode($lat,$lon);
                DB::table('users')->where('id',$userId)->update(['lat'=>$lat,'lon'=>$lon,'geohash'=>$gpshash]);
            }
        }


        return apiData()
            ->set_data('versionIos',$versionIos)
            ->set_data('versionAndroid',$versionAndroid)
            ->set_data('time',date('Y-m-d H:i:s'))
            ->send();
    }


    public function socket()
    {
        $http = new Http();
        $http->host("dev1.api.launchever.cn").url("api/appConfig");
        return ['ok'];
    }



}