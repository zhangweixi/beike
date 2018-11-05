<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/31
 * Time: 17:02
 */

namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\Controller;
use App\Models\Base\BaseMatchResultModel;
use App\Models\Base\BaseMatchSourceDataModel;
use App\Models\V1\CourtModel;
use App\Models\V1\MatchModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use DB;

class MatchController extends Controller
{

    public function matches(Request $request)
    {
        $matches = DB::table('match as a')
            ->leftJoin('users as b','b.id','=','a.user_id')
            ->select('a.*','b.nick_name')
            ->orderBy('a.match_id','desc')
            ->paginate(20);
        return apiData()->add('matches',$matches)->send();
    }



    /**
     * 球场信息
     * */
    public function match_court(Request $request)
    {

        $matchId    = $request->input('matchId');
        $matchInfo  = MatchModel::find($matchId);

        if($matchInfo->court_id == 0)
        {
            return apiData()->send(2001,"本次比赛没有测量球场");
        }


        $courtInfo  = CourtModel::find($matchInfo->court_id);

        $courtGps   = \GuzzleHttp\json_decode($courtInfo->boxs);

        //检查是否有百度地图
        if(!isset($courtGps->baiduGps))
        {

            foreach($courtGps as $gpsType => $gpsData)
            {
                if($gpsType != 'center') {

                    foreach($gpsData as $gps)
                    {
                        $gps->lat   = gps_to_gps($gps->lat);
                        $gps->lon   = gps_to_gps($gps->lon);
                    }

                }else{

                    //切割的中心点 二维数组
                    $centers = [];

                    foreach($gpsData as $gpsLine)
                    {
                        foreach($gpsLine as $gps)
                        {
                            $gps->lat   = gps_to_gps($gps->lat);
                            $gps->lon   = gps_to_gps($gps->lon);
                            array_push($centers,$gps);
                        }
                    }
                }
            }

            //将GPS转成百度GPS
            $baiduGps   = [];
            $baiduGps['A_D']    = gps_to_bdgps($courtGps->A_D);
            $baiduGps['AF_DE']  = gps_to_bdgps($courtGps->AF_DE);
            $baiduGps['F_E']    = gps_to_bdgps($courtGps->F_E);
            $baiduGps['center'] = gps_to_bdgps($centers);



            $newGps             = \GuzzleHttp\json_decode($courtInfo->boxs,true);
            $newGps['baiduGps'] = $baiduGps;

            $courtGps->baiduGps = $baiduGps;
            $courtInfo->boxs    = $courtGps;
            
            //更改数据
            //CourtModel::where('court_id',$matchInfo->court_id)->update(['boxs'=>\GuzzleHttp\json_encode($newGps)]);
        }

        return apiData()->add('court',$courtInfo)->send();

    }

    /**
     * 比赛结果
     * */
    public function match_result(Request $request){

        $matchId    = $request->input('matchId');

        $matchResult = BaseMatchResultModel::find($matchId);

        return apiData()->add('matchResult',$matchResult)->send();
    }


    public function match_files(Request $request){

        $matchId    = $request->input('matchId');

        //原始文件
        $matchFiles = BaseMatchSourceDataModel::where('match_id',$matchId)->orderBy('foot')->orderBy('type')->orderBy('match_source_id','desc')->get();

        //结果文件

        $dirfile    = public_path("uploads/match/".$matchId);

        $dirfile    = file_exists($dirfile) ? scandir($dirfile) : [];


        $resultFiles= [];

        foreach($dirfile as $file)
        {
            if(preg_match("/^\w/",$file))
            {
                array_push($resultFiles,['name'=>$file,'url'=>url("uploads/match/{$matchId}")."/".$file]);
            }
        }

        return apiData()->add('matchFiles',$matchFiles)->add('resultFiles',$resultFiles)->send();
    }


    public function get_compass_data(Request $request)
    {
        $url        = $request->input('file');
        $data       = file_to_array($url);
        return apiData()->add('compass',$data)->send();
    }


