<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\V1\MatchModel;
use Illuminate\Support\Facades\Redis;
use DB;
use App\Jobs\AnalysisMatchData;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;



class MatchController extends Controller
{
    public function __construct()
    {
        ini_set ('memory_limit', '500M');
        set_time_limit(300);
    }


    public function create_table($userId,$type)
    {

        /*gps_id
        match_id
        latitude
        longitude
        speed
        direction
        status
        data_key
        source_data
        created_at
        source_id
        timestamp*/

        $table  = "user_".$userId."_".$type;

        $hasTable = Schema::connection('matchdata')->hasTable($table);

        if($hasTable)
        {
            return true;
        }

        if($type == 'gps')
        {
            Schema::connection('matchdata')->create($table,function (Blueprint $table){

                $table->increments('id');
                $table->integer('match_id');
                $table->string('latitude');
                $table->string('longitude');
                $table->double('speed');
                $table->string('direction');
                $table->tinyInteger('status');
                $table->string('source_data');
                $table->bigInteger('timestamp');
                $table->dateTime('created_at');
            });

        } else {

            /*sensor_id
            match_id
            x
            y
            z
            data_key
            source_data
            created_at
            source_id
            type
            timestamp*/

            Schema::connection("matchdata")->create($table,function(Blueprint $table){

                $table->increments('id');
                $table->integer('source_id');
                $table->integer('match_id');
                $table->double('x');
                $table->double('y');
                $table->double('z');
                $table->string('type');
                $table->string('source_data');
                $table->bigInteger('timestamp');
                $table->dateTime('created_at');
            });
        }
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

        //数据文件存储在磁盘中
        $date   = date('Y-m-d');
        $time   = date('His');
        $file   = $date."/".$userId."-".$dataType.'-'.$time.".txt";//文件格式

        Storage::disk('local')->put($file,$deviceData);

        $matchData  = [
            'match_id'  => 0,
            'user_id'   => $userId,
            'device_sn' => $deviceSn,
            'type'      => $dataType,
            'data'      => $file,
            'foot'      => $foot
        ];

        //1.储存数据
        $matchModel     = new MatchModel();
        $sourceId       = $matchModel->add_match_source_data($matchData);


        //如果是最后一条，判断是否结束
        //$request->offsetSet('sourceId',$sourceId);
        //$this->handle_data($request);


        //2.开始解析数据
        $job    = new AnalysisMatchData($sourceId);
        $job->handle();

        //数据存储完毕，调用MATLAB系统开始计算
        //$delayTime      = now()->addSecond(2);
        //AnalysisMatchData::dispatch($sourceId)->delay($delayTime);

        //创建json文件  请求matlab来读取分析
        //$this->create_json($matchId);


        return apiData()->send(200,'ok');
    }


    public function test_match(Request $request){
        //数据存储完毕，调用MATLAB系统开始计算
        $sourceId = $request->input('sourceId');
        $delayTime      = now()->addSecond(2);
        AnalysisMatchData::dispatch($sourceId)->delay($delayTime);
        return apiData()->send(200,'ok');
    }


    public function jiexi(Request $request){


        //数据存储完毕，调用MATLAB系统开始计算
        $sourceId = $request->input('sourceId');

        //2.开始解析数据
        $job    = new AnalysisMatchData($sourceId);
        $job->handle1();
        //mylogger("相应前端".time());
        return apiData()->send(200,'ok');
    }

    public function sensortest()
    {
        return hexToInt("f9ffffff");

        $str = explode(',',$str);
        foreach($str as $k => $s)
        {
            //dd(pack(bin2hex($s));
        }
        return hexToInt("a1000000");
    }
    /**
     *
     * */
    public function gps_to_bdmap(Request $request)
    {
        $matchId    = $request->input('matchId');
        $file       = "match/result-".$matchId."-gps.json";
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
            if($lats[$i]== '') continue;

            array_push($points,['lat'=>$this->gps_to_gps($lons[$i]),'lon'=>$this->gps_to_gps($lats[$i])]);
        }

        //Storage::disk('web')->put("match/middle-".$matchId.".json",\GuzzleHttp\json_encode($points));


        $points = array_chunk($points,100);
        $ak     = "zZSGyxZgUytdiKG135BcnaP6";
        foreach($points as $key => $pointArr)
        {
            $tempArr = [];
            foreach($pointArr as $point)
            {
                array_push($tempArr,implode(',',$point));
            }

            $tempArr = implode(";",$tempArr);

            $url = "http://api.map.baidu.com/geoconv/v1/?coords={$tempArr}&from=1&to=5&ak={$ak}";
            $tempArr = file_get_contents($url);
            $tempArr = \GuzzleHttp\json_decode($tempArr);

            $points[$key] = $tempArr->result;

        }

        Storage::disk('web')->put('match/bd-'.$matchId.".json",\GuzzleHttp\json_encode($points));

        return $points;


