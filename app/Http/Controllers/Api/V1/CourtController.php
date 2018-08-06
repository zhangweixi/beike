<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\V1\CourtModel;
use App\Http\Controllers\Service\Court;
use App\Http\Controllers\Service\GPSPoint;




class CourtController extends Controller
{

    public function add_court(Request $request)
    {
        $userId     = $request->input('userId');
        $points     = $request->input('points');
        $points     = \GuzzleHttp\json_decode($points,true);

        //2.添加新的球场
        $courtData  = [
            'user_id'   => $userId,
            'lat'       => 0,   //手机的维度
            'lon'       => 0,   //手机经度
            'address'   => "",  //球场地址
            'width'     => 0,   //球场宽度
            'length'    => 0,   //球场长度
        ];

        foreach($points as $key => $p)
        {
            $courtData["p_".$key]   = implode(",",$this->str_to_gps($p));
        }

        $courtModel = new CourtModel();
        $courtId    = $courtModel->add_court($courtData);

        //$this->calculate_court($courtId); //计算足球场的数据

        return apiData()->set_data('courtId',$courtId)->send(200,'SUCCESS');
    }

    /* *
     * 单条GPS转经纬度
     * @param $str string
     * @return Array
     * */
    public function str_to_gps($str="")
    {
        //$str    = "322c312c232323237463a7f36401000020000000474e4747412c2c2c2c2c2c302c30302c39392e39392c2c2c2c2c2c2a35360d0a00";
        //$str = "322c312c232323234cb75bf36401000046000000474e47474132343033312e36302c333131302e32333436332c4e2c31323132332e38393039342c452c312c30362c322e31362c32302e372c4d2c392e372c4d2c2c2a34340d0a";
        //$str = "322c312c23232323222f59f36401000048000000474e4747412c3032333734352e37302c333131302e31373936352c4e2c31323132332e38383130352c452c312c30362c322e31352c33332e342c4d2c392e372c4d2c2c2a34300d0a";

        $arr    = explode('23232323',$str);
        $str    = substr($arr[1],24);
        $gps    = strToAscll($str);
        $gps    = explode(",",$gps);

        $lat    = gps_to_gps($gps[2]);
        $lon    = gps_to_gps($gps[4]);
        return ['lat'=>$lat,'lon'=>$lon];
    }


    /**
     * 计算足球场数据
     * */
    public function calculate_court($courtId=0)
    {

        $court  = new Court();

        $A =    new GPSPoint(0,0);
        $B =    new GPSPoint(0,0);
        $C =    new GPSPoint(0,0);
        $D =    new GPSPoint(0,5);
        $E =    new GPSPoint(5,5);
        $F =    new GPSPoint(5,0);

        if(0)
        {
            $A =    new GPSPoint(31.1799000000,121.3828080000);
            $B =    new GPSPoint(0,0);
            $C =    new GPSPoint(0,0);
            $D =    new GPSPoint(31.1762550000,121.3843530000);
            $E =    new GPSPoint(31.1777070000,121.3888450000);
            $F =    new GPSPoint(31.1810440000,121.3873000000);
        }

        $points =  $court->calculate_court($A,$B,$C,$D,$E,$F);

        $points = \GuzzleHttp\json_encode($points);

        return $points;
    }


}