    public function get_match_run_data(Request $request)
    {
        $matchId    = $request->input('matchId');
        $matchInfo  = MatchModel::find($matchId);
        $courtInfo  = CourtModel::find($matchInfo->court_id);

        $courtData  = [];
        if($courtInfo)
        {
            $keys = ['p_a','p_b','p_c','p_d','p_e','p_f','p_a1','p_d1'];
            foreach($keys as $key)
            {
                array_push($courtData,explode(",",$courtInfo->$key));
            }
        }

        $gpsList    = file_to_array(public_path("uploads/match/".$matchId."/gps-L.txt"));

        return apiData()->add('courtInfo',$courtData)->add('gpsList',$gpsList)->send();

    }
    /**
     * 比赛的GPS
     * */
    public function match_gps(Request $request)
    {
        $matchId    = $request->input('matchId');

        $baiduGpsFile    = "match/".$matchId."/bd-gps.json";

        if(!Storage::disk('web')->has($baiduGpsFile)) {

            $allGps     = [];

            $gpsFile    =public_path('uploads/match/'.$matchId."/gps-L.txt");

            if(!file_exists($gpsFile))
            {
                $gpsFile    =public_path('uploads/match/'.$matchId."/gps-R.txt");
            }

            if(!file_exists($gpsFile))
            {
                return apiData()->send(2001,"没有PGS数据");
            }


            $gpsList    = file($gpsFile);
            foreach($gpsList as $gps)
            {
                $gpsInfo    = explode(" ",trim($gps,"\n"));

                if($gpsInfo[0] == 0 || $gpsInfo[1] == 0)
                {
                    continue;
                }

                $lat        = gps_to_gps($gpsInfo[0]);
                $lon        = gps_to_gps($gpsInfo[1]);
                array_push($allGps,['lat'=>$lat,'lon'=>$lon]);
            }


            $allGps = gps_to_bdgps($allGps);

            Storage::disk('web')->put($baiduGpsFile,\GuzzleHttp\json_encode($allGps));
        }

        $points = Storage::disk('web')->get($baiduGpsFile);
        $points = \GuzzleHttp\json_decode($points);

        return apiData()->set_data('points',$points)->send();
    }


    public function update_match(Request $request){

        $matchId    = $request->input('matchId');
        $allData    = $request->all();
        $validColum = ["admin_remark"];
        $data       = [];
        foreach($allData as $key => $v)
        {
            if(in_array($key,$validColum))
            {
                $data[$key] = $v;
            }
        }

        MatchModel::where("match_id",$matchId)->update($data);


        return apiData()->send();

    }


