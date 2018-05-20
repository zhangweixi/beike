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

    /**
     * 从数据库读取数据并解析成想要的格式
     * */
    public function read_gps_data()
    {

        $matchId= 1526625576;

        $datas  = DB::table('match_gps')->where('match_id',$matchId)->get();

        $str    = "";

        foreach($datas as $key => $d)
        {
            //1.去掉前面的类型和数字  但是前面是不固定的，所以只有在第一条的时候切割掉前面的是数字

            $str    .= $this->delete_head($d->source_data);
        }

        $arr    = explode("23232323",$str);
        $arr    = array_filter($arr);

        foreach($arr as $key =>  $single)
        {
            $data   = substr($single,16);

            $arr[$key]  = strToAscll($data);

            if(0) //返回切割后的
            {
                $d = explode(",",$arr[$key]);
                $d2 = [];
                foreach($d as $k1 => $d1)
                {
                    $d2['i-'.$k1]= $d1;
                }
                return $d2;
            }
        }
        return $arr;
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
    public function read_sensor_data()
    {

        $str    = "00000000 99feffff 95010000 d5fcffff 55020000 01000000 99feffff 95010000 d5fcffff 55020000";

        $str    = "";
        $sensors= DB::table('match_sensor')->where('match_id','1526628259')->orderBy('sensor_id','asc')->get();
        $arr    = [];
        foreach($sensors as $str1)
        {
            //array_push($arr,$str1->source_data);
            //array_push($arr,$this->delete_head($str1->source_data));

            $str .= $this->delete_head($str1->source_data);
        }


        return $str;


        $str    = str_replace(" ", "", $str);
        $dataArr= str_split($str,40);


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


