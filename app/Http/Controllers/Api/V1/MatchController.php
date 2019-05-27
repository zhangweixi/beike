<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Base\BaseMatchDataProcessModel;
use App\Models\Base\BaseMatchModel;
use App\Models\Base\BaseMatchResultModel;
use App\Models\Base\BaseMatchSourceDataModel;
use App\Models\Base\BaseMatchUploadProcessModel;
use App\Models\Base\BaseUserAbilityModel;
use App\Models\V1\CourtModel;
use App\Models\V1\UserModel;
use Illuminate\Contracts\Support\Responsable;
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
        set_time_limit(300);
    }



    /**
     * 开始比赛
     * */
    public function add_match(Request $request)
    {
        return $this->create_match($request);
    }


    /**
     * 创建比赛
     *
     * */
    public function create_match(Request $request)
    {
        $courtModel = new CourtModel();
        $courtId    = $request->input('courtId',0);
        $userId     = $request->input('userId',0);
        $lat        = $request->input('latitude',0);
        $lon        = $request->input('longitude',0);
        $deviceVer  = $request->input('deviceVer',"");

        $courtId    = $courtId > 0 ? $courtId : $courtModel->add_empty_court($userId);

        $matchInfo  = [
            'user_id'   => $userId,
            'court_id'  => $courtId,
            'device_ver'=> $deviceVer
        ];

        //检查是否有未结束的比赛
        $oldMatch = DB::table('match')->where('user_id',$userId)
            ->where('time_end')
            ->orderBy('match_id','desc')
            ->first();
        if($oldMatch){
            return apiData()->send(2004,"您还有未结束的比赛");
        }

        if(config('app.env') == 'production'){

            //$weather    = get_weather($lat,$lon);
            //$matchInfo  = array_merge($matchInfo,$weather);
        }

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
        $matchInfo  = $matchModel->get_match_detail($matchId);
        $userId     = $matchInfo->user_id;

        $matchModel->finish_match($matchId);    //结束比赛
        $matchModel->log_match_status($matchId,'stop'); //操作标记

        $matchTime  = $this->get_matching_time($matchId);   //计算时间
        $matchModel->update_match($matchId,['time_length'=>$matchTime]);

        //初始化数据解析进度表
        BaseMatchDataProcessModel::create(['match_id'=>$matchId]);


        //初始化个人能力表
        if(BaseUserAbilityModel::find($userId) == null)
        {
            BaseUserAbilityModel::create(['user_id'=>$userId]);
        }


        //初始化比赛结果表
        BaseMatchResultModel::create(['match_id'=>$matchId]);

        return apiData()->send(200,"success");
    }

    /**
     * 获得比赛时间
     * @param $matchId integer 比赛ID
     * @return integer
     * */
    private function get_matching_time($matchId)
    {
        //计算比赛时间
        $matchStatus    = DB::table('match_status')->where('match_id',$matchId)->get();
        $statusNum      = count($matchStatus);
        $time           = 0;

        if($statusNum%2 == 0) //正常结束
        {
            for($i=0;$i<$statusNum;$i=$i+2)
            {
                $timeBegin = strtotime($matchStatus[$i]->created_at);
                $timeEnd   = strtotime($matchStatus[$i+1]->created_at);
                $time      = $time + ($timeEnd - $timeBegin);
            }

        }elseif($statusNum >=2 ){

            $time       = strtotime($matchStatus[$statusNum-1]->created_at) - strtotime($matchStatus[0]->created_at);
        }

        return $time;
    }


    /**
     * 上传比赛数据
     * @param $request Request
     * @return Responsable
     * */
    public function upload_match_data(Request $request)
    {
        $userId     = $request->input('userId',0);
        $deviceSn   = $request->input('deviceSn','');
        $deviceData = $request->input('deviceData','');
        $dataType   = $request->input('dataType');
        $foot       = $request->input('foot');
        $isFinish   = $request->input('isFinish',0);
        $matchId    = $request->input('matchId',0);

        if($matchId == 0){

            $lastMatch  = MatchModel::user_last_match($userId);
            $matchId    = $lastMatch->match_id;
        }

        //数据校验  以防客户端网络异常导致数据上传重复
        $checkCode  = crc32($deviceData);
        $hasFile    = BaseMatchSourceDataModel::check_has_save_data($userId,$checkCode);

        if($hasFile)
        {
            return apiData()->send(2001,'数据重复上传');
        }

        //数据文件存储在磁盘中
        $date   = date('Y-m-d');
        $time   = create_member_number();
        $file   = $date."/".$userId."/".$dataType.'-'.$foot.'-'.$time.".txt";//文件格式

        Storage::disk('local')->put($file,$deviceData);

        $matchData  = [
            'match_id'  => $matchId,
            'user_id'   => $userId,
            'device_sn' => $deviceSn??"",
            'type'      => $dataType,
            'data'      => $file,
            'foot'      => $foot,
            'is_finish' => $isFinish,
            'status'    => 0,
            'check_code'=> $checkCode
        ];

        //1.储存数据
        $matchModel     = new MatchModel();
        $sourceId       = $matchModel->add_match_source_data($matchData);

        //设置队列，尽快解析本条数据
        $delayTime      = now()->addSecond(1);
        $data           = ['sourceId'=>$sourceId,'jxNext'=>true];
        AnalysisMatchData::dispatch("parse_data",$data)->delay($delayTime);

        $client     = $request->header('Client-Type');
        $version    = $request->header("Client-Version");

        //IOS版本1.2才支持
        if($client != "IOS" || intval(str_replace(".","",$version)) >= 12){

            BaseMatchUploadProcessModel::update_process($userId,!!$isFinish); //更新数据上传记录

            $isFinish = BaseMatchUploadProcessModel::check_upload_finish($userId,true);

            if($isFinish == true){ //传输已完成 , 加入到计算监控中

                BaseMatchModel::join_minitor_match($request->input('matchId'));
            }
        }
        
        return apiData()->send(200,'ok');
    }



    /**
     * 添加心情
     * */
    public function add_mood(Request $request)
    {
        $data   = [
            'mood'      => emoji_text_encode($request->input('mood')),
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


        $matchResult     = BaseMatchResultModel::find($matchId);
        
        if($matchResult) {

            if($matchResult->map_gps_run)
            {
                $map                = \GuzzleHttp\json_decode($matchResult->map_gps_run);
                $map                = data_scroll_to($map,100);

            }else{

                $map                = create_round_array(20,32,true,0);
            }

            $matchInfo->shoot   = $matchResult->grade_shoot ?? 0;
            $matchInfo->pass    = $matchResult->grade_pass ?? 0;
            $matchInfo->strength= $matchResult->grade_strength ?? 0;
            $matchInfo->dribble = $matchResult->grade_dribble ?? 0;
            $matchInfo->defense = $matchResult->grade_defense ?? 0;
            $matchInfo->run     = $matchResult->grade_run ?? 0;

        }else{//默认值

            $map                = create_round_array(20,32,true,0);
            $matchInfo->shoot   = 0;
            $matchInfo->pass    = 0;
            $matchInfo->strength= 0;
            $matchInfo->dribble = 0;
            $matchInfo->defense = 0;
            $matchInfo->run     = 0;
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
                'speedMax'  => speed_second_to_hour($matchResult->shoot_speed_max),
                'speedAvg'  => speed_second_to_hour($matchResult->shoot_speed_avg),
                'disMax'    => $matchResult->shoot_dis_max,
                'disAvg'    => $matchResult->shoot_dis_avg,
                'number'    => $matchResult->shoot_num_total,
            ],
            'passShort'    => [
                'speedMax'  => speed_second_to_hour($matchResult->pass_s_speed_max),
                'speedAvg'  => speed_second_to_hour($matchResult->pass_s_speed_avg),
                'disMax'    => $matchResult->pass_s_dis_max/1000,
                'disAvg'    => $matchResult->pass_s_dis_avg/1000,
                'number'    => $matchResult->pass_s_num
            ],
            'passLength'    => [
                'speedMax'  => speed_second_to_hour($matchResult->pass_l_speed_max),
                'speedAvg'  => speed_second_to_hour($matchResult->pass_l_speed_avg),
                'disMax'    => $matchResult->pass_l_dis_max/1000,
                'disAvg'    => $matchResult->pass_l_dis_avg/1000,
                'number'    => $matchResult->pass_l_num
            ],

            'run'        => [
                'lowDis'       => ($matchResult->run_low_dis+$matchResult->run_static_dis)/1000,
                'lowTime'      => $matchResult->run_low_time+$matchResult->run_static_time,
                'midDis'       => $matchResult->run_mid_dis/1000,
                'midTime'      => $matchResult->run_mid_time,
                'highDis'      => $matchResult->run_high_dis/1000,
                'highTime'     => $matchResult->run_high_time
            ],
            'touchball' => [
                "number"    =>  $matchResult->touchball_num,
                "speedMax"  =>  speed_second_to_hour($matchResult->touchball_speed_max),
                "speedAvg"  =>  speed_second_to_hour($matchResult->touchball_speed_avg),
                "strengthMax"=> $matchResult->touchball_strength_max,
                "strengthAvg"=> $matchResult->touchball_strength_avg
            ]
        ];

        return apiData()
            ->set_data('matchResult',$data)
            ->send(200,'success');

    }

    /**
     * 热点图转换
     * @param $map string
     * @param $needScroll boolean 是否需要缩放
     * @return array
     * */
    private static function map_change($map,$needScroll=true){
        if($map) {

            $map   = \GuzzleHttp\json_decode($map,true);

            if($needScroll){

                $map   = data_scroll_to($map,100);
            }
        }else{

            $map   = create_round_array(12,22,true);
        }
        return $map;
    }

    /**
     * 比赛热点图
     * */
    public function match_detail_hotmap(Request $request)
    {
        $matchId        = $request->input('matchId');
        $matchResult    = BaseMatchResultModel::find($matchId);

        $lowSpeed       = self::map_change($matchResult->map_speed_low);
        $midSpeed       = self::map_change($matchResult->map_speed_middle);
        $highSpeed      = self::map_change($matchResult->map_speed_high);

        $shortPass      = self::map_change($matchResult->map_pass_short,false);
        $longPass       = self::map_change($matchResult->map_pass_long,false);
        $touchball      = self::map_change($matchResult->map_touchball,false);
        $shoot          = self::map_change($matchResult->map_shoot,false);


        //$sprint     = create_round_array(12,22);
        //$rob        = create_round_array(12,22);
        //$dribble    = create_round_array(12,22);


        $maps       = [
            ['name'=>"射门",'data'=>$shoot,'type'=>'point'],
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
        $totalPass  = 0;
        $totalShoot = 0;
        $totalRun   = 0;

        $sql    = "SELECT 
                        a.match_id,
                        b.shoot_num_total as shoot,
                        b.pass_s_num + b.pass_l_num as pass,
                        b.run_low_dis,
                        b.run_mid_dis,
                        b.run_high_dis,
                        b.run_static_dis,
                        b.run_low_dis + b.run_mid_dis +  b.run_high_dis + b.run_static_dis as run
                  FROM `match` as a 
                  LEFT JOIN match_result as b ON b.match_id = a.match_id 
                  WHERE a.user_id = $userId 
                  AND   a.deleted_at IS NULL 
                  ORDER BY a.match_id DESC ";


        if($number > 0)
        {
            $sql .= " LIMIT $number";
        }
        $matches = DB::select($sql);

        $speedLowDis   = 0;
        $speedMidDis   = 0;
        $speedHighDis  = 0;
        $staticDis     = 0;
        $totalNum       = count($matches);
        foreach ($matches as $key => $match)
        {
            $totalPass += $match->pass;
            $totalRun  += $match->run;
            $totalShoot+= $match->shoot;

            $speedHighDis  += $match->run_high_dis;
            $speedMidDis   += $match->run_mid_dis;
            $speedLowDis   += $match->run_low_dis;
            $staticDis     += $match->run_static_dis;

            $match->shoot   = $match->shoot ?? 0;
            $match->pass    = $match->pass ?? 0;
            $match->run     = $match->run/1000 ?? 0;
            $match->x       = $totalNum - $key;

            $match->run_low_dis = $match->run_low_dis / 1000;
            $match->run_mid_dis = $match->run_mid_dis / 1000;
            $match->run_high_dis = $match->run_high_dis / 1000;
            $match->run_static_dis = $match->run_static_dis / 1000;

        }

        //计算百分比

        $totalDis      = $speedLowDis + $speedMidDis + $speedHighDis + $staticDis;
        $totalDis      = $totalDis > 1 ? $totalDis : 1;

        $perSpeedLow    = ceil($speedLowDis / $totalDis*100);
        $perSpeedMid    = ceil($speedMidDis / $totalDis*100);
        $perSpeedHigh   = ceil($speedHighDis / $totalDis*100);
        $perStatic      = 100 - ($perSpeedHigh + $perSpeedMid + $perSpeedLow);

        $matches        = array_reverse($matches);

        $runInfo = [
            ["key"=>"static",   "name"=>"走动","value"=>$perStatic],
            ["key"=>"low",      "name"=>"低速","value"=>$perSpeedLow],
            ["key"=>"middle",   "name"=>"中速","value"=>$perSpeedMid],
            ["key"=>"heigh",    "name"=>"高速","value"=>$perSpeedHigh]
        ];

        $matchInfo = [
            'totalPass' => $totalPass,
            'totalRun'  => $totalRun/1000,
            'totalShoot'=> $totalShoot,
            'matches'   => $matches
        ];



        $suggestion = "作为阿根廷国家队主力左后卫，随着世界杯的进行，罗霍的身价也大幅度上涨。我记得我世界杯之前买入的时候连1W都不到，如今+1的已经8W8了，接近9W了。罗霍本届大赛发挥不错，有希望之后转会豪门，若是如此，想必10W的价格一点不贵。";

        return apiData()
            ->set_data('runInfo',$runInfo)
            ->set_data('matchInfo',$matchInfo)
            //->set_data('suggestion',$suggestion)
            ->send();
    }

    /**
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
     * 设置踢球位置
     * */
    public function set_position(Request $request){

        $positions  = ['CF','LMF','LB','CF','SS','AMF','CMF','DMF','CB','GK','REF','RMF','RB'];
        $position   = $request->input('position','');
        $matchId    = $request->input('matchId');

        if(!in_array($position,$positions)){

            return apiData()->send(3001,"没有该位置");
        }
        MatchModel::where('match_id',$matchId)->update(['position'=>$position]);
        return apiData()->send();
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


}






