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
use Maatwebsite\Excel\Facades\Excel;


class CourtController extends Controller
{

    public function add_court(Request $request)
    {
        $userId     = $request->input('userId');
        $gpsGroupId = $request->input('gpsGroupId');

        //$points     = $request->input('points');
        //$points     = \GuzzleHttp\json_decode($points,true);

        //2.添加新的球场
        $courtData  = [
            'user_id'   => $userId,
            'lat'       => 0,   //手机的维度
            'lon'       => 0,   //手机经度
            'address'   => "",  //球场地址
            'width'     => 0,   //球场宽度
            'length'    => 0,   //球场长度
            "boxs"      => '',
            'gps_group_id'  => $gpsGroupId
        ];


        $points  = $this->get_points_center($gpsGroupId);

        foreach($points as $key => $p)
        {
            $courtData["p_".strtolower($p->position)]   = $p->device;
        }


        $courtModel = new CourtModel();
        $courtId    = $courtModel->add_court($courtData);

        $this->calculate_court($courtId); //计算足球场的数据

        $configFile = $this->create_court_gps_config($courtId);//生成GPS的配置图

        $courtModel->where('court_id',$courtId)->update(['config_file'=>$configFile]);

        return apiData()->set_data('courtId',$courtId)->send(200,'SUCCESS');
    }


    public function get_points_center($gpsGroupId)
    {


        $sql = "SELECT `position`,CONCAT(AVG(device_lat),',',AVG(device_lon)) AS device 
                from football_court_point 
                WHERE gps_group_id = '{$gpsGroupId}' GROUP BY `position`";

        $gpsInfo    = DB::select($sql);

        return $gpsInfo;

        //获取点的中心
        $points = DB::table('football_court_point')->where('gps_group_id',$gpsGroupId)->get();
        $positions=[];
        foreach($points as $point)
        {
            $posi   = $point->position;
            isset($positions[$posi]) ? array_push($positions[$posi],$point) : $positions[$posi] = [$point];
        }



        return $positions;
    }

    /**
     * 标记测量足球场
     * */
    public function remark_mesure_court(Request $request)
    {
        $userId     = $request->input('userId');

        //清空数据
        //DB::table('football_court_point')->where('user_id',$userId)->delete();

        $uniqueId   = create_member_number();
        return  apiData()->add('gpsGroupId',$uniqueId)->send();

    }

