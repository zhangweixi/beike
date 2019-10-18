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
    //DIRECTORY_SEPARATOR 常量，系统路径分隔符
    return public_path("uploads".DIRECTORY_SEPARATOR."match".DIRECTORY_SEPARATOR.$matchId.DIRECTORY_SEPARATOR);
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

/**
 * 删除目录
 * */
function deldir($dir) {

    if(!is_dir($dir))
    {
        return true;
    }

    //先删除目录下的文件：
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if($file != "." && $file!="..") {
            $fullpath = $dir."/".$file;
            if(!is_dir($fullpath)) {
                unlink($fullpath);
            } else {
                deldir($fullpath);
            }
        }
    }
    closedir($dh);

    //删除当前文件夹：
    if(rmdir($dir)) {
        return true;
    } else {
        return false;
    }
}


/**
 * 获取天气
 * @param $lat float
 * @param $lon float
 * @return array
 * */
function get_weather($lat,$lon){

    if($lat == 0 || $lon == 0){
        return [];
    }

    $host = "https://ali-weather.showapi.com";
    $path = "/gps-to-weather";
    $method = "GET";
    $appcode = config('aliyun.appCode');
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    $querys = "from=5&lat=".$lat."&lng=".$lon;

    $bodys = "";
    $url = $host . $path . "?" . $querys;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    if (1 == strpos("$".$host, "https://"))
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }

    $result     = curl_exec($curl);
    $result     = \GuzzleHttp\json_decode($result);

    if($result->showapi_res_code != 0){

        return [];
    }

    $weather    = $result->showapi_res_body->now;
    $weather    = [
        "temperature"   => $weather->temperature,
        "weather"       => $weather->weather
    ];
    return $weather;
}

/**
 * 执行系统的artisan命令
 * @param $command string 执行的命令
 * @param $async boolean 是否异步执行
 * @return boolean
 * */
function artisan($command,$async=false){

    $basePath = base_path('');

    $cmd = "php {$basePath}/artisan {$command}";

    if($async){

        return asyn_shell($cmd);

    }else{

        return shell_exec($cmd);
    }
}

/**
 * GPS无效点过滤
 * @param $file string 文件
 * @param $keyLat int
 * @param $keyLon int
 * @param $resFile string
 * */
function gps_filter($file,$keyLat,$keyLon,$resFile = ''){

    $fd     = fopen($file,'a+');
    $num    = 0;
    $lats   = [];
    $lons   = [];

    while(!feof($fd)){

        $data = trim(fgets($fd));
        if(!$data){
            continue;
        }

        $data = explode(" ",$data);
        if($data[$keyLat] == 0 || $data[$keyLon] == 0){
            continue;
        }

        //取1000个点来计算平均数
        $lats[] = $data[$keyLat];
        $lons[] = $data[$keyLon];

        $num++;
        if($num == 1001){
            break;
        }
    }

    //计算平均数
    $avgLat = array_sum($lats) / count($lats);
    $avgLon = array_sum($lons) / count($lons);
    foreach($lats as $key => $lat){
        abs($lat - $avgLat) > 1 ? array_splice($lats,$key,1) : null;
    }

    foreach($lons as $key => $lon){
        abs($lon - $avgLon) > 1 ? array_splice($lons,$key,1): null;
    }

    $avgLat = array_sum($lats) / count($lats);
    $avgLon = array_sum($lons) / count($lons);
    fseek($fd,0);


    $temp   = $file.randStr(5);
    $fdt    = fopen($temp,'w');
    $num    = 0;
    $gpsArr = [];

    while(!feof($fd)){

        $data       = trim(fgets($fd));

        if($data != ''){

            $data       = explode(" ",$data);
            $lat        = $data[$keyLat];
            $lon        = $data[$keyLon];

            if(abs($lat - $avgLat) > 1 || abs($lon - $avgLon) > 1){
                $data[$keyLon] = 0;
                $data[$keyLat] = 0;
            }

            $gpsArr[]   = implode(" ",$data)."\n";
        }


        if($num == 1000 || feof($fd))
        {
            fwrite($fdt,implode('',$gpsArr));
            $num = 0;
            $gpsArr = [];
        }
        $num ++;
    }

    fclose($fd);
    fclose($fdt);
    if($resFile == ''){
        $resFile = $file;
    }

    copy($temp,$resFile);
    unlink($temp);
}