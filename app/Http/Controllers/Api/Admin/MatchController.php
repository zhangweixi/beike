<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/31
 * Time: 17:02
 */

namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Service\Court;
use App\Http\Controllers\Service\MatchCaculate;
use App\Models\Base\BaseMatchResultModel;
use App\Models\Base\BaseMatchSourceDataModel;
use App\Models\V1\CourtModel;
use App\Models\V1\MatchModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use DB;

const    PI = 3.1415926;

class MatchController extends Controller
{

    /**
     * 比赛列表
     * */
    public function matches(Request $request)
    {
        $matches = DB::table('match as a')
            ->leftJoin('users as b','b.id','=','a.user_id')
            ->select('a.*','b.nick_name')
            ->orderBy('a.match_id','desc')
            ->paginate(20);
        return apiData()->add('matches',$matches)->send();
    }


    //解析数据
    public function parse_data(Request $request){

        $matchId    = $request->input('matchId');

        //将之前的数据删除
        $dir        = matchdir($matchId);deldir($dir);

        $caculate   = new MatchCaculate();
        $caculate->jiexi_match($request);

        return apiData()->send();
    }


    /**
     * 运算比赛结果
     * */
    public function caculate_match(Request $request)
    {
        $matchId    = $request->input('matchId');
        $dir        = matchdir($matchId);deldir($dir);

        return (new MatchCaculate())->run_matlab($request);
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
                if($gpsType == 'center') {

                    //切割的中心点 二维数组
                    $centers = [];

                    foreach($gpsData as $gpsLine)
                    {
                        foreach($gpsLine as $gps)
                        {
                            array_push($centers,$gps);
                        }
                    }
                }
            }

            //将GPS转成百度GPS
            $baiduGps   = [];
            $baiduGps['A_D']    = $courtGps->A_D;
            $baiduGps['AF_DE']  = $courtGps->AF_DE;
            $baiduGps['F_E']    = $courtGps->F_E;
            $baiduGps['center'] = $centers;

            $newGps             = \GuzzleHttp\json_decode($courtInfo->boxs,true);

            $courtInfo->boxs    = $courtGps;

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


    /**
     * 比赛单项结果
     *
     * */
    public function get_match_single_result(Request $request)
    {
        $matchId    = $request->input('matchId');
        $type       = $request->input('type');

        $dir        = matchdir($matchId);
        $typeKey    = null;

        if($type == 'shoot'){

            $file   = $dir."result-shoot.txt";
            $data   = file_to_array($file);
            $latKey = 0;
            $lonKey = 1;

        }elseif($type == 'passLong'){

            $file   = $dir."result-pass.txt";
            $data   = file_to_array($file);
            $latKey = 4;
            $lonKey = 5;
            $typeKey = 1;
            $typeVal = 1;

        }elseif($type == 'passShort'){

            $file   = $dir."result-pass.txt";
            $data   = file_to_array($file);
            $latKey = 4;
            $lonKey = 5;
            $typeKey = 1;
            $typeVal = 2;

        }elseif ($type == 'touch'){

            $file   = $dir."result-pass.txt";
            $data   = file_to_array($file);
            $latKey = 4;
            $lonKey = 5;
            $typeKey = 1;
            $typeVal = 3;
        }

        $gps    = [];

        foreach($data as $d)
        {
            if($typeKey != null && $d[$typeKey]*1 != $typeVal){

                continue;
            }
            array_push($gps,['lon'=>$d[$lonKey],'lat'=>$d[$latKey]]);
        }

        return apiData()->add('gps',$gps)->send();
    }