    /**
     * 获得虚拟球场
     * */
    public function get_visual_match_court(Request $request)
    {

        mylogger($request->all());
        return "ok";

        $matchId    = $request->input('matchId');
        $file       = matchdir($matchId)."gps-L.txt";

        if(!file_exists($file))
        {
            return apiData()->send(2002,"GPS文件不存在");
        }

        $gpsArr     = file_to_array($file);

        $minLat     = [0,100000];
        $maxLat     = [0,0];
        $minLon     = [100000,0];
        $maxLon     = [0,0];

        foreach($gpsArr as $gps)
        {
            if($gps[0] == 0 || $gps[1] == 0)
            {
                continue;
            }

            $minLon = $gps[0] < $minLon[0] ? $gps : $minLon;
            $maxLon = $gps[0] > $maxLon[0] ? $gps : $maxLon;

            $minLat = $gps[1] < $minLat[1] ? $gps : $minLat;
            $maxLat = $gps[1] > $maxLat[1] ? $gps : $maxLat;
        }

        $points     = [
            $minLat,
            $maxLat,
            $minLon,
            $maxLon
        ];



        foreach($points as $key => $point)
        {

            $points[$key]   = ['lat'   => gps_to_gps($point[0]), 'lon'   => gps_to_gps($point[1])];

        }


        $p1 = (object)["x"=>$points[0]['lon'],"y"=>$points[0]['lat']];
        $p2 = (object)["x"=>$points[1]['lon'],"y"=>$points[1]['lat']];
        $p3 = (object)["x"=>$points[3]['lon'],"y"=>$points[3]['lat']];

        $params = get_cycle_params_by_three_point($p1,$p2,$p3);
        $x = $params[0];
        $y = $params[1];
        $radius = $params[2];

        array_push($points,["lon"=>$params[0],'lat'=>$params[1]]);
        array_push($points,["lat"=>$params[1]+$params[2],"lon"=>$params[0]]);
        array_push($points,["lat"=>$params[1]-$params[2],"lon"=>$params[0]]);

        array_push($points,["lon"=>$params[0]+$params[2],"lat"=>$params[1]]);
        array_push($points,["lon"=>$params[0]-$params[2],"lat"=>$params[1]]);

        //y = sqrt(r^2-(x-a)^2)+b
        $a = $x;
        $b = $y;

        $x = $a-$radius;

        $x = $x+0.000001;

        while($x < $a+$radius){

            $y = sqrt($radius*$radius - ($x-$a)*($x-$a))+$b;
            array_push($points,['lat'=>$y,'lon'=>$x]);

            $y = $b-sqrt($radius*$radius - ($x-$a)*($x-$a));
            array_push($points,['lat'=>$y,'lon'=>$x]);

            $x = $x+0.00001;
        }

        if(0){

            $p1 = $points[0];
            $p2 = $points[1];
            $params1 = get_fun_params_by_two_point([$p1['lon'],$p1['lat']],[$p2['lon'],$p2['lat']]);

            $k1  = $params1[0];
            $b1  = $params1[1];



            $p3 = $points[2];
            $p4 = $points[3];
            $params2 = get_fun_params_by_two_point([$p3['lon'],$p3['lat']],[$p4['lon'],$p4['lat']]);
            $k2  = $params2[0];
            $b2  = $params2[1];

            //求交点

            $x = bcdiv(bcsub($b2,$b1),bcsub($k1,$k2));
            $y = bcadd(bcmul($k1,$x),$b1);
            array_push($points,['lat'=>abs($y),'lon'=>abs($x)]);


            //return [$k1,$k2];

            //两条线的夹角公式 arctan( (K2-K1) / (1 + K2*k1) ) * 180 / 3.14

            $PI = 3.1415926;
            $angle1 = atan($k1)*180/$PI;

            $angle2 = atan($k2)*180/$PI;



            //return [$angle1,$angle2];

            $angleMid = ($angle2 + $angle1) / 2;
            //$angleMid = 45;
            $angle = 90 + $angleMid;

            $k3 = tan($angle * $PI / 180);

            $b3 = $y-$k3 * $x ;//b=y-k*x

            //return $k3;
        }


        if(0){

            $i=-20;

            do{

                $lon = $points[0]['lon']+0.00005*$i;
               // array_push($points,['lat'=>$k1*$lon-$b1,'lon'=>$lon]);

                $i++;

            }while($i<20);


            $i=-20;
            do{
                $lon = $points[2]['lon']+0.00005*$i;
               // array_push($points,['lat'=>$k2*$lon-$b2,'lon'=>$lon]);
                $i++;

            }while($i<20);


            $i=-20;
            do{
                $p = $points[4];
                //array_push($points,['lat'=>$p['lat']+0.00005*$i,'lon'=>$p['lon']]);
                $i++;
            }while($i<20);



            $i=-20;
            do{

                $lon = $points[4]['lon']+0.00005*$i;
               // array_push($points,['lat'=>$k3*$lon-$b3,'lon'=>$lon]);
                $i++;
            }while($i<20);

        }

        $points = gps_to_bdgps($points);



        return apiData()->add('points',$points)->send();
    }
}