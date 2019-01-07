<?php

namespace App\Http\Controllers\Api\V1;

use App\Common\Http;
use App\Http\Controllers\Service\MatchCaculate;
use App\Jobs\AnalysisMatchData;
use App\Models\Base\BaseFootballCourtModel;
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
        $courtName  = $request->input('courtName',"");



        //2.添加新的球场
        $courtData  = [
            'user_id'       => $userId,
            'gps_group_id'  => $gpsGroupId,
            'court_name'    => $courtName
        ];
        $courtModel     = new CourtModel();
        $courtId        = $courtModel->add_court($courtData);

        MatchCaculate::call_matlab_court_init($courtId);        //调用算法系统，异步生成足球场
        BaseFootballCourtModel::join_minitor_court($courtId);   //加入监控队列
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
        $uniqueId   = create_member_number();
        return  apiData()->add('gpsGroupId',$uniqueId)->send();
    }

    /**
     * 检查单个GPS是否有效
     * */
    public function check_single_gps(Request $request)
    {
        $gps        = $request->input('gps');
        $lat        = $request->input('latitude',0);
        $lon        = $request->input('longitude',0);
        $gpsGroupId = $request->input('gpsGroupId',"");
        $userId     = $request->input('userId');
        $position   = $request->input('position',null);

        if(empty($gps))
        {
            return apiData()->send(2001,"缺少GPS");
        }

        $gpsInfo    = $this->str_to_gps($gps);

        //仅仅检查GPS
        if(strlen($gpsGroupId) == 0){

            if($gpsInfo['lat'] != 0 && $gpsInfo['lon'] != 0){

                return apiData()->send(200,'GPS有效');
            }

            return apiData()->send(2004,'GPS无效');
        }

        //1手机PGS一直有效,即便设备无效也要存储
        if($gpsInfo['lon'] != 0 && $gpsInfo['lat'] != 0) {

            $gpsInfo['lat'] = gps_to_gps($gpsInfo['lat']);
            $gpsInfo['lon'] = gps_to_gps($gpsInfo['lon']);
            $gpsInfo        = gps_to_bdgps($gpsInfo);

        }else{
            $gpsInfo['lat'] = 0;
            $gpsInfo['lon'] = 0;
        }

        //2存储GPS信息
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

        //3检查手机的GPS和设备的GPS的距离
        $msg    = $gpsInfo['lat'] ? "GPS无效":"偏差". gps_distance($lon,$lat,$gpsInfo['lon'],$gpsInfo['lat']);

        return apiData()->send(200, $msg);
    }

    /**
     * 返回最近的一个点
     * */
    public function return_back_last_point(Request $request)
    {
        $position   = $request->input('position');
        $gpsGroupId = $request->input('gpsGroupId');

        DB::table('football_court_point')->where('gps_group_id',$gpsGroupId)->where('position',$position)->delete();

        return apiData()->send();
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
        if(count($gps) > 4){

            $lat    = $gps[2];
            $lon    = $gps[4];

        }else{

            $lat    = "";
            $lon    = "";
        }


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

    /**
    * 获得用户的球场
    *
    */
    public function get_courts(Request $request){

        $userId     = $request->input('userId');
        $owner      = $request->input('owner');

        $courts     = CourtModel::get_courts($userId,$owner);

        return apiData()->add('courts',$courts)->send();
    }
}
