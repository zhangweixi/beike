<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\V1\CourtModel;

class CourtController extends Controller
{


    public function add_court(Request $request)
    {

        $userId     = $request->input('userId');
        $points     = $request->input('points');
        $points     = \GuzzleHttp\json_decode($points,true);

        //1.分析球场数据 把球场拆分成一个网格，存入数据库
        //用4个点来构建一个球场模型 A,D,E,F

        $points = [
            'a'=>'',
            'd'=>'',
            'e'=>'',
            'f'=>''
        ];


        //2.添加新的球场
        $courtData  = [
            'user_id'   => $userId,
            'lat'       => 0,
            'lon'       => 0,
            'address'   => "",
            'width'     => 0,
            'length'    => 0,
        ];

        foreach($points as $k=>$v)
        {
            $courtData['p_'.$k] = $this->analysis_point($v);
        }


        //return $courtData;
        $courtModel = new CourtModel();
        //$courtId    = $courtModel->add_court($courtData);

        //解析球场数据
        $pyCourt    = app_path('python/court.py');
        $command    = "python $pyCourt > .temp.court.log &";

        system_exec($command);

        return $pyCourt;


        //调用python在后台运行
        return exec("python ");
        exit;

        dd();




        return apiData()->set_data('courtId',$courtId)->send(200,'SUCCESS');
    }


    /**
     * 这里的分析是获得从原始字符串获得经纬度
     * @param $gps string GPS信息
     * @return string
     * */
    private function analysis_point($gps)
    {

        return rand(1000,10000).",".rand(1000,10000);
    }

    public function visualip(Request $request){

        $ip1 =  getenv('HTTP_CLIENT_IP');
        $ip2 = getenv('HTTP_X_FORWARDED_FOR');
        $ip3 = getenv('REMOTE_ADDR');

        return apiData()->set_data('ip',$ip1."/".$ip2."/".$ip3)->send(200,'ok');

    }
}
