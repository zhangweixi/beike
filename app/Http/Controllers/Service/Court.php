<?php
namespace App\Http\Controllers\Service;

use App\Common\GPS;
use App\Http\Controllers\Service\GPSPoint;
use App\Models\V1\CourtModel;
use DB;

/*
  足球场结构点阵结构

  A---------------------F--------------------AF
  |        ·      ·     |  ·        ·        |
  |·····················|····················|
  B        ·      ·        ·        ·        |
  |·····················|····················|
  |        ·      ·        ·        ·        |
  C·····················|····················|
  |        ·      ·        ·        ·        |
  |·····················|····················|
  |        ·      ·        ·        ·        |
  D---------------------E--------------------DE

  */


/**
 * @property \App\Http\Controllers\Service\GPSPoint $A
 * @property \App\Http\Controllers\Service\GPSPoint $B
 * @property \App\Http\Controllers\Service\GPSPoint $C
 * @property \App\Http\Controllers\Service\GPSPoint $D
 * @property \App\Http\Controllers\Service\GPSPoint $E
 * @property \App\Http\Controllers\Service\GPSPoint $F
 * @property \App\Http\Controllers\Service\GPSPoint $AF
 * @property \App\Http\Controllers\Service\GPSPoint $DE
 * */
class Court{

    private $A,$B,$C,$D,$E,$F,$AF,$DE;

    //private $lonNum = 20;
    //private $latNum = 32;

    private $lonNum = 10;
    private $latNum = 20;

    public function __construct()
    {
        bcscale (10);
    }

    /**
     * 计算球场
     * @param $A GPSPoint
     * @param $B GPSPoint
     * @param $C GPSPoint
     * @param $D GPSPoint
     * @param $E GPSPoint
     * @param $F GPSPoint
     * @return Array
     * */
    public function calculate_court($A,$B,$C,$D,$E,$F)
    {
        $this->A = $A;
        $this->B = $B;
        $this->C = $C;
        $this->D = $D;
        $this->E = $E;
        $this->F = $F;

        $AFlat = bcadd($F->lat,bcsub($F->lat,$A->lat));
        $AFlon = bcadd($F->lon,bcsub($F->lon,$A->lon));

        $DElat = bcadd($E->lat,bcsub($E->lat,$D->lat));
        $DElon = bcadd($E->lon,bcsub($E->lon,$D->lon));


        $this->AF = new GPSPoint();
        $this->AF->lat = $AFlat;
        $this->AF->lon = $AFlon;

        $this->DE = new GPSPoint();
        $this->DE->lat = $DElat;
        $this->DE->lon = $DElon;

        return $this->cut_court();

    }



    /**
     * 切割球场
     * */
    private function cut_court()
    {
        //切割A->D
        $courtMap           = [];
        //$A_D_Points         = $this->cut_line($this->D,$this->A,$this->lonNum);
        $A_D_Points         = $this->cut_line($this->A,$this->D,$this->lonNum);

        $courtMap['A_D']    = $A_D_Points;
        $A_D_Points         = self::array_cycle($A_D_Points,2);

        //切割 AF-DE
        #$AF_DE_Points       = $this->cut_line($this->DE,$this->AF,$this->lonNum);
        $AF_DE_Points       = $this->cut_line($this->AF,$this->DE,$this->lonNum);
        $courtMap['AF_DE']  = $AF_DE_Points;

        $AF_DE_Points       = self::array_cycle($AF_DE_Points,2);

        $courtMap['F_E']    = [$this->F,$this->E];


        //左右两边连线再切割 只需要偶数的点
        $length     = count($A_D_Points);
        $boxPoints  = [];

        for($i=0;$i<$length;$i++)
        {
            $pointBegin  = $A_D_Points[$i];
            $pointEnd    = $AF_DE_Points[$i];

            //切割横线
            $linePoints     = $this->cut_line($pointBegin,$pointEnd,$this->latNum);

            $linePoints     = self::array_cycle($linePoints,2);

            array_push($boxPoints,$linePoints);

        }

        $courtMap['center']     = $boxPoints;

        return $courtMap;
    }

