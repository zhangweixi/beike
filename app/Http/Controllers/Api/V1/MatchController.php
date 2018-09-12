<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Base\BaseMatchResultModel;
use App\Models\Base\BaseMatchSourceDataModel;
use App\Models\V1\UserModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\V1\MatchModel;
use DB;
use App\Jobs\AnalysisMatchData;
use Illuminate\Support\Facades\Storage;



class MatchController extends Controller
{
    public function __construct()
    {
        ini_set ('memory_limit', '500M');
        set_time_limit(300);
    }

    /**
     * 上传比赛数据
     * */
    public function upload_match_data(Request $request)
    {
        $userId     = $request->input('userId',0);
        $deviceSn   = $request->input('deviceSn','');
        $deviceData = $request->input('deviceData','');
        $dataType   = $request->input('dataType');
        $foot       = $request->input('foot');
        $isFinish   = $request->input('isFinish',0);
        $isAll      = $request->input('isAll',0);

        //数据校验  以防客户端网络异常导致数据上传重复
        $checkCode  = crc32($deviceData);
        $hasFile    = BaseMatchSourceDataModel::check_has_save_data($userId,$checkCode);

        if($hasFile){

           # return apiData()->send(2001,'数据重复上传');
        }

        //数据文件存储在磁盘中
        $date   = date('Y-m-d');
        $time   = create_member_number();
        $file   = $date."/".$userId."-".$dataType.'-'.$foot.'-'.$time.".txt";//文件格式

        Storage::disk('local')->put($file,$deviceData);

        $matchData  = [
            'match_id'  => $request->input('matchId',0),
            'user_id'   => $userId,
            'device_sn' => $deviceSn,
            'type'      => $dataType,
            'data'      => $file,
            'foot'      => $foot,
            'is_finish' => $isFinish,
            'status'    => $isAll == 1 ? 2 : 0,
            'check_code'=> $checkCode
        ];

        //之前是否有 未完成解析的数据  正在解析  不要加入队列
        $hasData = DB::table('match_source_data')
            ->where('user_id',$userId)
            ->where('type',$dataType)
            ->where('foot',$foot)
            ->where('status',"<",2)
            ->first();


        //1.储存数据
        $matchModel     = new MatchModel();
        $sourceId       = $matchModel->add_match_source_data($matchData);

        if($isAll == 1)
        {
            return apiData()->send();
        }

        //数据已解析完毕，尽快解析本条数据
        if($hasData == null)
        {
            $host           = $request->getHost();
            $delayTime      = now()->addSecond(1);
            $data           = ['sourceId'=>$sourceId,'host'=>$host];
            AnalysisMatchData::dispatch("parse_data",$data)->delay($delayTime);
        }

        return apiData()->send(200,'ok');
    }



    /**
     * 百度地图
     * */
    public function baidu_map(Request $request)
    {
        $matchId    = $request->input('matchId');
        $baiduMap   = "match/".$matchId."-bd.json";
        $hasFile    = Storage::disk('web')->has($baiduMap);
        $fresh      = $request->input('fresh',0);

        if(!$hasFile || $fresh == 1) //没有转换过的数据
        {

            $file       = "match/".$matchId."-gps-L.json";
            $hasFile    = Storage::disk('web')->has($file);

            if($hasFile == false)
            {
                exit('gps文件不存在');
            }

            $gpsList = Storage::disk('web')->get($file);
            $gpsList = \GuzzleHttp\json_decode($gpsList);
            $lats   = $gpsList->lat;
            $lons   = $gpsList->lon;



            $length = count($lats);
            $points = [];

            for($i=0;$i<$length;$i++)
            {
                if($lats[$i]== '' || $lats[$i] == 0) continue;
                $p = [
                    'lat'   => gps_to_gps($lons[$i]),
                    'lon'   => gps_to_gps($lats[$i])
                ];
                array_push($points,$p);
            }

            if(true)
            {
                $points = array_chunk($points,100);

                $bdpoints= [];
                foreach($points as $key => $pointArr)
                {
                    $tempArr = [];
                    foreach($pointArr as $point)
                    {
                        array_push($tempArr,implode(',',$point));
                    }

                    $tempArr = implode(";",$tempArr);

                    $url = "http://api.map.baidu.com/geoconv/v1/?coords={$tempArr}&from=1&to=5&ak=zZSGyxZgUytdiKG135BcnaP6";

                    $tempArr = file_get_contents($url);
                    $tempArr = \GuzzleHttp\json_decode($tempArr);
                    $bdpoints= array_merge($bdpoints,$tempArr->result);
                }
            }else{

                $bdpoints   = [];
                foreach($points as $key => $point)
                {
                    array_push($bdpoints,['y'=>$point['lon'],'x'=>$point['lat']]);
                }
            }

            Storage::disk('web')->put($baiduMap,\GuzzleHttp\json_encode($bdpoints));

        }else{

            $bdpoints   = $gpsList = Storage::disk('web')->get($baiduMap);
            $bdpoints   = \GuzzleHttp\json_decode($bdpoints);
        }

        return apiData()->set_data('points',$bdpoints)->send();
    }



