<?php
namespace App\Http\Controllers\Service;

use App\Http\Controllers\Service\GPSPoint;
use phpDocumentor\Reflection\Types\Integer;

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

    private $lonNum = 5;
    private $latNum = 10;

    private $centerPoints = [];

    public function __construct()
    {
        bcscale (8);
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

    /*间隔获得数组内容*/
    static function array_cycle($arr,$cycle)
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
        $this->centerPoints = array_chunk($points,$this->lonNum);
    }

    /**
     * 找到最近的点
     * @param $point GPSPoint 要找的点
     * */
    public function find_nearest_point(GPSPoint $point)
    {
        $minDis = 100000000;

        foreach($this->centerPoints as $key1 => $line)
        {
            $preDis = 100000000;
            foreach($line as $key2 => $p)
            {

                $a = bcsub($point->lat,$p->lat);
                $b = bcsub($point->lon,$p->lon);
                $dis = bcadd(bcmul($a,$a),bcmul($b,$b));

                $minDis = $dis < $minDis ?? $dis;
                //如果当前距离大于上一个点的距离，说明越来越远，则不用计算后面的点了
                if($dis > $preDis)
                {
                    break;
                }
                $preDis = $dis;
            }
        }
    }


}