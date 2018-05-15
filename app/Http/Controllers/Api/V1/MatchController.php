<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use Illuminate\Support\Facades\Redis;
use DB;



class MatchController extends Controller
{

    /**
     * 上传比赛数据
     * */
    public function upload_match_data(Request $request)
    {

        $matchId    = $request->input('matchId',0);
        $userId     = $request->input('userId',0);
        $deviceSn   = $request->input('deviceSn','');
        $deviceData = $request->input('deviceData','');

        $dataType   = $request->input('dataType');

        $matchId    = time();
        $matchData  = [
            'match_id'  => $matchId,
            'user_id'   => $userId,
            'device_sn' => $deviceSn,
            'created_at'=> date_time()
        ];

        if($dataType == 'gps')
        {
            $this->handle_gps($deviceData,$matchData);

        }elseif($dataType == 'sensor') {

            $this->handle_sensor($deviceData,$matchData);

        }
        return apiData()->send(200,'ok');
    }





    /**
     * 处理GPS信号
     * 每组gps信号由4部分组成
     * 1.类型（4位)
     * 2.时间 (8位)
     * 3.长度（8位)
     * 4.数据 ，位数=3所得的长度
     * */
    public function handle_gps($gps,$matchData)
    {


        //$gps        = "23232323 26010000 29000000 474e474741 2c30302c39392e39392c2c2c2c2c2c2a37320d0a,";
        //$gps       .= "23232323 26010000 29000000 474e474741 2c30302c39392e39392c2c2c2c2c2c2a37320d0a";

        $gps        = str_replace(" ","",$gps);
        $matchModel     = new MatchModel();
        $gpsArray       = explode(',',$gps);
        foreach($gpsArray as $singleData)
        {
            $otherInfo  = [
                'source_data'   => $singleData,
                'status'        => 0,
                'data_key'      => time(),
            ];


            $fullMatchInfo      = array_merge($matchData,$otherInfo);
            $matchModel->add_gps_data($fullMatchInfo);

        }
    }


    public function handle_sensor($sensor,array $matchData)
    {


        $sensor     = str_replace(" ","",$sensor);
        $sensorArray= explode(',',$sensor);
        $matchModel = new MatchModel();

        foreach($sensorArray as $singleData)
        {
            $otherInfo  = [
                'source_data'   => $singleData,
                'data_key'      => time(),
            ];

            $fullMatchInfo      = array_merge($matchData,$otherInfo);
            $matchModel->add_sensor_data($fullMatchInfo);

        }

    }



    public function redis()
    {

        return [Redis];
        //Redis::set('name',time());

        $name = Redis::get('name');
        exit('kk');
        return "hello";
        return [$name];

    }


    public function read_data()
    {

        $datas  = DB::table('match_gps')->where('gps_id',">=",276)->where('gps_id',"<=",281)->get();

        $str    = "";

        $arr    = [];
        foreach($datas as $key => $d)
        {

            //1.去掉前面的类型和数字  但是前面是不固定的，所以只有在第一条的时候切割掉前面的是数字

            $posi   = stripos($d->source_data,"2c00");

            $str    .= substr($d->source_data,$posi+4);
        }


        $arr    = explode("23232323",$str);
        $arr    = array_filter($arr);
        foreach($arr as $key =>  $single)
        {
            $data   = substr($single,16);
            $data   = str_replace("0d0a","",$data);
            $arr[$key]  = strToAscll($data);

            $d = explode(",",$arr[$key]);
            $d2 = [];
            foreach($d as $k1 => $d1)
            {

                $d2['i-'.$k1]= $d1;
            }
            return $d2;
        }
        return $arr;
    }




}






function strToAscll($str)
{
    $len    = strlen($str);
    $temp   = "";

    for($i = 0;$i<$len;$i=$i+2)
    {

        $temp .= chr(hexdec(substr($str,$i,2)));   //十六进制转换成ASCLL

    }
    return $temp;
}