    public function find_gps()
    {
        $url = "http://dev.api.launchever.cn/uploads/match/result-99-gps.json";
        $content = file_get_contents($url);
        $content = \GuzzleHttp\json_decode($content);

        $lats = $content->lat;
        $lons = $content->lon;

        $latArr = [];
        $lonArr = [];

        foreach($lats as $lat)
        {
            if($lat == '') continue;
            $lat = $lat * 1;
            array_push($latArr,$lat);
        }


        foreach ($lons as $lon){

            if($lon == "") continue;
            $lon = $lon * 1;
            array_push($lonArr,$lon);
        }

        $maxLat = max($latArr);
        $minLat = min($latArr);

        $maxLon = max($lonArr);
        $minLon = min($lonArr);


        $minlon =0;
        $maxlon = 0;

        foreach($latArr as $key => $lat)
        {
            if($lat == $minLat)
            {
                $minlon = max($minlon,$lonArr[$key]);
                mylogger('minlat:'.$lat.",".$minlon);
            }

            if($lat == $maxLat)
            {
                $maxlon = max($maxlon,$lonArr[$key]);
                mylogger('maxlat:'.$lat.",".$maxlon);
            }
        }

        $str = "lat:\nmin-".$minLat.";\nmax-".$maxLat.";\nlon:\nmin-".$minLon.";\nmax-".$maxLon;
        exit($str);

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



    /**
     * 开始比赛
     * */
    public function add_match(Request $request)
    {

        $matchInfo  = [
            'user_id'   => $request->input('userId'),
            'court_id'  => $request->input('courtId',0),
        ];

        $matchModel = new MatchModel();
        $matchId    = $matchModel->add_match($matchInfo);
        $timestamp  = getMillisecond();

        $matchModel->log_match_status($matchId,'begin');
        return apiData()
            ->set_data('matchId',$matchId)
            ->set_data('timestamp',$timestamp)
            ->send();
    }


    /*
     * 结束比赛
     * */
    public function finish_match(Request $request)
    {
        $matchId    = $request->input('matchId');
        $matchModel = new MatchModel();
        $matchModel->finish_match($matchId);
        $matchModel->log_match_status($matchId,'stop');
        return apiData()->send(200,"success");
    }


    /**
     * 添加心情
     * */
    public function add_mood(Request $request)
    {
        $data   = [
            'mood'      => emoji_text_encode($request->input('mood')),
            'weather'   => $request->input('weather'),
            'message'   => emoji_text_encode($request->input('message')),
            'created_mood_at'   => date_time()
        ];

        $matchId    = $request->input('matchId');
        if($matchId < 0)
        {
            return apiData()->send(4001,'比赛ID异常');
        }

        $matchModel = new MatchModel();
        $matchModel->update_match($matchId,$data);

        return apiData()->send(200,'添加成功');
    }


    /**
     * 数据比赛
     * */
    public function match_list(Request $request)
    {
        $matchModel = new MatchModel();
        $userId     = $request->input('userId');

        $matchs     = $matchModel->get_match_list($userId);

        return apiData()->set_data('matchs',$matchs)->send(200,'success');
    }

    /**
     * 比赛详细基本信息
     * */
    public function match_detail_base(Request $request)
    {
        $matchId    = $request->input('matchId');
        $matchModel = new MatchModel();
        $matchInfo  = $matchModel->get_match_detail($matchId);


        $result     = BaseMatchResultModel::find($matchId);

        if($result) {

            $map        = $result->gps_map;
            $map        = \GuzzleHttp\json_decode($map);

            $map        = data_scroll_to($map,100);

        }else{//默认值

            $map        = create_round_array(20,32,true,0);
        }


        return apiData()
            ->set_data('matchInfo',$matchInfo)
            ->set_data('map',$map)
            ->send(200,'success');
    }



    public function match_detail_mood(Request $request)
    {
        $matchId    = $request->input('matchId');
        $matchModel = new MatchModel();

        $matchInfo  = $matchModel->get_match_detail($matchId);

        $matchInfo  = [
            'weather'       => $matchInfo->weather,
            'temperature'   => $matchInfo->temperature,
            'mood'          => $matchInfo->mood,
            'message'       => $matchInfo->message,
            'created_mood_at'=>$matchInfo->created_mood_at
        ];

        return apiData()
            ->set_data('matchInfo',$matchInfo)
            ->send();
    }

    /**
     * 比赛详细数据部分
     * */
    public function match_detail_data(Request $request)
    {

        $matchId    = $request->input('matchId');
        $matchModel = new MatchModel();
        $matchResult= $matchModel->get_match_result($matchId);

        if($matchResult == null)
        {
            return apiData()->send(2001,"系统正在对数据玩命分析，请稍等");
        }


        $data   = [
            'shoot'         => [
                'speedMax'  => $matchResult->shoot_speed_max,
                'speedAvg'  => $matchResult->shoot_speed_avg,
                'disMax'    => $matchResult->shoot_dis_max,
                'disAvg'    => $matchResult->shoot_dis_avg,
                'number'    => $matchResult->shoot_num_total,
            ],
            'passShort'    => [
                'speedMax'  => $matchResult->pass_s_speed_max,
                'speedAvg'  => $matchResult->pass_s_speed_avg,
                'disMax'    => $matchResult->pass_s_dis_max,
                'disAvg'    => $matchResult->pass_s_dis_avg,
                'number'    => $matchResult->pass_s_num
            ],
            'passLength'    => [
                'speedMax'  => $matchResult->pass_l_speed_max,
                'speedAvg'  => $matchResult->pass_l_speed_avg,
                'disMax'    => $matchResult->pass_l_dis_max,
                'disAvg'    => $matchResult->pass_l_dis_avg,
                'number'    => $matchResult->pass_l_num
            ],

            'run'        => [
                'lowDis'       => $matchResult->run_low_dis,
                'lowTime'      => $matchResult->run_low_time,
                'midDis'       => $matchResult->run_mid_dis,
                'midTime'      => $matchResult->run_mid_time,
                'highDis'      => $matchResult->run_high_dis,
                'highTime'     => $matchResult->run_high_time
            ],
            'touchball' => [
                "number"    => $matchResult->touchball_num,
                "speedMax"  =>  $matchResult->touchball_speed_max,
                "speedAvg"  =>  $matchResult->touchball_speed_avg,
                "strengthMax"=> $matchResult->touchball_strength_max,
                "strengthAvg"=> $matchResult->touchball_strength_avg
            ]
        ];

        return apiData()
            ->set_data('matchResult',$data)
            ->send(200,'success');

    }

    /**
     * 比赛热点图
     * */
    public function match_detail_hotmap(Request $request)
    {
        $matchId    = $request->input('matchId');
        $matchResult    = BaseMatchResultModel::find($matchId);

        $lowSpeed   = \GuzzleHttp\json_decode($matchResult->map_speed_low,true);
        $midSpeed   = \GuzzleHttp\json_decode($matchResult->map_speed_middle,true);
        $highSpeed  = \GuzzleHttp\json_decode($matchResult->map_speed_high,true);

        $lowSpeed   = data_scroll_to($lowSpeed,100);
        $midSpeed   = data_scroll_to($midSpeed,100);
        $highSpeed  = data_scroll_to($highSpeed,100);



        $shortPass  = \GuzzleHttp\json_decode($matchResult->map_pass_short,true);
        $longPass   = \GuzzleHttp\json_decode($matchResult->map_pass_long,true);
        $touchball  = \GuzzleHttp\json_decode($matchResult->map_touchball,true);



        $sprint     = create_round_array(12,22);
        $rob        = create_round_array(12,22);
        $dribble    = create_round_array(12,22);


        $maps       = [
            ['name'=>"高速跑动",'data'=>$highSpeed,'type'=>'hot'],
            ['name'=>"中速跑动",'data'=>$midSpeed,'type'=>'hot'],
            ['name'=>"低速跑动",'data'=>$lowSpeed,'type'=>'hot'],

            ['name'=>'短传','data'=>$shortPass,'type'=>'point'],
            ['name'=>'长传','data'=>$longPass,'type'=>'point'],
            ['name'=>'触球','data'=>$touchball,'type'=>'point'],
            //['name'=>'冲刺','data'=>$sprint,'type'=>'hot'],
            //['name'=>'抢断','data'=>$rob,'type'=>'hot'],
            //['name'=>'带球','data'=>$dribble,'type'=>'hot']
        ];

        return apiData()
            ->set_data('maps',$maps)
            ->send(200,'success');
    }


    /**
     * 历史比赛数据
     * 1.近10场比赛
     * 2.全部比赛
     * */
    public function match_history(Request $request)
    {
        $userId     = $request->input('userId');
        $number     = $request->input('matchNumber',20);

        //静止、慢跑、中速跑、高速跑
        $matches = [];
        $totalPass  = 0;
        $totalShoot = 0;
        $totalRun   = 0;

        for($i =0;$i<$number;$i++)
        {
            $match = [
                'shoot' => rand(0,20),
                'pass'  => rand(0,50),
                'run'   => rand(0,100),
                'x'     => $i+1,
            ];

            $totalPass += $match['pass'];
            $totalRun  += $match['run'];
            $totalShoot+= $match['shoot'];

            array_push($matches,$match);
        }

        $runInfo = [
            ["key"=>"static","name"=>"静止","value"=>30],
            ["key"=>"low","name"=>"低速","value"=>40],
            ["key"=>"middle","name"=>"中速","value"=>20],
            ["key"=>"heigh","name"=>"高速","value"=>10]
        ];
        $matchInfo = [
            'totalPass' => $totalPass,
            'totalRun'  => $totalRun,
            'totalShoot'=> $totalShoot,
            'matches'   => $matches
        ];

        $suggestion = "作为阿根廷国家队主力左后卫，随着世界杯的进行，罗霍的身价也大幅度上涨。我记得我世界杯之前买入的时候连1W都不到，如今+1的已经8W8了，接近9W了。罗霍本届大赛发挥不错，有希望之后转会豪门，若是如此，想必10W的价格一点不贵。";

        return apiData()->set_data('runInfo',$runInfo)->set_data('matchInfo',$matchInfo)->set_data('suggestion',$suggestion)->send();
    }

    /*
     * 是否有未完成的比赛
     *
     * */
    public function has_unfinished_match(Request $request)
    {
        $userId = intval($request->input('userId',0));
        if($userId<=0)
        {
            return apiData()->send(4001,'用户ID小于0');
        }

        $matchInfo = DB::table('match')->where('user_id',$userId)
            ->where('time_end')
            ->where('time_begin')
            ->orderBy('match_id','desc')
            ->first();

        $matchId        = 0;
        if($matchInfo)
        {
            $matchId    = $matchInfo->match_id;
        }
        return apiData()->set_data('matchId',$matchId)->send(200,'SUCCESS');
    }

    /**
     * 当前比赛
     * */
    public function current_match(Request $request)
    {
        $userId         = $request->input('userId');
        $matchModel     = new MatchModel();
        $currentMatch   = $matchModel->get_current_match($userId);

        return apiData()->set_data('matchInfo',$currentMatch)->send();
    }


    /**
     * 记录比赛的状态
     *
     * */
    public function log_match_status(Request $request)
    {
        $matchId    = $request->input('matchId');
        $status     = $request->input('status');

        $allAtatus     = ['begin','pause','continue','stop'];

        if(!in_array($status,$allAtatus))
        {
            return apiData()->send(3001,'不存在状态'.$status);
        }
        $matchModel = new MatchModel();
        $matchModel->log_match_status($matchId,$status);

        return apiData()->send();
    }





    /**
     * 重置时间
     * */
    public function reset_time(Request $request)
    {
        $matchId    = $request->input('matchId');
        $type       = $request->input('type');
        $foot       = $request->input('foot');
        $userId     = $request->input('userId');


        $where      = [
            'match_id'  => $matchId,
            'type'      => "",
            "foot"      => $foot,
        ];

        $table      = "user_".$userId."_".$type;

        $db1         = DB::connection('matchdata')->table($table)->where('match_id',$matchId)->where('foot',$foot);
        $db2         = DB::connection('matchdata')->table($table)->where('match_id',$matchId)->where('foot',$foot);

        $first      = $db1->where('type','')->orderBy('id')->first();
        $last       = $db2->where('type','')->orderBy('id','desc')->first();

        $times      = $last->timestamp - $first->timestamp;

        $allNum     = DB::connection('matchdata')->table($table)->where($where)->count();
        $perTime    = $times/$allNum;

        $allData    = DB::connection('matchdata')->table($table)->where($where)->select('id')->orderBy('id')->get();

        foreach($allData as $key => $d)
        {

            $newTime    = $perTime * $key + $first->timestamp;
            DB::connection('matchdata')->table($table)->where('id',$d->id)->update(['timestamp'=>$newTime]);

        }



        return 'ok';

    }





    public function zhangweixi(Request $request)
    {
        //如果是最后一条，判断是否结束
        $num = "12156.9032234";
        return gps_to_gps($num);
        $job    = new AnalysisMatchData();
        $job->create_compass_data(318);
    }

    public function gps(Request $request)
    {
        $matchId    = $request->input('matchId');
        $matchModel = new MatchModel();
        $matchInfo  = $matchModel->get_match_detail($matchId);
        $data       = [];
        $type       = $request->input('type','gps');

        DB::connection('matchdata')
            ->table('user_'.$matchInfo->user_id."_gps")
            ->where('match_id',$matchId)
            ->orderBy('id')
            ->chunk(1000,function($gpsList) use (&$data,$type)
            {
                if($type == 'data')
                {
                    foreach($gpsList as $gps)
                    {
                        array_push($data,$gps->source_data);
                    }

                }else{

                    foreach($gpsList as $key =>  $single)
                    {
                        //时间（16）长度（8）数据部分（n）
                        $single     = $single->source_data;//dd($single);
                        //$time       = substr($single,0,16);
                        $gps        = substr($single,24);   //数据部分起始
                        $gps        = strToAscll($gps);
                        array_push($data,$gps);
                    }
                }

            });
        dd($data);
        return $data;
    }

    /*
     * 检查同步时间
     *
     * */
    public function check_t_time(Request $request)
    {
        $userId     = $request->input('userId');
        $matchId    = $request->input('matchId');
        $foot       = $request->input('foot');
        $type       = $request->input('type');

        $data       = [];
        $table      = "user_".$userId."_".$type;

        DB::connection('matchdata')->table($table)
            ->select('id','timestamp')
            ->where('foot',$foot)
            ->where('match_id',$matchId)
            ->where('type',"T")
            ->orderBy('id')
            ->chunk(1000,function($res) use($table,$matchId,$foot,&$data) {
                $f = public_path('logs/my.txt');

               //获取前一条数据
               foreach($res as $d)
               {
                    $prev = DB::connection('matchdata')
                        ->table($table)
                        ->select('timestamp')
                        ->where('match_id',$matchId)
                        ->where('foot',$foot)
                        ->where('id',"<",$d->id)
                        ->where('type',"")
                        ->orderBy('id','desc')
                        ->first();

                    if($prev)
                    {
                        $dis    = $d->timestamp-$prev->timestamp;
                        file_put_contents($f,$d->timestamp."-".$prev->timestamp."=".$dis."\n",FILE_APPEND);

                    }else{
                        file_put_contents($f,$d->timestamp."\n",FILE_APPEND);

                    }
               }
        });

        return 'ok';
        //return $data;
    }

}