    /**
     * 判断球场是否是顺时针方向走动
     * @param $A GPSPoint
     * @param $D GPSPoint
     * @param $E GPSPoint
     * @return boolean
     * */
    static function judge_court_is_clockwise($A,$D,$E){

        //以下是逆时针方向的判断
        if(    ($A->lat <  $D->lat && $D->lon < $E->lon)
            || ($A->lat >  $D->lat && $D->lon > $E->lon)
            || ($A->lat == $D->lat && $A->lon > $D->lon && $D->lat < $E->lat)
            || ($A->lat == $D->lat && $A->lon < $D->lon && $D->lat > $D->lat))
        {
            return false;
        }

        return true;
    }

    /**
     * 间隔获得数组内容
     * @param $arr array 数组
     * @param $cycle integer 下标
     * @return array
     * */
    static function array_cycle($arr, $cycle)
    {
        if($cycle<2)
        {
            return false;
        }

        $tempArr    = [];
        foreach($arr as $key => $v)
        {
            if(($key+1)%$cycle == 0)
            {
                array_push($tempArr,$v);
            }
        }
        return $tempArr;
    }

    /**
     * @param $begin GPSPoint 开始点
     * @param $end GPSPoint 结束点
     * @param $num Integer 要切割的段数
     * @return Array
     * */
    public function cut_line(GPSPoint $begin,GPSPoint $end, $num)
    {
        $num = $num * 2;

        $avglat = bcdiv(bcsub($end->lat,$begin->lat),$num);
        $avglon = bcdiv(bcsub($end->lon,$begin->lon),$num);

        $points = [];

        for($i=0;$i<=$num;$i++)
        {
            $p = new GPSPoint();
            $p->lat = bcadd($begin->lat,bcmul($avglat,$i));
            $p->lon = bcadd($begin->lon,bcmul($avglon,$i));

            array_push($points,$p);
        }

        return $points;
    }


    public $maxLat  = 0;
    public $minLat  = 0;
    public $maxLon  = 0;
    public $minLon  = 0;


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

        if($latNum > 0)
        {
            $this->set_lat_num($latNum);
        }

        if($lonNum > 0)
        {
            $this->set_lon_num($lonNum);
        }

        $boxPoints  = $this->calculate_court($A,$B,$C,$D,$E,$F);

        if($courtInfo->is_clockwise == 1){

            $boxPoints['center']  = array_reverse($boxPoints['center']);
        }

