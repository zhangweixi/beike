<?php
namespace App\Http\Controllers\Service;

class GPSPoint{

    public $lat;
    public $lon;


    public function __construct(string $lat = "", string  $lon = "")
    {
        if($lat != "")
        {
            $this->lat = self::gps_to_gps($lat);
        }


        if($lon != ""){

            $this->lon = self::gps_to_gps($lon);

        }
    }


    static function gps_to_gps($gps)
    {
        bcscale (8);
        $num = bcdiv($gps,100);
        $int = (int)$num;
        $flo = bcmul(bcdiv(bcmod($num,1),60),100);
        return bcadd($int,$flo);
    }
}