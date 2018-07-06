<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

class HomeController extends Controller{


    /**
     * app配置
     * */
    public function app_config(){

        //1.APP版本

        $versionIos        = "";
        $versionAndroid    = "";


        return apiData()
            ->set_data('versionIos',$versionIos)
            ->set_data('versionAndroid',$versionAndroid)
            ->set_date('time',date('Y-m-d H:i:s'))
            ->send();
    }

}