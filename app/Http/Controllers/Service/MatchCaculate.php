<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/27
 * Time: 13:35
 */

namespace App\Http\Controllers\Service;
use App\Http\Controllers\Controller;
use Dingo\Api\Http\Request;
use App\Jobs\AnalysisMatchData;
use DB;


class MatchCaculate extends Controller
{

    public function __construct()
    {
        ini_set ('memory_limit', '500M');
        set_time_limit(300);
    }


    /**
     * 解析单条数据，不传递
     * */
    public function jiexi(Request $request)
    {
        //数据存储完毕，调用MATLAB系统开始计算
        $sourceId = $request->input('sourceId');


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
            $host           = $request->getHost();
            $data           = ['sourceId'=>$matchSourceId,'host'=>$host];
            AnalysisMatchData::dispatch('parse_data',$data)->delay($delayTime);
        }
        return apiData()->send();
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


        //计算角度
        //$job->compass_translate()

        //调用算法系统


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
     * 解析比赛数据
     * */
    public function jiexi_match(Request $request)
    {
        $matchId    = $request->input('matchId');
        $dataes     = DB::table('match_source_data')->where('match_id',$matchId)->get();

        foreach($dataes as $key=> $data)
        {
            $delayTime      = now()->addSecond(3*$key);
            AnalysisMatchData::dispatch($data->match_source_id,$request->getHost())->delay($delayTime);
        }

        return apiData()->send();
    }


    /**
     * 调用算法系统
     * */
    public function call_matlab(Request $request)
    {
        $matchId        = $request->input('matchId');
        $data           = ['matchId'=>$matchId];

        (new AnalysisMatchData("run_matlab"))->run_matlab($matchId);

        //$delayTime      = now()->addSecond(1);
        //AnalysisMatchData::dispatch('run_matlab',$data)->delay($delayTime);

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

            return (new AnalysisMatchData(''))->save_matlab_result($matchId);

            $delayTime      = now()->addSecond(1);
            AnalysisMatchData::dispatch('save_matlab_result',['matchId'=>$matchId])->delay($delayTime);
        }

        return apiData()->send();
    }


}