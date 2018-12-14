<?php
namespace App\Http\Controllers\Service;
use App\Common\Http;
use App\Http\Controllers\Controller;
use App\Models\Base\BaseMatchModel;
use App\Models\V1\CourtModel;
use App\Models\V1\MatchModel;
use Dingo\Api\Http\Request;
use App\Jobs\AnalysisMatchData;
use DB;
use App\Http\Controllers\Service\Court;



class MatchCaculate extends Controller
{

    public function __construct()
    {
        
    }

    /**
     * 解析比赛数据
     * */
    public function jiexi_match(Request $request)
    {
        $matchId    = $request->input('matchId');
        $types      = ['compass','sensor','gps'];
        $foots      = ["R","L"];

        //1.将之前的数据设置为未解析状态
        DB::table('match_source_data')->where('match_id',$matchId)->update(['status'=>0]);


        //2.将数据进度设置为未解完
        $data = ["gps_L"=>0,"gps_R"=>0,"sensor_L"=>0,"sensor_R"=>0,"compass_L"=>0,"compass_R"=>0];
        DB::table('match_data_process')->where('match_id',$matchId)->update($data);

        foreach($types as $type)
        {
            foreach($foots as $foot)
            {
                if($type == 'gps' && $foot == 'R'){

                    continue;
                }

                $condition      = ['match_id'=>$matchId,'type'=>$type,'foot'=>$foot];
                $data         = DB::table('match_source_data')->where($condition)->orderBy('match_source_id')->first();

                $host           = "http://".$request->getHost();
                $data           = ['sourceId'=>$data->match_source_id,'jxNext'=>true];
                $delayTime      = now()->addSecond(1);
                AnalysisMatchData::dispatch("parse_data",$data)->delay($delayTime);
            }
        }

        return apiData()->send();
    }


    /**
     * 解析单条数据，不传递
     * */
    public function jiexi(Request $request)
    {
        //数据存储完毕，调用MATLAB系统开始计算
        $sourceId = $request->input('matchSourceId');


        //2.开始解析数据
        $job    = new AnalysisMatchData('parse_data',['sourceId'=>$sourceId]);

        $job->handle();
        //mylogger("相应前端".time());
        return apiData()->send(200,'ok');
    }


    /**
     * 解析单条数据
     * 调用matlab解析数据
     * @param $request Request
     * @return string
     * */
    public function jiexi_single_data(Request $request)
    {
        $matchSourceId  = $request->input('matchSourceId',0);
        $dataInfo       = DB::table('match_source_data')->where('match_source_id',$matchSourceId)->first();
        if($dataInfo->status == 0)
        {
            $delayTime      = now()->addSecond(1);
            $data           = ['sourceId'=>$matchSourceId,'jxNext'=>true];
            AnalysisMatchData::dispatch('parse_data',$data)->delay($delayTime);
        }
        return apiData()->send();
    }


    /**
     * 结束解析数据
     * @param $request Request
     * @return string
     * */
    public function finish_parse_data(Request $request)
    {

        $matchId    = $request->input('matchId');
        $matchInfo  = MatchModel::find($matchId);
        mylogger("数据解析完毕");
        BaseMatchModel::match_process($matchId,"数据解析完毕");

        if(!self::check_has_gps($matchId))
        {
            (new AnalysisMatchData(''))->caculate_angle($matchId);
            jpush_content("比赛通知","GPS数据量不足,无法进行计算",4001,1,$matchInfo->user_id,['matchId'=>$matchId]);
            return "GPS invalid";
        }

        $delayTime  = now()->addSecond(1);
        $data       = ['matchId'=>$matchId];
        AnalysisMatchData::dispatch("finish_parse_data",$data)->delay($delayTime);
        BaseMatchModel::match_process($matchId,"比赛队列设置成功");
        return apiData()->send();
    }

