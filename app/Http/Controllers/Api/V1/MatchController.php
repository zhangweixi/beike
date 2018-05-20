<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\V1\MatchModel;
use Illuminate\Support\Facades\Redis;
use DB;
use App\Jobs\AnalysisMatchData;




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
            'type'      => $dataType,
            'data'      => $deviceData
        ];

        $matchModel     = new MatchModel();

        $sourceId       = $matchModel->add_match_source_data($matchData);

        //数据存储完毕，调用MATLAB系统开始计算

        $delayTime      = now()->addSecond(2);
        AnalysisMatchData::dispatch($sourceId)->delay($delayTime);

        $request->offsetSet('sourceId',$sourceId);
        $this->handle_data($request);
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


    public function handle_data(Request $request)
    {

        $sourceId = $request->input('sourceId');
        $data   = DB::table('match_source_data')->where('match_source_id',$sourceId)->first();
        $type   = $data->type;
        $datas  = explode(",",$data->data);
        $str    = "";
        $matchData  = [
            'source_id' => $sourceId,
            'match_id'  => $data->match_id,
            'user_id'   => $data->user_id,
            'device_sn' => $data->device_sn,
        ];

        foreach($datas  as $data)
        {
            //1.去掉前面的类型和数字  但是前面是不固定的，所以只有在第一条的时候切割掉前面的是数字
            $str    .= $this->delete_head($data);
        }


        if($type == 'gps')
        {
            return $this->handle_gps_data($str,$matchData);

        }elseif($type == 'sensor'){

            return $this->hand_sensor_data($str,$matchData);
        }
    }


    /**
     * 从数据库读取数据并解析成想要的格式
     * */
    public function handle_gps_data($dataSource,$matchData)
    {
        $matchModel  = new MatchModel();
        $dataList    = explode("23232323",$dataSource); //gps才有232323
        $dataList    = array_filter($dataList);

        $lat    = [];
        $lon    = [];
        $spe    = [];
        $dir    = [];


        foreach($dataList as $key =>  $single)
        {
            $data       = substr($single,16);
            $arr[$key]  = strToAscll($data);
            $detailInfo = explode(",",$arr[$key]);

            if(count($detailInfo)<15)
            {
                continue;
            }

            $tlat       = $detailInfo[2];
            $tlon       = $detailInfo[4];
            $tspe       = $detailInfo[11];
            $tdir       = $detailInfo[3]."/".$detailInfo[5];

            array_push($lat,$tlat);
            array_push($lon,$tlon);
            array_push($spe,$tspe);
            array_push($dir,$tdir);

            if(0)
            {
                //将分解的数据写入数据库
                $otherInfo  = [
                    'source_data'   => $single,
                    'latitude'      => $tlat,
                    'longitude'     => $tlon,
                    'speed'         => $tspe,
                    'direction'     => $tdir,
                    'status'        => $detailInfo[6],
                    'data_key'      => 0,
                    'data_time'     => $detailInfo[1],
                ];
                $fullMatchInfo      = array_merge($matchData,$otherInfo);
                $matchModel->add_gps_data($fullMatchInfo);
            }
        }
        //要获得两种数据 1是负责给matlab处理的，一种是负责给写入数据库的 同时写入会造成新能问题
        $matlabData = [
            'lat'   => $lat,
            'lon'   => $lon,
            'spe'   => $spe,
            'dir'   => $dir
        ];

        file_put_contents(public_path('gps.json'),\GuzzleHttp\json_encode($matlabData));
        return $matlabData;
    }





    /**
     * 去除头部
     * */
    private function delete_head($str)
    {
        $p = strpos($str,"2c");
        $str = substr($str,$p+2);
        $p = strpos($str,'2c');
        //$key = substr($str,0,$p);
        $str = substr($str,$p+2);
        return $str;
    }


    /**
     * 读取sensor数据
     * */
    public function hand_sensor_data($dataSource,$matchData)
    {

        $dataArr= str_split($dataSource,40);

        $ax = $ay = $az = $gx = $gy = $gz = [];
        foreach($dataArr as $key => $d)
        {
            if(strlen($d)<40)
            {
                continue;
            }

            $single     = str_split($d,8);
            foreach($single as $key2 => $v2)
            {
                $single[$key2]  = hexToInt($v2);
            }

            $type   = $single[0];
            if ($type == 1)  //重力感应
            {
                array_push($ax,$single[1]);
                array_push($ay,$single[2]);
                array_push($az,$single[3]);

            } elseif ($type == 0) { //acc 加速度

                array_push($gx,$single[1]);
                array_push($gy,$single[2]);
                array_push($gz,$single[3]);
            }
        }


        $data   = [
            'ax'    => $ax,
            'ay'    => $ay,
            'az'    => $az,
            'gx'    => $gx,
            'gy'    => $gy,
            'gz'    => $gz
        ];

        file_put_contents(public_path('sensor.json'),\GuzzleHttp\json_encode($data));
        return $data;




        $arr = str_split($str,8);

        $hexStr = $str;
        $data = [[],[],[],[],[],[]];
        $offset = 0;
        while (1)
        {
            $str = substr($hexStr, $offset, 40);
            $offset += 40;
            if (strlen($str) < 40)
            {
                break;
            }


            //type
            $type = hexToInt(substr($str, 0, 8));

            if ($type == 1)  //重力感应
            {
                $data[0][] = hexToInt(substr($str, 8, 8));
                $data[1][] = hexToInt(substr($str, 16, 8));
                $data[2][] = hexToInt(substr($str, 24, 8));

            } elseif ($type == 0) { //acc 加速度

                $data[3][] = hexToInt(substr($str, 8, 8));
                $data[4][] = hexToInt(substr($str, 16, 8));
                $data[5][] = hexToInt(substr($str, 24, 8));
            }

            //var_dump(hexToInt(substr($str, 32, 8)));
        }
        echo json_encode($data);


    }

    /**
     * 数据结构
     * */
    public function data_struct()
    {
        $gps    = [
            'lat'   =>[30.9022363331,30.9022363331,30.9022363331],
            'lon'   =>[121.1792043623,121.1792043623,121.1792043623],
            'spe'   =>[90,90,90],
            'dir'   =>["L","R","R"]
        ];


        $sensor = [
            'ax'    => [1,2,3,4],
            'ay'    => [1,2,3,4],
            'az'    => [1,2,3,4],
            'gx'    => [1,2,3,4],
            'gy'    => [1,2,3,4],
            'gz'    => [1,2,3,4],
        ];

        $data   = [
            'gps'       => $gps,
            'sensor'    => $sensor
        ];

        $data1   = [    //结果数据
            'SM'    => 1,
            'CQ'    => 2,
            'LL'    => 3,
            'PD'    => 90,
            'PD'    => 3,
            'FS'    => 34,
        ];

        //file_put_contents(public_path('json.json'),\GuzzleHttp\json_encode($data));
        return $data;
    }



    public function job()
    {
        $delayTime  = now()->addSecond(3);
        AnalysisMatchData::dispatch(900)->delay($delayTime);
        return "hello";
    }

}











function hexToInt($hex)
{
    if (strlen($hex) != 8)
    {
        return false;
    }

    //将低位在前高位在后转换成 高位在前低位在后
    $bHex = substr($hex, 6, 2) . substr($hex, 4, 2) . substr($hex, 2, 2) . substr($hex, 0, 2);
    return unpack("l", pack("l", hexdec($bHex)))[1];
}


