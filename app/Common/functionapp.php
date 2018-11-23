<?php
use Illuminate\Support\Facades\DB;

/**
 * 获得默认头像
 * @param $head string 头像
 * @return string
 * */
function get_default_head($head='')
{
    return $head ? config('app.filehost')."/".$head : url("beike/images/default/head.png");
}

/**
 * 生日转年龄
 * */
function birthday_to_age($birthday)
{
    if(empty($birthday))
    {
        return 0;
    }

    $year1  =  substr($birthday,0,4);
    $yearn  =  date('Y');
    $age    =  $yearn - $year1;
    if($age < 0 || $age > 100)
    {
        return 0;
    }

    return $age;

}


function credit_to_text($credit)
{
    switch ($credit)
    {
        case 0:     $text = "不限";break;
        case 60:    $text = "中等以上";break;
        case 80:    $text = "良好以上";break;
        case 90:    $text = "优秀以上";break;
    }
    return $text;
}



function text_to_credit($text)
{
    switch ($text)
    {
        case "不限":       $credit = 0;break;
        case "中等以上":    $credit = 60;break;
        case "良好以上":    $credit = 80;break;
        case "优秀以上":    $credit = 90;break;
        default: $credit = 0;
    }
    return $credit;
}

/**
 * 百度GPS和国标GPS的互换
 * @param $gpsArr 要转换的GPS数组
 * @param $from int 原始坐标类型
 * @param $to 转换后的坐标类型
 * @return array
 * */
function bdgps_gbgps($gpsArr,$from,$to){

    $gpsArr = json_encode($gpsArr);
    $gpsArr = json_decode($gpsArr,true);

    $gpsArr = array_chunk($gpsArr,100);

    $result = [];

    foreach($gpsArr as $points)
    {
        $tempArr    = [];
        foreach($points as $key => $p)
        {
            array_push($tempArr,$p['lon'].",".$p['lat']);
        }

        $str    = implode(";",$tempArr);
        $url    = "http://api.map.baidu.com/geoconv/v1/?coords={$str}&from={$from}&to={$to}&ak=zZSGyxZgUytdiKG135BcnaP6";

        $bdgps  = file_get_contents($url);
        $bdgps  = \GuzzleHttp\json_decode($bdgps,true);
        $bdgps  = $bdgps['result'];
        foreach ($bdgps as $gps)
        {
            array_push($result,['lat'=>$gps['y'],'lon'=>$gps['x']]);
        }
    }
    return $result;

}

/**
 * GPS转换成百度GPS
 * @param $gps array gps
 * @return array
 * */
function gps_to_bdgps($gps)
{
    $cmd        = "node ". app_path('node/gps.js') . " --outtype=str --lat={$gps['lat']} --lon={$gps['lon']}";
    $cmd        = str_replace("\\","/",$cmd);
    $result     = shell_exec($cmd);
    $result     = json_decode($result,true);

    return ['lat'=>$result[1],'lon'=>$result[0]];

}

/**
 * 百度GPS转换到GPS
 * @param $gpsArr array 转换的数据
 * @return array
 * */
function bdgps_to_gps($gpsArr)
{
    return bdgps_gbgps($gpsArr,5,3);
}



function logbug($content)
{
    if(is_array($content)){
        
        $content = json_encode($content);

    }elseif(is_object($content)){

        $content = object_to_array($content);
        logbug($content);
        return;
    }
    DB::table('debug')->insert(['debuginfo'=>$content,'created_at'=>date_time()]);
}

function matchdir($matchId)
{
    return public_path("uploads/match/{$matchId}/");
}

/**
 * 由两个点获得直线方程系数
 * */
function get_fun_params_by_two_point($p1,$p2)
{
    $x1 = $p1[0];
    $x2 = $p2[0];
    $y1 = $p1[1];
    $y2 = $p2[1];

    $k = bcdiv(bcsub($y2,$y1),bcsub($x2,$x1));
    $b = bcdiv(bcsub( bcmul($x1,$y2), bcmul($x2,$y1) ), bcsub($x2,$x1));

    return [$k,$b];
}


/**
 * 通过三个点来获得一个圆形
 * */
function get_cycle_params_by_three_point($p1,$p2,$p3){

    class point {

        public function __construct($x=0,$y=0){
            $this->x = $x;
            $this->y = $y;
        }

        public $x;
        public $y;
    }


    $center = new point();



    $midp1 = new point();
    $midp2 = new point();

    $midp1->x = ($p2->x + $p1->x)/2;
    $midp1->y = ($p2->y + $p1->y)/2;


    $midp2->x = ($p3->x + $p1->x)/2;
    $midp2->y = ($p3->y + $p1->y)/2;


    $k1 = -($p2->x - $p1->x)/($p2->y - $p1->y);

    $k2 = -($p3->x - $p1->x)/($p3->y - $p1->y);

    $center->x = ($midp2->y - $midp1->y- $k2 * $midp2->x + $k1*$midp1->x)/($k1 - $k2);
    $center->y = $midp1->y + $k1*( $midp2->y - $midp1->y - $k2*$midp2->x + $k2*$midp1->x)/($k1-$k2);

    $radius    = sqrt(($center->x - $p1->x) * ($center->x - $p1->x) + ($center->y - $p1->y) * ($center->y - $p1->y));

    return [$center->x,$center->y,$radius];
}


/**
 * 将速度由M/S改为KM/H
 * @param $speed float
 * @return float
 * */
function speed_second_to_hour($speed){

    $speed  = $speed * 60 * 60 / 1000;
    $speed  = round($speed,2);

    return $speed;
}