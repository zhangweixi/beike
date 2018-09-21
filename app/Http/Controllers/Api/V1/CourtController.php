<?php

namespace App\Http\Controllers\Api\V1;

use App\Common\Http;
use App\Http\Controllers\Service\MatchCaculate;
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
        $userId     = $request->input('userId',0);
        $gpsGroupId = $request->input('gpsGroupId');

        //2.添加新的球场
        $courtData  = [
            'user_id'       => $userId,
            'gps_group_id'  => $gpsGroupId,
        ];

        $courtModel     = new CourtModel();
        $courtId        = $courtModel->add_court($courtData);

        //异步生成足球模型
        MatchCaculate::call_matlab_court_init($courtId);

        return apiData()->set_data('courtId',$courtId)->send(200,'SUCCESS');
    }


    /**
     * 获得中心点
     * @param $gpsGroupId string
     * @return array
     * */
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
                "device_lon"    => $gpsInfo['lon'],
                'created_at'    => date_time()
            ];

            DB::table('football_court_point')->insert($gpsPoint);

            //检查点数是否达到一定要求

            if($position == 'A')
            {
                $gpsNum = DB::table('football_court_point')->where('gps_group_id',$gpsGroupId)->where('position',$position)->count();

            }else{

                $gpsNum = 1000;
            }



            //将设备GPS转换成百度GPS
            $gpsBaidu = gps_to_bdgps([['lat'=>gps_to_gps($gpsInfo['lat']),'lon'=>gps_to_gps($gpsInfo['lon'])]]);

            $gpsBaidu  = $gpsBaidu[0];


            //检查手机的GPS和设备的GPS的距离
            $distance = gps_distance($lon,$lat,$gpsBaidu['lon'],$gpsBaidu['lat']);

            $msg    = "距离【{$distance}】";


            if($distance > 3 && $gpsNum < 20) {

                $code   = 2004;
                $msg    = $msg;

            } else {

                $code   = 200;
                $msg    = $msg."，开始";
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
        $sql = "SELECT `position`,
                CONCAT(AVG(device_lon),',',AVG(device_lat)) AS device ,
                CONCAT(AVG(mobile_lon),',',AVG(mobile_lat)) as  mobile,
                AVG(device_lon) device_lon,
                AVG(device_lat) device_lat,
                AVG(mobile_lon) mobile_lon,
                AVG(mobile_lat) mobile_lat
                from football_court_point 
                WHERE gps_group_id = '{$gpsGroupId}' 
                GROUP BY `position`";

        $gpsInfo    = DB::select($sql);


        foreach($gpsInfo as $point)
        {
            $info = gps_to_bdgps([['lat'=>gps_to_gps($point->device_lat),'lon'=>gps_to_gps($point->device_lon)]]);
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
            array_push($points['device'],['lat'=>gps_to_gps($point->device_lat),'lon'=>gps_to_gps($point->device_lon)]);
        }

        $points['device']   = gps_to_bdgps($points['device']);

        return $points;
    }


    /*
     * 从文件中读取gps显示到地图上
     * */
    public function show_file_map(Request $request)
    {
        $filepath   = $request->input('filepath');

        $gpsList        = file(public_path($filepath));

        foreach($gpsList as $key => $gps){

            $info = explode(" ",trim($gps,"\n"));
            $gpsList[$key]  = ["lat"=>gps_to_gps($info[0]),'lon'=>gps_to_gps($info[1])];
        }

        $gpsList    = gps_to_bdgps($gpsList);

        return apiData()->add('points',$gpsList)->send();
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
        $mapData= $court->create_court_hot_map($points);
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
        return str_replace("http://","",config("app.matlabhost"));
        Http::sock("matlab.launchever.cn","/api/v1/test/test?test=123");
        //Http::sock("dev1.api.launchever.cn","/api/matchCaculate/call_matlab_create_court_top_point");
        mylogger('xx');
        return "ok";

        return (new Court())->create_court_model_input_file(146);
        //return $this->create_court_gps_config(145);


        $a = [
            ['lat'=>3109.7304,'lon'=>12125.1460],
            ['lat'=>3109.7401,'lon'=>12125.1448],
            ['lat'=>3109.7492,'lon'=>12125.1436],
            ['lat'=>3109.7465,'lon'=>12125.1219],
            ['lat'=>3109.7438,'lon'=>12125.1001],
            ['lat'=>3109.7249,'lon'=>12125.1025],
            ['lat'=>3109.7346,'lon'=>12125.1013],
            ['lat'=>3109.7276,'lon'=>12125.1242]
        ];


        foreach($a as &$b)
        {
            $b['lat']   = gps_to_gps($b['lat']);
            $b['lon']   = gps_to_gps($b['lon']);
        }

        $a = gps_to_bdgps($a);

        $point  = [];
        $litter = ['A','B','C','D','E','F','G','H'];


        foreach($a as $k=> $p)
        {
            array_push($point,['mobile_lat'=>$p['lat'],'mobile_lon'=>$p['lon'],"device_lat"=>$p['lat'],'device_lon'=>$p['lon'],'gps_group_id'=>1234,'user_id'=>4,'position'=>$litter[$k]]);
        }


        DB::table('football_court_point')->insert($point);

        return $a;
        //file_get_contents("http://matlab.launchever.cn/api/matchCaculate/call_matlab?matchId=564&sign=4587d4bd9ba3ea31124bfa72474e44c5");

        //return "ok";
        //return $this->get_points_center("201809121504737483");
        return $this->create_court_gps_config(117);

        $ana    = new AnalysisMatchData(0);
        $ana->create_gps_map(364);
        return "ok";
    }





}
