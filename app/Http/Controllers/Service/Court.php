<?php
namespace App\Http\Controllers\Service;

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

    private $centerPoints = [];

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


    /**
     * 设置中心点
     * @param $points array 中心点集合
     * */
    public function set_centers(array $points)
    {
        $this->centerPoints = $points;
    }

    /**
     * 找到最近的点
     * @param $point GPSPoint 要找的点
     * @return array
     * */
    public function find_nearest_point(GPSPoint $point)
    {
        $minDis     = 100000000;

        $position   = [0,0];

        foreach($this->centerPoints as $key1 => $line)
        {
            $preDis = 100000000;

            foreach($line as $key2 => $p)
            {
                $a      = bcmul(bcsub($point->lat,$p->lat),10000);
                $b      = bcmul(bcsub($point->lon,$p->lon),10000);

                $dis    = bcadd(bcmul($a,$a),bcmul($b,$b));

                if($dis < $minDis)
                {
                    $minDis = $dis;
                    $position = [$key1,$key2];
                }

                //如果当前距离大于上一个点的距离，说明越来越远，则不用计算后面的点了
                if($dis > $preDis)
                {
                    break;
                }
                $preDis = $dis;
            }
        }

        return $position;
    }

    public  $middlePoints   = [];

    /**
     * 找到最近的点
     * @param $point GPSPoint 要找的点
     * @return array
     * */
    public function find_nearest_point1(GPSPoint $point)
    {
        //找到中间一列
        if($this->middlePoints == [])
        {
            foreach($this->centerPoints as $key=>$line)
            {
                array_push($this->middlePoints,$line[ceil($this->latNum/2)]);
            }
        }

        //寻找最近的那一列
        $key1    = $this->min_dis_position($point,$this->middlePoints);



        $linePoints = $this->centerPoints[$key1];


        $key2   = $this->min_dis_position($point,$linePoints);
        $position   = [$key1,$key2];

        return $position;
    }

    /**
     * 获得最小距离的位置
     * @param $p GPSPoint
     * @param $points GPSPoint
     * @return integer
     * */
    private function min_dis_position($p,$points)
    {
        $minDis = 100000000;
        $preDis = 100000000;

        $posi   = null;

        foreach($points as $key => $point)
        {
            $a      = bcmul(bcsub($point->lat,$p->lat),1000);
            $b      = bcmul(bcsub($point->lon,$p->lon),1000);

            $dis    = bcadd(bcmul($a,$a),bcmul($b,$b));

            if($dis < $minDis)
            {
                $minDis     = $dis;
                $posi       = $key;
            }

            //如果当前距离大于上一个点的距离，说明越来越远，则不用计算后面的点了
            if($dis > $preDis)
            {
                break;
            }
            $preDis = $dis;
        }

        return $posi;
    }



    public $maxLat  = 0;
    public $minLat  = 0;
    public $maxLon  = 0;
    public $minLon  = 0;



    /**
     * 创建球场热点图
     * @param $points GPSPoint[]
     * @return  array
     * */
    public function create_court_hot_map($points)
    {
        $result   = [];

        //初始化一个二维数组
        for($i=0;$i<$this->lonNum;$i++)
        {
            for($j=0;$j<$this->latNum;$j++)
            {
                $result[$i][$j] = 0;
            }
        }

        foreach($points as $point)
        {
            if(intval($point['lat']) == 0 ) {

                continue;
            }

            $gpsPoint   = new GPSPoint($point['lat'],$point['lon']);

            $position   = $this->find_nearest_point($gpsPoint);

            $result[$position[0]][$position[1]]++;
        }

        return $result;
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

        $config .= implode(" ",$b)." ".implode(" ",$c)." 0";

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
        $gpsGroupId = CourtModel::where('court_id',$courtId)->value('gps_group_id');

        $points     = DB::table('football_court_point')
            ->where('gps_group_id',$gpsGroupId)
            ->select('position','device_lat','device_lon')
            ->get()->toArray();

        foreach($points as $key => $p)
        {
            $points[$key]   = implode(" ",object_to_array($p));
        }
        $points     = implode("\n",$points);
        $file       = "uploads/court-config/{$courtId}/border-src.txt";
        mkdir(public_path("uploads/court-config/{$courtId}"),777,true);
        file_put_contents(public_path($file),$points);

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


}