    /**
     * 检查单个jps是否有效
     * */
    public function check_single_gps(Request $request)
    {
        $gps        = $request->input('gps');
        $lat        = $request->input('latitude',0);
        $lon        = $request->input('longitude',0);
        $gpsGroupId = $request->input('gpsGroupId');
        $userId     = $request->input('userId');
        $position   = $request->input('position');


        if(empty($gps))
        {
            return apiData()->send(2001,"GPS为空");
        }

        $gpsInfo    = $this->str_to_gps($gps);

        if($gpsInfo['lon'] == 0 || $gpsInfo['lat'] == 0)
        {
            $code   = 2001;
            $msg    = "GPS无效";

        }else{

            //存储GPS信息
            $gpsPoint   = [
                "user_id"       => $userId,
                "gps_group_id"  => $gpsGroupId,
                "position"      => $position,
                "mobile_lat"    => $lat,
                "mobile_lon"    => $lon,
                "device_lat"    => $gpsInfo['lat'],
                "device_lon"    => $gpsInfo['lon']
            ];

            DB::table('football_court_point')->insert($gpsPoint);

            //检查点数是否达到一定要求
            $gpsNum = DB::table('football_court_point')->where('gps_group_id',$gpsGroupId)->where('position',$position)->count();

            if($gpsNum == 20)
            {
                return apiData()->send();
            }



            //将设备GPS转换成百度GPS
            $gpsBaidu = gps_to_bdgps([['lat'=>$gpsInfo['lat'],'lon'=>$gpsInfo['lon']]]);
            $gpsBaidu  = $gpsBaidu[0];


            //检查手机的GPS和设备的GPS的距离
            $distance = gps_distance($lon,$lat,$gpsBaidu['lon'],$gpsBaidu['lat']);

            if($distance > 2) {

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
        $arr    = explode('23232323',$str);
        $str    = substr($arr[1],24);
        $gps    = strToAscll($str);
        $gps    = explode(",",$gps);

        $lat    = $gps[2];
        $lon    = $gps[4];

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


    /*
     * 绘制球场
     * */
    public function draw_court(Request $request)
    {
        $gpsGroupId = $request->input('gpsGroupId');
        $sql = "SELECT position,
                CONCAT(AVG(device_lon),',',AVG(device_lat)) AS device ,
                CONCAT(AVG(mobile_lon),',',AVG(mobile_lat)) as  mobile,
                AVG(device_lon) device_lon,
                AVG(device_lat) device_lat,
                AVG(mobile_lon) mobile_lon,
                AVG(mobile_lat) mobile_lat
                from football_court_point 
                WHERE gps_group_id = '{$gpsGroupId}' GROUP BY position";
        $gpsInfo    = DB::select($sql);


        foreach($gpsInfo as $point)
        {
            $info = gps_to_bdgps([['lat'=>$point->device_lat,'lon'=>$point->device_lon]]);
            $point->device_lat  = $info[0]['lat'];
            $point->device_lon  = $info[0]['lon'];
        }

        return $gpsInfo;
    }


    /*
   * 绘制球场
   * */
    public function draw_court_all(Request $request)
    {
        $gpsGroupId = $request->input('gpsGroupId');
        $sql = "SELECT * from football_court_point 
                WHERE gps_group_id = '{$gpsGroupId}' ";

        $gpsInfo    = DB::select($sql);
        $points     = [
            'device'=>[],
            'mobile'=>[]
        ];

        foreach($gpsInfo as $point)
        {
            array_push($points['mobile'],['lat'=>$point->mobile_lat,'lon'=>$point->mobile_lon]);
            array_push($points['device'],['lat'=>$point->device_lat,'lon'=>$point->device_lon]);
        }

        $points['device']   = gps_to_bdgps($points['device']);

        return $points;
    }



    /**
     * 计算足球场数据
     * @param $courtId integer 足球场ID
     * */
    public function calculate_court($courtId=0)
    {
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

        $points =  $this->cut_court_to_small_box($courtId);

        $points = \GuzzleHttp\json_encode($points); //将切割的图存放在数据库

        CourtModel::where('court_id',$courtId)->update(['boxs'=>$points]);
    }


    /**
     * 将足球场切分成小格子
     * @param $courtId integer
     * @param $latNum integer
     * @param $lonNum integer
     * @return array
     * */
    public function cut_court_to_small_box($courtId,$latNum=0,$lonNum =0)
    {
        $courtInfo  = CourtModel::find($courtId);

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


        $court      = new Court();

        if($latNum > 0)
        {
            $court->set_lat_num($latNum);
        }

        if($lonNum > 0)
        {
            $court->set_lon_num($lonNum);
        }

        return $court->calculate_court($A,$B,$C,$D,$E,$F);
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
        file_get_contents("http://matlab.launchever.cn/api/matchCaculate/call_matlab?matchId=564&sign=4587d4bd9ba3ea31124bfa72474e44c5");

        return "ok";
        return $this->get_points_center("201809121504737483");
        return $this->create_court_gps_config(72,11);

        $ana    = new AnalysisMatchData(0);
        $ana->create_gps_map(364);
        return "ok";
    }


    /**
     *
     * 创建球场GPS配置文件
     * @param $courtId integer 球场ID
     * @param $courtType integer 球场类型
     * @return array
     * */
    public function create_court_gps_config($courtId)
    {

        $points = $this->cut_court_to_small_box($courtId,40,25);

        $points = $points['center'];

        $courtInfo  = CourtModel::find($courtId);


        $configBoxs     = DB::table('football_court_type')->where('people_num',11)->first();
        $configBoxs     = \GuzzleHttp\json_decode($configBoxs->angles);
        $filepath       = "uploads/court-config/{$courtId}.txt";
        $courtAngleConfiFile = public_path($filepath);
        mk_dir(public_path("uploads/court-config"));
        $config = "";

        foreach($configBoxs as $line)
        {
            foreach($line as $box)
            {
                $box->lat   = $points[$box->x][$box->y]->lat;
                $box->lon   = $points[$box->x][$box->y]->lon;
                $big        = $box->type == "D" ? 1 : 0;
                $small      = $box->type == 'X' ? 1 : 0;

                $config .= $box->lat . " ".$box->lon." ".$big." ".$small." ".$box->angle."\n";
            }
        }


        $b = explode(",",$courtInfo->p_b);
        $c = explode(",",$courtInfo->p_c);

        $config .= implode(" ",$b)." ".implode(" ",$c)." 0";

        file_put_contents($courtAngleConfiFile,$config);

        return $filepath;
    }


}
