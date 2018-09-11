<?php

namespace App\Http\Controllers\Api\V1;

use App\Jobs\AnalysisMatchData;
use App\Models\Base\BaseMatchResultModel;
use App\Models\V1\MatchModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\V1\CourtModel;
use App\Http\Controllers\Service\Court;
use App\Http\Controllers\Service\GPSPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


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
            "boxs"      => ''
        ];

        foreach($points as $key => $p)
        {
            $courtData["p_".$key]   = implode(",",$this->str_to_gps($p));
        }

        $courtModel = new CourtModel();
        $courtId    = $courtModel->add_court($courtData);

        $this->calculate_court($courtId); //计算足球场的数据

        return apiData()->set_data('courtId',$courtId)->send(200,'SUCCESS');
    }


    /**
     * 检查单个jps是否有效
     * */
    public function check_single_gps(Request $request)
    {
        $gps        = $request->input('gps');
        $lat        = $request->input('latitude',0);
        $lon        = $request->input('longitude',0);

        if(empty($gps))
        {
            return apiData()->send(2001,"GPS为空");
        }

        $gpsInfo    = $this->str_to_gps($gps);

        if($gpsInfo['lon'] == 0 || $gpsInfo['lat'] == 0)
        {
            $code   = 2001;
            $msg    = "GPS无效";

        }elseif($lat > 0 && $lon > 0){

            //将设备GPS转换成百度GPS
            $gpsInfo  = gps_to_bdgps([['lat'=>$gpsInfo['lat'],'lon'=>$gpsInfo['lon']]]);
            $gpsInfo  = $gpsInfo[0];

            //检查手机的GPS和设备的GPS的距离
            $distance = gps_distance($lon,$lat,$gpsInfo['lon'],$gpsInfo['lat']);

            if($distance > 3) {

                $code   = 2004;
                $msg    = "设备与手机距离{$distance},定位不准确，请重新定位";

            } else {

                $code   = 200;
                $msg    = "SUCCESS";
            }
        }

        return apiData()->send($code,$msg);
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


    /*
     * 显示足球场地图
     * */
    public function court_border(Request $request)
    {
        $matchId        = $request->input('matchId');

        $courtFile      = "match/court-".$matchId.".json";
        $has            = Storage::disk('web')->has($courtFile);
        if(!$has) {

            $matchInfo      = MatchModel::find($matchId);
            $info           = CourtModel::find($matchInfo->court_id);

            $points         = \GuzzleHttp\json_decode($info->boxs);

            $points->A_D    = gps_to_bdgps($points->A_D);
            $points->AF_DE  = gps_to_bdgps($points->AF_DE);
            $points->F_E    = gps_to_bdgps($points->F_E);


            $arr            = [];
            foreach($points->center as $center)
            {
                foreach($center as $p)
                {
                    array_push($arr,$p);
                }
            }
            $points->center  = gps_to_bdgps($arr);

            Storage::disk('web')->put($courtFile,\GuzzleHttp\json_encode($points));

        }else{

            $points         = Storage::disk('web')->get($courtFile);
            $points         = \GuzzleHttp\json_decode($points);

        }
        return apiData()->add('points',$points)->send();
    }


    /*
     * 显示足球内部热点图
     * */
    public function court_content(Request $request)
    {
        $matchId    = $request->input('matchId');

        $matchInfo  = MatchModel::find($matchId);
        $gpsFile    = "match/".$matchId."-bd-gps.json";

        $has        = Storage::disk('web')->has($gpsFile);
        if(!$has) {

            $allGps     = [];
            $table      = "user_".$matchInfo->user_id."_gps";
            DB::connection('matchdata')
                ->table($table)
                ->where('match_id',$matchId)
                ->orderBy('id')
                ->select('lat','lon')
                ->chunk(1000,function($gpsList) use (&$allGps)
                {
                    foreach($gpsList as $gps)
                    {
                        if($gps->lat == 0) continue;

                        $gps->lat   = gps_to_gps($gps->lat);
                        $gps->lon   = gps_to_gps($gps->lon);
                        array_push($allGps,$gps);
                    }
                });

            $allGps = gps_to_bdgps($allGps);

            Storage::disk('web')->put($gpsFile,\GuzzleHttp\json_encode($allGps));

        }
        $points = Storage::disk('web')->get($gpsFile);
        $points = \GuzzleHttp\json_decode($points);

        return apiData()->set_data('points',$points)->send();
    }



    /**
     * 计算足球场数据
     * @param $courtId integer 足球场ID
     * */
    public function calculate_court($courtId=0)
    {
        //$courtId    = 66;

        $court  = new Court();
        $courtModel = new CourtModel();
        $courtInfo  = $courtModel->find($courtId);

        $pa         = explode(',',$courtInfo->p_a);
        $pd         = explode(',',$courtInfo->p_d);
        $pe         = explode(',',$courtInfo->p_e);
        $pf         = explode(',',$courtInfo->p_f);

        $A          = new GPSPoint($pa[0],$pa[1]);
        $B          = new GPSPoint(0,0);
        $C          = new GPSPoint(0,0);
        $D          = new GPSPoint($pd[0],$pd[1]);
        $E          = new GPSPoint($pe[0],$pe[1]);
        $F          = new GPSPoint($pf[0],$pf[1]);


        if(0)
        {
            $A =    new GPSPoint(1,10);
            $B =    new GPSPoint(1,7);
            $C =    new GPSPoint(1,4);
            $D =    new GPSPoint(1,1);
            $E =    new GPSPoint(10,1);
            $F =    new GPSPoint(10,10);

            if(0)
            {
                $A =    new GPSPoint(31.2904461856,121.3755366212);
                $B =    new GPSPoint(0,0);
                $C =    new GPSPoint(0,0);
                $D =    new GPSPoint(31.2875212840,121.3751932984);
                $E =    new GPSPoint(31.2872003645,121.3783046609);
                $F =    new GPSPoint(31.2901894581,121.3786157971);
            }
        }

        $points =  $court->calculate_court($A,$B,$C,$D,$E,$F);


        //将切割的图存放在数据库
        $points = \GuzzleHttp\json_encode($points);

        $courtModel = new CourtModel();
        $courtModel->where('court_id',$courtId)->update(['boxs'=>$points]);


        //return apiData()->add('points',$points)->send();
    }


    public function find_gps(Request $request)
    {
        set_time_limit(120);


        $courtId    = $request->input('courtId');

        $courtInfo  = CourtModel::find($courtId);

        $points     = $courtInfo->boxs;
        $points     =  \GuzzleHttp\json_decode($points);

        $court      = new Court();
        $court->set_centers($points->center);

        $points = [
            ['lat'=>31.2902994842,'lon'=>121.3757082826],
            ['lat'=>31.2870628272,'lon'=>121.3813301927],
            ['lat'=>31.2894926226,'lon'=>121.3805899030],
            ['lat'=>31.2883648386,'lon'=>121.3777896768],
            ['lat'=>31.2878605405,'lon'=>121.3794633752],
            ['lat'=>31.2873562398,'lon'=>121.3783261186]
        ];

        for($i=0;$i<8;$i++)
        {

            $points = array_merge($points,$points);
        }

        //dd(count($points));
        mylogger('begin');
        $mapData= $court->court_hot_map($points);
        mylogger('end');

        return $mapData;
    }


    public function test_gps_map()
    {
        $matchId    = 356;

         $gpsData = [
           ['lat'=>31.2902994842,'lon'=>121.3757082826],
           ['lat'=>31.2870628272,'lon'=>121.3813301927],
           ['lat'=>31.2894926226,'lon'=>121.3805899030],
           ['lat'=>31.2883648386,'lon'=>121.3777896768],
           ['lat'=>31.2878605405,'lon'=>121.3794633752],
           ['lat'=>31.2873562398,'lon'=>121.3783261186]
       ];

//       for($i=0;$i<11;$i++)
//       {
//
//           $gpsData = array_merge($gpsData,$gpsData);
//       }


        $job    = new AnalysisMatchData(0);
        $job->create_gps_map($matchId,$gpsData);
    }


    public function temp()
    {
        $ana    = new AnalysisMatchData(0);
        $ana->create_gps_map(364);
        return "ok";
    }
}
