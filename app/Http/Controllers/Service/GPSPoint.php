<?php
namespace App\Http\Controllers\Service;

class GPSPoint{

    public $lat;
    public $lon;


    public function __construct(string $lat = "", string  $lon = "")
    {
        if($lat != "")
        {
            $this->lat = $lat;
        }

        if($lon != ""){

            $this->lon = $lon;

        }
    }
}