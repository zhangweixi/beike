<?php

namespace App\Http\Controllers\Service;
use App\Common\Http;

class Match
{
    public static function create_compass_angle($infile,$outfile,$compassVersion=0){
        $http   = new Http();
        $url    = config('app.matlabhost').'/compass';
        //$url    = "http://localhost:5000/compass";
        //$url    = "http://dev1.api.launchever.cn/api/matchCaculate/upload";
        $data   = file_get_contents($infile);
        $data   = trim($data);
        $md5    = md5($data);
        $res    = $http->url($url)
            ->method("post")
            ->set_data(["compassVersion"=>$compassVersion,"compassSensorData"=>$data,'md5'=>$md5])
            ->send();
        $res = \GuzzleHttp\json_decode($res);

        if($res->code == 200)
        {
            file_put_contents($outfile,$res->data);
        }

        return $res->code == 200 ? true : false;
    }
}