        return $boxPoints;
    }



    /**
     *
     * 创建球场GPS配置文件
     * @param $courtId integer 球场ID
     * @return string
     * */
    public function create_court_gps_angle_config($courtId)
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

        foreach($configBoxs as $y => $line)
        {
            foreach($line as $x => $box)
            {
                $lat    = $points[$x][$y]->lat;
                $lon    = $points[$x][$y]->lon;
                $big    = $box->type == "D" ? 1 : 0;
                $small  = $box->type == 'X' ? 1 : 0;

                $config .= $lat . " ".$lon." ".$big." ".$small." ".$box->angle."\n";
            }
        }


        $b = explode(",",$courtInfo->p_b);
        $c = explode(",",$courtInfo->p_c);
        $b1= explode(",",$courtInfo->p_b1);
        $c1= explode(",",$courtInfo->p_c1);

        $config .= implode(" ",$b)." ".implode(" ",$c)." 0\n";
        $config .= implode(" ",$b1)." ".implode(" ",$c1)." 0";

        file_put_contents($courtAngleConfiFile,$config);

        return $filepath;
    }



    /**
     * 创建球场模型输入文件
     * @param $courtId integer 球场ID
     * @return string 文件路径
     * */
    public static function create_court_model_input_file($courtId)
    {
        $useMobileGps = true;

        $gpsGroupId = CourtModel::where('court_id',$courtId)->value('gps_group_id');
        $db         = DB::table('football_court_point');

        if($useMobileGps == true){

            $db->select('position','mobile_lat as lat','mobile_lon as lon');

        }else{

            $db->select('position','device_lat as lat','device_lon as lon');
        }

        $points     = $db->where('gps_group_id',$gpsGroupId)->get()->toArray();

        foreach($points as $key => $p)
        {
            $points[$key]   = implode(" ",object_to_array($p));
        }
        $points     = implode("\n",$points);
        $dir        = public_path("uploads/court-config/{$courtId}");
      
        $file       = $dir."/border-src.txt";
        mk_dir($dir);
        file_put_contents($file,$points);
        return $file;
    }


    /*
    * 设置维度数量
    *
    * */
    public function set_lat_num($latNum)
    {
        $this->latNum = $latNum;
    }


    /*
     * 设置经度数量
     * */
    public function set_lon_num($lonNum)
    {
        $this->lonNum = $lonNum;
    }



    /**
     * 获得虚拟球场
     * @param $matchId int 球场ID
     * @return mixed
     * */
    static function create_visual_match_court($matchId,$courtId)
    {
        $file       = matchdir($matchId)."gps-L.txt";
        $gpsArr     = file_to_array($file);

        $gpsNewArr  = [];

        //转换成标准的GPS
        foreach($gpsArr as $key => $gps)
        {
            if($gps[0] * 1 == 0){

                continue;
            }
            array_push($gpsNewArr,[$gps[0],$gps[1]]);
        }

        $gpsArr     = $gpsNewArr;
        $gpsNum     = count($gpsArr);


        /*==============找出直线跑动的距离 begin=================*/
        $courtAngle = self::get_visual_court_angle($gpsArr);

        $courtSlope = tan(angle_to_pi($courtAngle));
        /*====================求球场斜率 end =================*/


        /*=======找到球场中的一个点来确定两条直线======*/

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

        /*====================找上下方 最大点 begin =================*/

        $upDownPoint    = self::get_max_dis_point($courtSlope,$courtB,$gpsArr);
        $pointUp        = $upDownPoint['up'];
        $pointDown      = $upDownPoint['down'];

        /*========================找上下方 最大点 end =====================*/




        /*========================找左右方 最大点 begin ===================*/

        //找到最左最右的点
        $verticalSlope  = tan(angle_to_pi($courtAngle+90));         //球场垂直斜率
        $verticalB      = $centerLat - $verticalSlope*$centerLon;
        $leftRightPoint = self::get_max_dis_point($verticalSlope,$verticalB,$gpsArr);
        $pointLeft      = $leftRightPoint['up'];
        $pointRight     = $leftRightPoint['down'];
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
        * A           F              A1
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

            }while($courtScale <=1.7);

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

            }while($courtScale > 1.7);
        }

        //计算球门的位置

        $blat   = $pa['lat'] + (($pd['lat'] - $pa['lat']) / 10 * 3);
        $blon   = $pa['lon'] + (($pd['lon'] - $pa['lon']) / 10 * 3);

        $clat   = $pa['lat'] + (($pd['lat'] - $pa['lat']) / 10 * 7);
        $clon   = $pa['lon'] + (($pd['lon'] - $pa['lon']) / 10 * 7);

        $b1lat  = $pa1['lat'] + (($pd1['lat'] - $pa1['lat']) / 10 * 3);
        $b1lon  = $pa1['lon'] + (($pd1['lon'] - $pa1['lon']) / 10 * 3);

        $c1lat  = $pa1['lat'] + (($pd1['lat'] - $pa1['lat']) / 10 * 7);
        $c1lon  = $pa1['lon'] + (($pd1['lon'] - $pa1['lon']) / 10 * 7);
        $pb     = ['lat'=>$blat,'lon'=>$blon];
        $pc     = ['lat'=>$clat,'lon'=>$clon];
        $pb1    = ['lat'=>$b1lat,'lon'=>$b1lon];
        $pc1    = ['lat'=>$c1lat,'lon'=>$c1lon];

        $courtTopPoints = [
            'p_a'   => $pa,
            'p_b'   => $pb,
            'p_c'   => $pc,
            'p_d'   => $pd,
            'p_a1'  => $pa1,
            'p_b1'  => $pb1,
            'p_c1'  => $pc1,
            'p_d1'  => $pd1
        ];

        foreach ($courtTopPoints as &$point)
        {
            $point = $point['lat'].",".$point['lon'];

        }

        //计算高宽
        $courtTopPoints['width']        = gps_distance($pa['lon'],$pa['lat'],$pd['lon'],$pd['lat']);
        $courtTopPoints['length']       = gps_distance($pa['lon'],$pa['lat'],$pa1['lon'],$pa1['lat']);

        //更新球场
        DB::table("football_court")->where('court_id',$courtId)->update($courtTopPoints);
    }


    /**
     * 求得虚拟球场的斜率
     * @param $gpsArr array
     * @return float
     * */
    static function get_visual_court_angle($gpsArr)
    {
        $gpsNum     = count($gpsArr);
        $points     = [];
        //每隔5S分段一次
        $timeLength = 30;

        $lineAngles = [];
        $distances  = [];

        //求得每条线的斜率
        for($i=0; $i+$timeLength < $gpsNum; $i+=$timeLength){

            $begin  = $gpsArr[$i];
            $end    = $gpsArr[$i+$timeLength];

            //如果两个点的距离太小，则不取

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

        return $courtAngle;
    }
    /**
     * 获取两条直线的交点
     * @param $k1 float
     * @param $b1 float
     * @param $k2 float
     * @param $b2 float
     * @return array
     * **/



    /**
     * 获得距离直线不同方向的最远的点
     * @param $lineSlope float 直线的K
     * @param $lineB float 直线的B
     * @param $points array 要查询的点
     * @return array
     * */
    static function get_max_dis_point($lineSlope,$lineB,$points)
    {
        //3.1把GPS分成两个方向，中心的上方和下方
        $maxUpDis   = 0;
        $maxDownDis = 0;
        $pointUp    = null;   //球场最上的点
        $pointDown  = null;   //球场最下的点
        $courtAngle = pi_to_angle(atan($lineSlope));
        $sinAngle   = sin(angle_to_pi(90-abs($courtAngle)));

        //经过球心把球场分成上下两部分 但是有可能有一方没有数据
        foreach($points as $gps){

            //如果当前点的x值对呀的Y小于直线上点的Y，则在下方，否则在上方
            $currrentLat    = $lineSlope*$gps[1] + $lineB;                    //当前点的x对应的线的Y值
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

        return ['up'=>$pointUp,'down'=>$pointDown];
    }



    static function get_jiao_dian($k1,$b1,$k2,$b2){

        $x = ($b2 - $b1) / ($k1 - $k2);

        $y = $k1 * $x + $b1;

        return ["x"=>$x,"y"=>$y];
    }


    /**
     * 坐标转换
     * @param $centerX float 中心点X
     * @param $centerY float 中心点Y
     * @param $x float
     * @param $y float
     * @param $angle float 要转换的角度
     * @return array
     * */
    function change_coordinate($centerX,$centerY,$x,$y,$angle){

        $a = angle_to_pi($angle);

        $x0= ($x - $centerX)*cos($a) - ($y - $centerY)*sin($a) + $centerX ;
        $y0= ($x - $centerX)*sin($a) + ($y - $centerY)*cos($a) + $centerY ;

        return ["x"=>$x0,"y"=>$y0];
    }


    /**
     * 球场创建热点图
     * @param $pa array
     * @param $pa1 array
     * @param $pd array
     * @param $pd1 array
     * @param $points array
     * @param $width int 球场宽度
     * @param $height int 球场高度
     * @return array
     * */
    static function create_gps_map($pa,$pa1,$pd,$pd1,$points,$width=1000,$height=557)
    {
        $centerx    = $pa['x'] + ($pd1['x'] - $pa['x'])/2;
        $centery    = $pa['y'] + ($pd1['y'] - $pa['y'])/2;

        //获得要转动的角度
        $slope      = ($pa['y'] - $pa1['y']) / ($pa['x'] - $pa1['x']);
        $angle      = pi_to_angle(atan($slope));
        $angle      = -$angle; //斜率大于0:减去角度  斜率小于0：加上角度

        //将旋转后的数据缩放到前端界面要显示尺寸


        //将最左最下的点设置为原点
        $origin     = null;
        $originDis  = 0;
        $topPoint   = [
            'pa'    => $pa,
            "pa1"   => $pa1,
            "pd"    => $pd,
            "pd1"   => $pd1
        ];

        //将顶点置为新的坐标点
        foreach($topPoint as $key => $gps)
        {
            $gps            = change_coordinate($centerx,$centery,$gps['x'],$gps['y'],$angle);
            $topPoint[$key] = $gps;
            $dis            = gps_distance(0,0,$gps['x'],$gps['y']);

            if($originDis == 0 || $dis < $originDis)
            {
                $originDis  = $dis;
                $origin     = $gps;
            }
        }

        //找一个最小的点作为远点
        $perx   = $width   / abs($topPoint['pa']['x'] - $topPoint['pa1']['x']);
        $pery   = $height  / abs($topPoint['pa']['y'] - $topPoint['pd']['y']);

        //旋转 缩放 每个点
        foreach($points as $key => $p)
        {
            //旋转
            $gps   = change_coordinate($centerx,$centery,$p['x'],$p['y'],$angle);

            //缩放
            $gps   = self::move_and_scroll_point($origin['x'],$origin['y'],$gps['x'],$gps['y'],$perx,$pery);

            $points[$key] = $gps;
        }
        return $points;
    }



    /**
     * 平移和缩放数据
     * @param $originX float 原点x
     * @param $originY float 原点Y
     * @param $x float 要移动的X
     * @param $y float 要移动的Y
     * @param $perx float 移动后的没个X要缩放的倍数
     * @param $pery float 移动后的每个Y要缩放的高度
     * @return array
     * */
    static function move_and_scroll_point($originX,$originY,$x,$y,$perx,$pery)
    {
        $x = ($x - $originX) * $perx;

        $y = ($y - $originY) * $pery;

        return ['x'=>$x,'y'=>$y];
    }

    /**
     * 切割球场和配置射门角度
     * 1.切割球场
     * 2.创建球场配置文件
     * @param $courtId integer 球场ID
     *
     * */
    public function cut_court_to_box_and_create_config($courtId)
    {
        $boxs           = $this->cut_court_to_small_box($courtId);          //划分球场成多个区域图

        $configFile     = $this->create_court_gps_angle_config($courtId);   //球场角度配置文件

        $courtData      = ['boxs'=>\GuzzleHttp\json_encode($boxs),"config_file"=>$configFile];

        CourtModel::where('court_id',$courtId)->update($courtData);
    }



    /**
     * 检查球场是否符合标准
     * @param $width integer 宽度
     * @param $length integer 高度
     * @return integer
     * */
    static function check_court_is_valid($width,$length)
    {
        if($width == 0 || $length == 0){

            return false;
        }
        //检验球场是否合格
        $scale  = $length / $width;

        if($width < 2 || $width > 100 || $length < 4  || $length > 200 || $scale < 1.1 || $scale > 2.5 ) {

            return false;
        }

        return true;
    }


    /**
     * 判断是否在球场
     * 注意，这里一定是经过转换过的坐标
     * @param $minx float
     * @param $maxx float
     * @param $miny float
     * @param $maxy float
     * @param $x float
     * @param $y float
     * @return boolean
     * */
    static function judge_in_court($minx,$maxx,$miny,$maxy,$x,$y)
    {
        if($x > $minx && $x < $maxx && $y > $miny && $y <$maxy){

            return true;
        }
        return false;
    }
}