    /**
     * 检查是否有GPS
     * @param $matchId
     * @return boolean
     * */
    public static function check_has_gps($matchId){

        //如果GPS全部为空，则不进行运算
        $gpsFile    = matchdir($matchId)."gps-L.txt";

        $gpsArr     = file_to_array($gpsFile);
        $num        = 0;

        foreach($gpsArr as $gps){

            if(floatval($gps[0]) > 0){

                $num++;

                if($num > 100){

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 创建罗盘和sensor的文件
     * */
    public function create_compass_sensor(Request $request)
    {
        $matchId        = $request->input('matchId',0);
        $foot           = $request->input('foot','');
        $delayTime      = now()->addSecond(1);
        $data           = ['matchId'=>$matchId,'foot'=>$foot];
        AnalysisMatchData::dispatch("create_compass_sensor",$data)->delay($delayTime);

        return apiData()->send();
    }




    /**
     * 调用角度转换
     * */
    public function compass_translate(Request $request)
    {
        $infile  = $request->input('infile');
        $outfile = $request->input('outfile');
        $matchId = $request->input('matchId');


        $job        = new AnalysisMatchData();

        //创建角度转换文件
        $job->compass_translate($infile,$outfile);

        return apiData()->send();
    }


    /**
     * 创建gps热点图
     * */
    public function create_gps_map(Request $request)
    {
        $matchId    = $request->input('matchId');
        $foot       = $request->input('foot');
        $queue      = $request->input('needQueue',1);

        if($queue == 0)
        {
            $analysis   = new AnalysisMatchData("create_gps_map");
            $analysis->create_gps_map($matchId,$foot);
            return "ok";
        }



        $delayTime      = now()->addSecond(1);
        $data           = ['matchId'=>$matchId,'foot'=>$foot];
        AnalysisMatchData::dispatch("create_gps_map",$data)->delay($delayTime);


    }

    /**
     * 解析时间
     * */
    public function sensortest(Request $request)
    {
        //return hexToInt("f9ffffff");
        $time   = $request->input('time');
        return hexdec(reverse_hex($time));

        $str = explode(',',$str);
        foreach($str as $k => $s)
        {
            //dd(pack(bin2hex($s));
        }
        return hexToInt("a1000000");
    }





    /**
     * 调用算法系统
     * */
    public function run_matlab(Request $request)
    {
        $matchId        = $request->input('matchId');
        $data           = ['matchId'=>$matchId];

        //(new AnalysisMatchData("run_matlab"))->run_matlab($matchId);

        $delayTime      = now()->addSecond(1);
        AnalysisMatchData::dispatch('run_matlab',$data)->delay($delayTime);
        mylogger('计算任务设置成功');
        return apiData()->send();
    }


    /**
     * 读取数据并保存到结果中
     * */
    public function save_matlab_result(Request $request)
    {
        $matchId        = $request->input('matchId');
        $result         = $request->input('result');

        if($result == "FAIL")
        {
            //算法调用失败，使用微信通知我


        }else{

            //return (new AnalysisMatchData(''))->save_matlab_result($matchId);

            $delayTime      = now()->addSecond(1);
            AnalysisMatchData::dispatch('save_matlab_result',['matchId'=>$matchId])->delay($delayTime);
        }

        return apiData()->send();
    }


    /**
     * 调用处理球场
     * @param $courtId integer 球场ID
     * */
    static function call_matlab_court_init($courtId)
    {
        $host   = str_replace("http://","",config("app.matlabhost"));
        $path   = "/api/matchCaculate/call_matlab_court_action?courtId=".$courtId;
        Http::sock($host,$path);
    }


    /**
     * MATLAB服务器执行,建立球场模型
     * 调用matlab生成去球场的几个顶点坐标
     * @param $request Request
     * */
    public function call_matlab_court_action(Request $request)
    {
        $courtId    = $request->input('courtId');
        //创建输入文件
        $srcFile    = Court::create_court_model_input_file($courtId);

        //调用matlab
        $dir        = public_path("uploads/court-config/{$courtId}/");
        $inputFile  = "border-src.txt";         //边框数据
        $outFile    = "border-dest.txt";        //顶点数据

        $pythonFile = app_path("python/python_call_matlab.py");
        $matlabCmd  = "Stadium('{$dir}','{$inputFile}','{$outFile}')";//matlab执行的命令

        $command = "python $pythonFile --command=$matlabCmd";
        mylogger("分析球场数据:".$command);
        if(!file_exists($dir.$inputFile))
        {

            mylogger($dir.$inputFile."不存在");
            exit;
        }

        $result     = shell_exec($command);

        $colums = [
            "A"     => 'p_a',
            "B"     => 'p_b',
            "C"     => 'p_c',
            "D"     => 'p_d',
            "E"     => 'p_e',
            "F"     => 'p_f',
            "Sym_A" => 'p_a1',
            "Sym_B" => 'p_b1',
            'Sym_C' => 'P_c1',
            "Sym_D" => 'p_d1'
        ];

        //读取结果，存储到数据中
        $courtResult    = file_to_array($dir.$outFile);
        $courtInfo      = [];

        $positions      = array_keys($colums);
        foreach($courtResult as $point)
        {
            $position   = $point[0];

            if(in_array($position,$positions))
            {
                $key                = $colums[$point[0]];
                $courtInfo[$key]    = $point[1].",".$point[2];
            }
        }



        //判断球场是否是顺时针
        $pa     = explode(",",$courtInfo['p_a']);
        $pd     = explode(",",$courtInfo['p_d']);
        $pe     = explode(",",$courtInfo['p_e']);
        $pa1    = explode(",",$courtInfo['p_a1']);

        $PA     = new GPSPoint($pa[0],$pa[1]);
        $PD     = new GPSPoint($pd[0],$pd[1]);
        $PE     = new GPSPoint($pe[0],$pe[1]);
        $PA1    = new GPSPoint($pa1[0],$pa1[1]);

        $isClockWise                = Court::judge_court_is_clockwise($PA,$PD,$PE);;
        $courtInfo['is_clockwise']  = $isClockWise ? 1 : 0;

        $courtInfo['width']     = round(gps_distance($PA->lon,$PA->lat,$PD->lon,$PD->lat),2);
        $courtInfo['length']    = round(gps_distance($PA->lon,$PA->lat,$PA1->lon,$PA1->lat),2);

        CourtModel::where('court_id',$courtId)->update($courtInfo);

        //球场解析结束
        mylogger("球场解析成功,courtId:".$courtId);

    }



}