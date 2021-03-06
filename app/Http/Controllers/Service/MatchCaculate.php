<?php
namespace App\Http\Controllers\Service;
use App\Common\Http;
use App\Http\Controllers\Controller;
use App\Models\Base\BaseFootballCourtModel;
use App\Models\Base\BaseMatchDataProcessModel;
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
        DB::table('match_data_parse_process')->where('match_id',$matchId)->update($data);

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
            jpush_content("比赛通知","GPS数据量不足,无法进行计算",4003,1,$matchInfo->user_id,['matchId'=>$matchId]);
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
        mylogger('计算任务设置成功'.$matchId);
        return apiData()->send();
    }


    /**
     * 读取数据并保存到结果中
     * */
    public function save_matlab_match_result(Request $request)
    {
        $code   = $request->input('code');
        $matchId= $request->input('id');
        $matchInfo  = (new MatchModel())->get_match_detail($matchId);

        if($code != 200)
        {
            //算法调用失败，使用微信通知我
            BaseMatchModel::match_process($matchId,"获得通知，算法计算失败");
            jpush_content("比赛结果通知","哎呀！真遗憾，比赛{$matchId}计算失败了",1002,1,$matchInfo->user_id);

        }else{

            //同步结果文件
            $files  = explode(",",$request->input('files'));
            $dir    = matchdir($matchId);
            $fileRootUrl = $request->input('fileRootUrl');
            foreach ($files as $fname)
            {
                file_put_contents($dir.$fname,file_get_contents($fileRootUrl."/".$fname));
            }
            BaseMatchModel::match_process($matchId,"获得通知，算法计算成功");
            $job    = new AnalysisMatchData();
            $job->save_matlab_result($matchId);
            //$delayTime      = now()->addSecond(1);
            //AnalysisMatchData::dispatch('save_matlab_result',['matchId'=>$matchId])->delay($delayTime);
            //通知客户端
            jpush_content("比赛通知","亲，您的比赛已经出结果啦!",4001,1,$matchInfo->user_id,['matchId'=>$matchId]);
            BaseMatchModel::match_process($matchId,"结果处理完成");
        }
        /**8.处理结果**/
        return apiData()->send();
    }

    /**
     * 存储球场结果
     * */
    public function save_matlab_court_result(Request $request){
        $id     = $request->input('id');
        $code   = $request->input('code');
        $msg    = $request->input('msg');

        if($code != 200){
            BaseMatchModel::match_process($id,"获得通知，".$msg);
        }else{
            Court::save_after_matlab_court_result($id,$request->input('datafile'));
        }

        return apiData()->send();
    }
}