        dd($points);
    }

    public function gps_to_gps($num)
    {
        bcscale (8);
        $num = bcdiv($num,100);
        $int = (int)$num;
        $flo = bcmul(bcdiv(bcmod($num,1),60),100);
        return bcadd($int,$flo);
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
     * 生产json文件
     * */
    public function create_json($matchId)
    {
        //将一定时间内的数据提取出来 生成json文件
        $GLOBALS['sensorData']  = [
            'ax'    => [],
            'ay'    => [],
            'az'    => [],
            'gx'    => [],
            'gy'    => [],
            'gz'    => []
        ];

        DB::table('match_sensor')
            ->where('match_id',$matchId)
            ->where('data_key',1)
            ->select('x','y','z','type')
            ->orderBy('sensor_id')
            ->chunk(1000,function($sensors)
            {
                foreach($sensors as $sensor)
                {
                    if($sensor->type == 'A')
                    {
                        array_push($GLOBALS['sensorData']['ax'],$sensor->x);
                        array_push($GLOBALS['sensorData']['ay'],$sensor->y);
                        array_push($GLOBALS['sensorData']['az'],$sensor->z);

                    }else{

                        array_push($GLOBALS['sensorData']['gx'],$sensor->x);
                        array_push($GLOBALS['sensorData']['gy'],$sensor->y);
                        array_push($GLOBALS['sensorData']['gz'],$sensor->z);

                    }
                }
            });

        file_put_contents('sensor.json',\GuzzleHttp\json_encode($GLOBALS['sensorData']));
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

        $map        = create_round_array(20,32);
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
                'disAvg'    => $matchResult->shoot_dis_avg
            ],
            'passShort'    => [
                'speedMax'  => $matchResult->pass_s_speed_max,
                'speedAvg'  => $matchResult->pass_s_speed_vag,
                'disMax'    => $matchResult->pass_s_dis_max,
                'disAvg'    => $matchResult->pass_s_dis_avg,
                'number'    => $matchResult->pass_s_num
            ],
            'passLength'    => [
                'speedMax'  => $matchResult->pass_l_speed_max,
                'speedAvg'  => $matchResult->pass_l_speed_vag,
                'disMax'    => $matchResult->pass_l_dis_max,
                'disAvg'    => $matchResult->pass_l_dis_avg,
                'number'    => $matchResult->pass_l_num
            ],

            'run'        => [
                'lowDis'       => $matchResult->run_low_dis,
                'lowTime'      => $matchResult->run_low_time,
                'midDis'       => $matchResult->run_mid_dis,
                'midTime'      => $matchResult->run_mid_time,
                'highDis'       => $matchResult->run_high_dis,
                'highTime'      => $matchResult->run_high_time
            ],
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
        $midSpeed   = create_round_array(20,23);
        $heighSpeed = create_round_array(20,23);
        $sprint     = create_round_array(20,23);
        $shortPass  = create_round_array(20,23);
        $longPass   = create_round_array(20,23);
        $rob        = create_round_array(20,23);
        $dribble    = create_round_array(20,23);

        $maps       = [
            ['name'=>"中速跑动",'data'=>$midSpeed],
            ['name'=>"高速跑动",'data'=>$heighSpeed],
            ['name'=>'冲刺','data'=>$sprint],
            ['name'=>'短运','data'=>$shortPass],
            ['name'=>'长运','data'=>$longPass],
            ['name'=>'抢断','data'=>$rob],
            ['name'=>'带球','data'=>$dribble]
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


    public function get_pitch(Request $request)
    {
        $matchModel = new MatchModel();
        $matchId    = $request->input('matchId');
        $matchInfo  = $matchModel->get_match_detail($matchId);

        $compassTable   = "user_".$matchInfo->user_id."_compass";
        $sensorTable    = "user_".$matchInfo->user_id."_sensor";


        DB::connection('matchdata')
            ->table($compassTable)
            ->where('match_id',$matchId)
            ->orderBy('id')
            ->chunk(1000,function($compasses) use($sensorTable,$matchId)
        {
            foreach($compasses as $compass)
            {
                $timestamp = $compass->timestamp;

                $sensor = DB::connection("matchdata")
                    ->table($sensorTable)
                    ->where("match_id",$matchId)
                    ->where('timestamp',">=",$timestamp)
                    ->where('type','A')
                    ->orderBy('id')
                    ->first();

                $info = [
                    "path"  => "/home/zhangweixi/compass_to_gps/app",
                    "ax"    => $sensor->x,
                    "ay"    => $sensor->y,
                    "az"    => $sensor->x,
                    "cx"    => $compass->x,
                    "cy"    => $compass->y,
                    "cz"    => $compass->x
                ];

                system("/home/zhangweixi/compass_to_gps/app -88 -19 1009 -0.013671875 -0.26985546946525574 -0.32883575558662415");
                $command = implode(" ",$info);
                //dd($command);
                //$command = "pwd";
                $result  = exec($command,$arr);
                var_dump($result);
                var_dump($arr);
                exit();
            }


        });
    }
}