    /**
     * 比赛文件
     * */
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
                $lineNum = get_file_line_num(public_path("uploads/match/{$matchId}/".$file));
                array_push($resultFiles,['name'=>$file,'url'=>url("uploads/match/{$matchId}")."/".$file,'lineNum'=>$lineNum]);
            }
        }

        return apiData()->add('matchFiles',$matchFiles)->add('resultFiles',$resultFiles)->send();
    }

    /**
     * 获取罗盘数据
     * */
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

                $lat        = $gpsInfo[0];
                $lon        = $gpsInfo[1];
                array_push($allGps,['lat'=>$lat,'lon'=>$lon]);
            }


            //$allGps = gps_to_bdgps($allGps);

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
        $matchId    = $request->input('matchId');
        $file       = matchdir($matchId)."gps-L.txt";

        if(!file_exists($file))
        {
            return apiData()->send(2002,"GPS文件不存在");
        }

        $gpsArr     = file_to_array($file);

        $gpsNewArr  = [];

        //转换成标准的GPS
        foreach($gpsArr as $key => &$gps){

            if($gps[0] * 1 == 0){

                continue;
            }
            if($gps[0]>100){

                array_push($gpsNewArr,[gps_to_gps($gps[0]),gps_to_gps($gps[1])]);
            }else{

                array_push($gpsNewArr,[$gps[0],$gps[1]]);
            }

        }

        $gpsArr     = $gpsNewArr;

        $gpsNum     = count($gpsArr);

        $points     = [];

        /*==============找出直线跑动的距离 begin=================*/

        //每隔5S分段一次
        $timeLength = 30;

        $lineAngles = [];
        $distances  = [];

        //求得每条线的斜率
        for($i=0; $i+$timeLength < $gpsNum; $i+=$timeLength){

            $begin  = $gpsArr[$i];
            $end    = $gpsArr[$i+$timeLength];

            //如果两个点的距离太小，则不取

            //$distance   = gps_distance($begin[0],$begin[1],$end[0],$end[1]);
            $distance   = gps_distance($begin[1],$begin[0],$end[1],$end[0]);
            array_push($distances,$distance);
            if($distance > 0.5 && $distance < 25)
            {
                array_push($points,['lat'=>$begin[0],'lon'=>$begin[1]],['lat'=>$end[0],'lon'=>$end[1]]);

                if($end[1]-$begin[1] == 0){

                    $angle = 0;

                }else{

                    $slope = ($end[0]-$begin[0]) / ($end[1]-$begin[1]);
                    $angle = pi_to_angle(atan($slope));
                }
                array_push($lineAngles,$angle);
            }
        }


        /*==============寻找球场的方向 end =================*/
        //goto  end;
        //return $lineAngles;

        /*======================求球场斜率 begin =====================*/

        //1.把所有的方向分成6个区间0-30-60-90-120-150-180

        $angleRanges    = [];

        for($i=-90;$i<90;$i+=5){

            array_push($angleRanges,["start"=>$i,"end"=>$i+5,'num'=>0,'data'=>[]]);
        }

        $angleRangesNum = count($angleRanges);

        //查询在每个区间的方向的累积量  最小区间
        foreach($lineAngles as $angle){

            $index  = (int)(($angle + 90) / 5);

            if($index >= $angleRangesNum){

                $index    = $index - 1;
            }

            array_push($angleRanges[$index]['data'],$angle);
            $angleRanges[$index]['num']++;
        }

        //将首部的八个区间移动到尾部去，形成一个闭环
        for($i=0;$i<=8;$i++){

            array_push($angleRanges,$angleRanges[$i]);
        }



        //将小区间组合成大区间 每10个小区间构成一个大区间
        $newAngleRanges     = [];
        $angleRangesNum     = count($angleRanges);
        $angleRangeNumArr   = [];

        for($i=0;$i<=$angleRangesNum - 18 ;$i++){

            $bigAngle   = [
                'data'  => [],
                'start' => $angleRanges[$i]['start'],
                'end'   => $angleRanges[$i+17]['end'],
                'num'   => 0
            ];

            for($j=$i;$j<$i+18;$j++){

                $bigAngle['num']    +=  $angleRanges[$j]['num'];
                $bigAngle['data']   =   array_merge($bigAngle['data'],$angleRanges[$j]['data']);
            }

            array_push($newAngleRanges,$bigAngle);
            array_push($angleRangeNumArr,$bigAngle['num']);
        }

        $angleRanges    = $newAngleRanges;


        //筛选两份最大的，之所以选两份最大的，是为了求得交集的地方的数量
        array_multisort($angleRangeNumArr,SORT_DESC);

        $maxNumber  = $angleRangeNumArr[0];

        //过滤到数量比较少的区间
        $maxAngleRange  = [];//最大范围角度
        foreach($angleRanges as $key => $stage){

            if($stage['num'] == $maxNumber){

                array_push($maxAngleRange,$stage);
            }
        }

        //取各自的平均值

        $sumAngle   = 0;
        $numAngle   = 0;

        foreach($maxAngleRange as $angle){

            $sumAngle   += array_sum($angle['data']);
            $numAngle   += $angle['num'];
        }

        $courtAngle   = $sumAngle/$numAngle;



        $courtSlope = tan($courtAngle/180*PI);

        //return $courtSlope;
        /*====================求球场斜率 end =================*/



        /*====================找上下方 最大点 begin =================*/
        //设定一个中心
        $lats   = [];
        $lons   = [];
        $num    = 0;

        for($i = 0;$i< $gpsNum ;$i=$i+10)
        {
            array_push($lats,$gpsArr[$i][0]);
            array_push($lons,$gpsArr[$i][1]);
            $num++;
        }

        $centerLat      = array_sum($lats)/$num;
        $centerLon      = array_sum($lons)/$num;
        $courtCenter    = ['lon'=>$centerLon,'lat'=>$centerLat];

        //球场方向直线偏移量
        $courtB = $courtCenter['lat'] - $courtSlope * $courtCenter['lon'];  //y=k*x+b => b = y -k*x


        //3.1把GPS分成两个方向，中心的上方和下方

        $maxUpDis   = 0;
        $maxDownDis = 0;
        $pointUp    = null;   //球场最上的点
        $pointDown  = null;   //球场最下的点

        $sinAngle       = sin(angle_to_pi(90-abs($courtAngle)));

        //经过球心把球场分成上下两部分 但是有可能有一方没有数据
        foreach($gpsArr as $gps){

            //如果当前点的x值对呀的Y小于直线上点的Y，则在下方，否则在上方
            $currrentLat    = $courtSlope*$gps[1] + $courtB;                    //当前点的x对应的线的Y值
            $pointToLineDis = abs(($currrentLat - $gps[0])) / $sinAngle;       //点到直线的距离
            $direction      = $currrentLat > $gps[0] ?  'down':'up' ;

            if($direction == "up" && $maxUpDis < $pointToLineDis){

                $maxUpDis   = $pointToLineDis;
                $pointUp    = $gps;

            }elseif($direction == "down" && $maxDownDis < $pointToLineDis){

                $maxDownDis = $pointToLineDis;
                $pointDown  = $gps;
            }
        }
        /*========================找上下方 最大点 end =====================*/



        /*========================找左右方 最大点 begin ===================*/

        //找到最左最右的点
        $verticalSlope  = tan(angle_to_pi($courtAngle+90)); //球场垂直斜率
        $verticalB      = $centerLat - $verticalSlope*$centerLon;
        $sinAngle       = sin(angle_to_pi(90-abs($courtAngle)));
        $pointLeft      = null;
        $pointRight     = null;
        $maxLeft        = 0;
        $maxRight       = 0;

        foreach($gpsArr as $gps){

            //如果当前点的x值对呀的Y小于直线上点的Y，则在下方，否则在上方
            $currentY   = $verticalSlope*$gps[1] + $verticalB;              //当前点的x对应的线的Y值

            $pointToLineDis= abs(($currentY - $gps[0])) / $sinAngle;        //点到直线的距离


            if(($verticalSlope > 0 && $currentY < $gps[0]) || ($verticalSlope < 0 && $currentY > $gps[0])){

                $direction  = "left";

            } else{

                $direction = "right";
            }

            if($direction == "right"){ //位于右边

                if($pointToLineDis > $maxRight){

                    $maxRight      = $pointToLineDis;
                    $pointRight    = $gps;
                }

            }else{ //位于中心下之下

                if($pointToLineDis > $maxLeft){

                    $maxLeft      = $pointToLineDis;
                    $pointLeft    = $gps;
                }
            }
        }

        /*========================找左右方 最大点 end ===================*/




        //==============求得球场边沿的函数值===============
        $upBorderB      = $pointUp[0]       - $courtSlope   * $pointUp[1];
        $downBorderB    = $pointDown[0]     - $courtSlope   * $pointDown[1];
        $rightBorderB   = $pointRight[0]    - $verticalSlope* $pointRight[1];
        $leftBorderB    = $pointLeft[0]     - $verticalSlope* $pointLeft[1];


        //==============求得目前球场的4个顶点=================
        //k1 * x + b1 = k2*x + b2
        $leftTopPoint       = self::get_jiao_dian($verticalSlope,$leftBorderB,$courtSlope,$upBorderB);  //左上点
        $leftBottomPoint    = self::get_jiao_dian($verticalSlope,$leftBorderB,$courtSlope,$downBorderB);//左下点
        $rightTopPoint      = self::get_jiao_dian($verticalSlope,$rightBorderB,$courtSlope,$upBorderB); //右上点
        $rightBottompoint   = self::get_jiao_dian($verticalSlope,$rightBorderB,$courtSlope,$downBorderB);//右下点


        //4个顶点
        $fourTopPoint = [
            "left_top"       => ["lat"=>$leftTopPoint['y'],     "lon"=>$leftTopPoint['x']],
            "left_bottom"    => ["lat"=>$leftBottomPoint['y'],  "lon"=>$leftBottomPoint['x']],
            "right_top"      => ["lat"=>$rightTopPoint['y'],    "lon"=>$rightTopPoint['x']],
            "right_bottom"   => ["lat"=>$rightBottompoint['y'], "lon"=>$rightBottompoint['x']],
        ];

        $borderArr       = [
            "left_top_right_top"        => $upBorderB,
            "right_top_left_top"        => $upBorderB,
            "left_top_left_bottom"      => $leftBorderB,
            "left_bottom_left_top"      => $leftBorderB,
            "right_top_right_bottom"    => $rightBorderB,
            "right_bottom_right_top"    => $rightBorderB,
            "right_bottom_left_bottom"  => $downBorderB,
            "left_bottom_right_bottom"  => $downBorderB
        ];

        $positionArr    = [
            "left"  => "right",
            "right" => "left",
            "top"   => "bottom",
            "bottom"=> "top"
        ];


        //确定球场的起点-即图上的A-B-C-D-E-F
        //把前面的点用来设置为开始比赛的起点


        //=======寻找A点==================

        //获取前面50个点的坐标来设置为开始点
        $latSum     = 0;
        $lonSum     = 0;

        for($i=0;$i<50;$i++)
        {
            $latSum += $gpsArr[$i][0];
            $lonSum += $gpsArr[$i][1];
        }

        $beginLat = $latSum / 50;
        $beginLon = $lonSum / 50;

        $beginTopPoint  = null;
        $beginDis       = 10000000;


        foreach ($fourTopPoint as $pname => $point){

            $dis    = gps_distance($beginLon,$beginLat,$point['lon'],$point['lat']);

            if($dis < $beginDis){

                $beginDis       = $dis;
                $beginTopPoint  = $pname;
            }
        }


        /*
        * A           F              A1  A1
        *
        * B
        *
        * D
        *
        * D           E              D1
        * */

        //按顺序获得球场4个点
        $direction = explode("_",$beginTopPoint);


        $pa  = $fourTopPoint[$direction[0]."_".$direction[1]];

        //知道了pa,但是pb可能是旁边的两个点，究竟哪一个点才是，要根据他们的斜率
        $pdSameLeft     = $fourTopPoint[$direction[0]."_".$positionArr[$direction[1]]]; //同样的左右方 如：左上 左下
        $pdSameDown     = $fourTopPoint[$positionArr[$direction[0]]."_".$direction[1]]; //同样的上下方 如：左上 右下

        $slopeDown      = ($pa['lat'] - $pdSameDown['lat'])    / ($pa['lon'] - $pdSameDown['lon']);
        $slopeLeft      = ($pa['lat'] - $pdSameLeft['lat'])    / ($pa['lon'] - $pdSameLeft['lon']);

        if(abs($slopeLeft - $verticalSlope) < abs($slopeDown - $verticalSlope)){ //a d 在相同的左方


            $pak  = $direction[0]."_".$direction[1];
            $pdk  = $direction[0]."_".$positionArr[$direction[1]];
            $pa1k = $positionArr[$direction[0]]."_".$direction[1];
            $pd1k = $positionArr[$direction[0]]."_".$positionArr[$direction[1]];

        }else{ //相同的下方
            $pak  = $direction[0]."_".$direction[1];
            $pdk  = $positionArr[$direction[0]]."_".$direction[1];
            $pa1k = $direction[0]."_".$positionArr[$direction[1]];
            $pd1k = $positionArr[$direction[0]]."_".$positionArr[$direction[1]];
        }

        $pa   = $fourTopPoint[$pak];
        $pd   = $fourTopPoint[$pdk];
        $pa1  = $fourTopPoint[$pa1k];
        $pd1  = $fourTopPoint[$pd1k];


        //获得球场比例
        $width  = gps_distance($pa['lon'],$pa['lat'],$pd['lon'],$pd['lat']);
        $length = gps_distance($pa['lon'],$pa['lat'],$pa1['lon'],$pa1['lat']);
        $courtScale = $length / $width; //球场比例

        //判断球场的长宽比例是否达到一个足球场的比例

        //符合一个足球场的比例 1.3  2.2
        if($courtScale < 1.3){ //长度不够，扩长度

            //X移动步频 扩展的方向应该是起点的对立方向
            $xStep          = ($pa1['lon'] - $pa['lon']) / abs($pa1['lon'] - $pa['lon']) * 0.000001;
            $borderSelfB    = $borderArr[$pak."_".$pa1k];
            $borderItB      = $borderArr[$pdk."_".$pd1k];

            do{

                $pa1['lon']      = $pa1['lon'] + $xStep;

                $pa1['lat']      = $courtSlope * $pa1['lon'] + $borderSelfB;      //这里的b也要根据方向来判断

                $pd1['lon']      = $pd1['lon'] + $xStep;

                $pd1['lat']      = $courtSlope * $pd1['lon'] + $borderItB;

                $length          = gps_distance($pa['lon'],$pa['lat'],$pa1['lon'],$pa1['lat']);

                $courtScale      = $length / $width;

            }while($courtScale <=2);

        }elseif($courtScale > 2){ //宽度不够，扩宽度


            $xStep          = ($pd['lon'] - $pa['lon']) / abs($pd['lon'] - $pa['lon']) * 0.000001;
            $borderSelfB    = $borderArr[$pak."_".$pdk];
            $borderItB      = $borderArr[$pa1k."_".$pd1k];

            do{

                $pd['lon']  = $pd['lon'] + $xStep;
                $pd['lat']  = $verticalSlope * $pd['lon'] + $borderSelfB;

                $pd1['lon'] = $pd1['lon'] + $xStep;
                $pd1['lat'] = $verticalSlope * $pd1['lon'] + $borderItB;

                $width      = gps_distance($pa['lon'],$pa['lat'],$pd['lon'],$pd['lat']);

                $courtScale             = $length / $width;

                //mylogger($courtScale);

            }while($courtScale > 0.5);

        }

        $courtTopPoints = [
            'p_a'   => $pa,
            'p_d'   => $pd,
            'p_a1'  => $pa1,
            'p_d1'  => $pd1,
        ];

        foreach ($courtTopPoints as &$point){

            $point = implode(",",$point);
        }

        //更新球场
        DB::table("football_court")->where('court_id',311)->update($courtTopPoints);

        //return $courtTopPoints;
        //$points = $courtTopPoints;

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



        end:

        $points = gps_to_bdgps($points);

        return apiData()->add('points',$points)->send();
    }


    /**
     * 创建新的二维坐标
     *
     * */
    public function build_new_court_coordinate(Request $request){

        $matchId        = $request->input('matchId');
        $matchInfo      = MatchModel::find($matchId);
        $courtDataObj   = DB::table('football_court')->select("p_a","p_d","p_a1","p_d1")->where('court_id',$matchInfo->court_id)->first();
        $courtWidth     = $request->input('courtWidth',400);
        $courtHeight    = $request->input('courtHeight',200);

        $courtData      = [];
        foreach($courtDataObj as $key => $gps)
        {
            $gps             = explode(",",$gps);
            $courtData[$key] = ['x'=>$gps[1],'y'=>$gps[0]];
        }

        $gpsList    = file_to_array(matchdir($matchId)."result-pass.txt");
        $points     = [];

        foreach($gpsList as $key=> $gps)
        {
            if($gps[0] * 1 == 0){
                continue;
            }
            array_push($points,['x'=>$gps[5],'y'=>$gps[4]]);
        }

        $points = Court::create_gps_map($courtData['p_a'],$courtData['p_a1'],$courtData['p_d'],$courtData['p_d1'],$points);

        //$points = Court::create_gps_map($courtData['p_a'],$courtData['p_a1'],$courtData['p_d'],$courtData['p_d1'],$points,$courtWidth,$courtHeight);


        return apiData()->add('points',$points)->send();
    }

    /**
     *
     * 平移和缩放数据
     *
     * */
    static function move_and_scroll_point($originX,$originY,$x,$y,$perx,$pery){


        $x = ($x - $originX) * $perx;

        $y = ($y - $originY) * $pery;


        return ['x'=>$x,'y'=>$y];
    }

    /**
     * 获取两条直线的交点
     * @param $k1 float
     * @param $b1 float
     * @param $k2 float
     * @param $b2 float
     * @return array
     * **/
    static function get_jiao_dian($k1,$b1,$k2,$b2){

        $x = ($b2 - $b1) / ($k1 - $k2);

        $y = $k1 * $x + $b1;

        return ["x"=>$x,"y"=>$y];
    }
}