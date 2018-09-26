<?php

namespace App\Jobs;

use App\Common\WechatTemplate;
use App\Http\Controllers\Service\GPSPoint;
use App\Http\Controllers\Service\Wechat;
use App\Models\Base\BaseMatchDataProcessModel;
use App\Models\Base\BaseMatchResultModel;
use App\Models\Base\BaseMatchSourceDataModel;
use App\Models\Base\BaseUserAbilityModel;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Common\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\V1\MatchModel;
use App\Models\V1\CourtModel;
use App\Http\Controllers\Service\Court;
use Exception;


class AnalysisMatchData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $tries   = 1;
    public $timeout = 80;

    private $sourceId   = 0;    //要处理的比赛的数据
    private $userId     = 0;    //用户ID
    private $action     = "";   //要调用的方法
    private $host       = "";   //当前域名，用于发起下一次网络请求
    private $infile     = "";   //转换角度的文件，转换角度的输入文件
    private $outfile    = "";   //转换角度的输出文件
    private $matchId    = 0;    //比赛ID
    private $type       = "";   //数据类型
    private $foot       = "";   //哪只脚



    public function __construct($action,$param = [])
    {
        $this->action   = $action;

        foreach($param as $key => $v)
        {
            $this->$key = $v;
        }
    }


    public function create_table($userId,$type)
    {
        $table = "user_" . $userId . "_" . $type;

        $hasTable = Schema::connection('matchdata')->hasTable($table);

        if ($hasTable) {
            return true;
        }

        if ($type == 'gps') {

            Schema::connection('matchdata')->create($table, function (Blueprint $table)
            {
                $table->increments('id');
                $table->integer('match_id');
                $table->integer('source_id');
                $table->string('type');
                $table->string('lat');
                $table->string('lon');
                $table->string('foot');
                $table->string('source_data',500);
                $table->bigInteger('timestamp');
                $table->dateTime('created_at');
            });

        } elseif($type == 'sensor') {

            Schema::connection("matchdata")->create($table, function (Blueprint $table)
            {
                $table->increments('id');
                $table->integer('source_id');
                $table->integer('match_id');
                $table->string('foot');
                $table->double('ax');
                $table->double('ay');
                $table->double('az');
                $table->double('gx')->default(0);
                $table->double('gy')->default(0);
                $table->double('gz')->default(0);
                $table->string('type');
                $table->string('source_data');
                $table->bigInteger('timestamp');
                $table->dateTime('created_at');
            });
        } elseif($type == 'compass') {

            Schema::connection("matchdata")->create($table, function (Blueprint $table)
            {
                $table->increments('id');
                $table->integer('source_id');
                $table->integer('match_id');
                $table->string('type');
                $table->string('foot');
                $table->double('x');
                $table->double('y');
                $table->double('z');
                $table->string('source_data');
                $table->bigInteger('timestamp');
                $table->dateTime('created_at');
            });
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->action)
        {
            case 'parse_data':              $this->parse_data();                                        break;

            case 'create_compass_sensor':   $this->create_compass_sensor($this->matchId,$this->foot);   break;

            case 'compass_translate':       $this->compass_translate($this->infile,$this->outfile);     break;

            case 'create_gps_map':          $this->create_gps_map($this->matchId,$this->foot);          break;

            case 'run_matlab':              $this->run_matlab($this->matchId);                          break;

            case 'save_matlab_result':      $this->save_matlab_result($this->matchId);                  break;

            case 'finish_parse_data':       $this->finish_parse_data($this->matchId);                   break;
        }
    }


    /**
     * 解析数据
     * */
    private function parse_data()
    {
        //获得数据信息
        $sourceData = BaseMatchSourceDataModel::find($this->sourceId);

        //检查本信息是否处理过
        if($sourceData->status > 0 ) {

            return true;
        }


        $type       = $sourceData->type;
        $userId     = $sourceData->user_id;
        $foot       = $sourceData->foot;
        $this->userId = $userId;

        if(!Storage::disk('local')->has($sourceData->data))
        {
            dd('文件不存在'.$sourceData->data);
            return false;
        }

        //标记处于解析状态中
        MatchModel::update_match_data($this->sourceId,['status'=>1]);

        //由于上一条数据没有解析的完毕的话会自动请求解析下一条，所以如果有未解析完毕的数据就停止执行，等待上一条结束来自动延续
        $prevSourceData = DB::table('match_source_data')
            ->where('match_source_id',"<",$this->sourceId)
            ->where('user_id',$userId)
            ->where('foot',$foot)
            ->where('type',$type)
            ->where('device_sn',$sourceData->device_sn)
            ->orderBy('match_source_id','desc')
            ->first();

        if($prevSourceData != null && $prevSourceData->status < 2)
        {
            return true;
        }


        //1.切分成单组
        $dataStr    = Storage::disk('local')->get($sourceData->data);
        $dataArr    = explode(",",$dataStr);
        $dataArr    = $this->delete_head($dataArr);
        $dataStr    = implode('',$dataArr);

        //0.创建数据表
        $this->create_table($userId,$type);
        $table      = "user_".$userId."_".$type;
        $db         = DB::connection('matchdata')->table($table);


        //从最后一条数据获得历史最新时间和比赛ID
        $lastData   = $db->orderBy('id','desc')->first();

        if($lastData){

            $matchId    = $lastData->match_id;
            $syncTime   = $lastData->timestamp;


        }else{

            $matchId    = 0;
            $syncTime   = 0;
        }

        //2.连接之前不完整的数据
        $prevData   = $this->get_prev_data($userId,$type,$foot,$matchId);
        $dataStr      = $prevData.$dataStr;


        //mylogger("开始解析:".time());
        //3.解析数据
        switch ($type)
        {
            case  "sensor": $datas = $this->handle_sensor_data($dataStr,$matchId,$syncTime);    break;
            case     "gps": $datas = $this->handle_gps_data($dataStr,$matchId);                 break;
            case "compass": $datas = $this->handle_compass_data($dataStr,$matchId,$syncTime);   break;
        }


        //mylogger("解析完毕:".time());

        $createdAt      = date_time();
        $dataBaseInfo   = [
            'source_id'     => $this->sourceId,
            'foot'          => $sourceData->foot,
            'created_at'    => $createdAt,
        ];


        //这里将数据产生了两份  一份是原始数据datas,一份是新的数据 $matchesData
        //多场数据
        $matchesData    = [];

        foreach($datas as $key=>$data)
        {
            //获得比赛场次 开始时间 结束时间  如果在两者之间 则为该场比赛的
            $matchId    = $data['match_id'];
            if(!isset($matchesData[$matchId]))
            {
                $matchesData[$matchId]  = [

                    'isFinish'  => 0,
                    'matchId'   => $matchId,
                    'data'      => []
                ];

                $file   = public_path('uploads/match/'.$matchId."/".$type."-".$foot.".txt");
                mk_dir(dirname($file));
            }


            if($data['type'] == "E")    //END 数据结束
            {
                $matchesData[$matchId]['isFinish']  = 1;    //比赛结束标记

            } elseif($data['type'] == ''){

                switch ($type)
                {
                    case "gps":     $str = [$data['lat'],   $data['lon'],$data['timestamp']];                  break;
                    case "sensor":  $str = [$data['ax'],    $data['ay'],$data['az']];       break;
                    case "compass": $str = [$data['x'],     $data['y'], $data['z']];        break;
                }

                file_put_contents($file,implode(" ",$str)."\n",FILE_APPEND);            //将数据写入到文件中

                continue;
            }

            array_push($matchesData[$matchId]['data'],array_merge($data,$dataBaseInfo));
        }


        //将数据存入到数据库中
        //如果是分批传输，则解析后的内容必须存储在数据库


        //分批插入
        foreach($matchesData as $key => $matchData)
        {
            $multyData  = array_chunk($matchData['data'],1000);

            foreach($multyData as $key => $data)
            {
                $db->insert($data);
            }
        }


        //数据解析完毕，修改标记
        MatchModel::update_match_data($this->sourceId,['status'=>2]);


        /*========本条数据解析完毕，请求解析下一条数据 begin ===*/

        $nextData = DB::table('match_source_data')
            ->where('user_id',$userId)
            ->where('foot',$foot)
            ->where('type',$type)
            ->where('status',0)
            ->where('match_source_id','>',$this->sourceId)
            ->orderBy('match_source_id')
            ->first();

        if($nextData && $this->host)
        {
            $params = ['matchSourceId'  =>  $nextData->match_source_id];
            $params = http_build_sign($params);
            $url    = $this->host."/api/matchCaculate/jiexi_single_data?".$params;
            file_get_contents($url);
        }


        /*========本条数据解析完毕，请求解析下一条数据 end =====*/


        //上传的最后一条是数据，生成航向角 生成json文件

        foreach($matchesData as $key => $matchData)
        {
            $matchId = $matchData['matchId'];

            if($matchData['isFinish'] == 0 || $matchId == 0)
            {
                continue;
            }

            //更新数据解析进度
            BaseMatchDataProcessModel::where('match_id',$matchId)->update([$type."_".$foot => 1]);

            $process   = BaseMatchDataProcessModel::find($matchId);

            //mylogger("检查数据是否解析完毕");
            //mylogger($process->gps_L .'-' .$process->sensor_L .'-'. $process->sensor_R .'-'. $process->compass_L .'-'.$process->compass_R);

            if($process->gps_L == 1 && $process->sensor_L == 1 && $process->sensor_R == 1 && $process->compass_L == 1 && $process->compass_R == 1)
            {
                //数据解析结束，同意处理后续数据
                //$this->finish_parse_data($matchId);
                //$url = config('app.apihost')."/api/matchCaculate/finish_parse_data?matchId=".$matchId;

                self::execute("finish_parse_data",["matchId"=>$matchId]);
            }
        }

        return true;
    }






    /**
     * 删除头部数据
     * @param $datas array 要处理的数据
     * @return array
     * */
    public function delete_head(array $datas)
    {
        foreach($datas as $key => $data)
        {
            $p      = strpos($data,"2c");
            $data   = substr($data,$p+2);   //删除2c及以前

            $p      = strpos($data,'2c');
            $data   = substr($data,$p+2);   //删除2c及以前

            //$data   = substr($data,2);//删除两个0

            $datas[$key] = $data;
        }

        return  $datas;
    }


    /**
     *
     * 连接上一条sensor数据
     * @param $userId integer 用户ID
     * @param $type string 数据类型
     * @param $foot string 脚
     * @return string
     * */
    private function get_prev_data($userId,$type,$foot)
    {
        $table  = "user_".$userId."_".$type;

        //将上一次未处理的数据加入到这一条中来
        $prevStr    = "";
        $lastData   = DB::connection('matchdata')
            ->table($table)
            ->where('foot',$foot)
            ->orderBy('id','desc')
            ->first();


        if($lastData && $lastData->type == "OLD" && $type != 'gps')
        {
            DB::connection('matchdata')->table($table)->where('id',$lastData->id)->delete();
            $prevStr =  $lastData->source_data;
        }


        if($lastData && $type == 'gps' && $lastData->type == "OLD" )
        {
            DB::connection('matchdata')->table($table)->where('id',$lastData->id)->delete();
            $prevStr =  $lastData->source_data;
        }

        return $prevStr;
    }



    //通用类型
    public $types   = [
        '99'    => "B",
        '88'    => "T",
        'aa'    => "P",
        'bb'    => "C",
        'cc'    => "E"
    ];


    /**
     * 读取sensor数据
     * @param $dataSource string 要解析的json数据
     * @param $matchId integer 比赛ID
     * @param $syncTime integer 同步时间
     * @return array
     * */
    private function handle_sensor_data($dataSource,$matchId,$syncTime=0)
    {
        //每一条数据的长度为20位 类型：2位 x:4,y:4,z:4, 预览:4,校验:2

        $leng       = 20;
        $dataArr    = str_split($dataSource,$leng);

        $insertData = [];



        $invalidData   = [
            'ax'            => 0,
            'ay'            => 0,
            'az'            => 0,
            'gx'            => 0,
            'gy'            => 0,
            'gz'            => 0,
            'type'          => "",
            'timestamp'     => 0,
            'source_data'   => 0,
            "match_id"      => 0
        ];

        $validDataNum       = 0;

        $content            = [
            'ax'            => 0,
            'ay'            => 0,
            'az'            => 0,
            'gx'            => 0,
            'gy'            => 0,
            'gz'            => 0,
            'data'          => ''
        ];

        $perTime    = 1000/104;
        $syncTime   = $syncTime - $perTime;
        $dataLength = count($dataArr)-1;

        foreach($dataArr as $key => $d)
        {

            $singleInsertData                   = $invalidData;
            $singleInsertData['source_data']    = $d;
            $singleInsertData['match_id']       = $matchId;

            if(strlen($d)<$leng) {


            } else {

                $type       = substr($d,0,2);
                $d          = substr($d,2);

                if($type == "01" || $type == "00"){ //正文数据

                    $d          = substr($d,0,12);
                    $single     = str_split($d,4);

                    for($i=0;$i<3;$i++)
                    {
                        $single[$i] = hexToInt($single[$i],'s');
                    }

                    list($x,$y,$z)  = $single;


                    if($type == "00"){ //后出现 $type   = "A";

                        $content['ax']      = bcdiv(bcadd(bcmul($x,488),500),1000,0);
                        $content['ay']      = bcdiv(bcadd(bcmul($y,488),500),1000,0);
                        $content['az']      = bcdiv(bcadd(bcmul($z,488),500),1000,0);
                        //$content['data']    = $singleInsertData['source_data'];

                    }
                    elseif($type == "01")
                    { //先出现 $type   = "G"; 暂时不使用

                        $content['data']    = $singleInsertData['source_data'];
                        $content['gx']      = bcadd(bcmul($x,70),0x55);
                        $content['gy']      = bcadd(bcmul($y,70),0x1d);
                        $content['gz']      = bcadd(bcmul($z,70),0x1c);

                        if($key < $dataLength){ //如果不是最后一条数据

                            continue;

                        }else{      //最后一条数据要保留

                            $content['ax']      = 0;
                            $content['ay']      = 0;
                            $content['az']      = 0;
                            $singleInsertData['type']   = "OLD";
                        }
                    }

                    //mylogger($syncTime."-".$validDataNum."*".$perTime);
                    $timestamp                  = bcadd($syncTime ,$validDataNum*$perTime,0);
                    $validDataNum++;

                    $singleInsertData['ax']     = $content['ax'];
                    $singleInsertData['ay']     = $content['ay'];
                    $singleInsertData['az']     = $content['az'];
                    //$singleInsertData['gx']     = $content['gx'];
                    //$singleInsertData['gy']     = $content['gy'];
                    //$singleInsertData['gz']     = $content['gz'];
                    //$singleInsertData['source_data']    = $content['data'];
                    $singleInsertData['timestamp']      =$timestamp;

                }elseif($type == "88" || $type == "99" || $type == 'aa' || $type == 'bb' || $type == 'cc' ) {

                    // 同步 开始 暂停 继续 结束
                    $validDataNum= 0;
                    $timestamp  = substr($d,0,16);
                    $timestamp  = HexToTime($timestamp);

                    $syncTime                       = $timestamp;


                    $type                           = $this->types[$type];
                    $singleInsertData['type']       = $type;
                    $singleInsertData['timestamp']  = $timestamp;

                    if($type == 'B' ){   //开始比赛

                        $matchId    = $this->find_match_by_time($timestamp);
                        $singleInsertData['match_id']   = $matchId;
                    }


                }else{

                    dd('数据类型错误:'.$d);
                }
            }

            //array_push($insertData, $timestamp);
            array_push($insertData,$singleInsertData);
        }

        return $insertData;
    }




    /**
     * 从数据库读取数据并解析成想要的格式
     * @param $dataSource string 原始数据
     * @param $matchId integer 比赛ID
     * @return Array
     * */
    private function handle_gps_data($dataSource,$matchId)
    {
        $dataList    = explode("23232323",$dataSource); //gps才有232323
        $dataList    = array_filter($dataList);

        $insertData     = [];

        foreach($dataList as $key =>  $single)
        {
            //时间（16）长度（8）数据部分（n）
            $timestamp  = substr($single,0,16);
            $length     = substr($single,16,8);
            $timestamp  = HexToTime($timestamp);


            if($length == "00000000" || $length == "01000000" || $length == "02000000" || $length == "03000000"){

                if($length == "00000000"){

                    $matchId    = $this->find_match_by_time($timestamp);
                }

                switch ($length)
                {
                    case "00000000": $type = "B";break;
                    case "01000000": $type = "P";break;
                    case "02000000": $type = "C";break;
                    case "03000000": $type = "E";break;
                }

                $lon    = 0;
                $lat    = 0;

            }else{

                $data       = substr($single,24);   //数据部分起始
                $data       = strToAscll($data);
                $detailInfo = explode(",",$data);
                $type       = "";

                //数据即便不合格，也不能丢弃
                if(count($detailInfo)<15) {

                    $lat    = 0;
                    $lon    = 0;

                }else{

                    $lat       = floatval($detailInfo[2]);
                    $lon       = floatval($detailInfo[4]);
                }

            }

            $otherInfo  = [
                'source_data'   => $single,
                'lat'           => $lat,
                'lon'           => $lon,
                'timestamp'     => $timestamp,
                'match_id'      => $matchId,
                'type'          => $type
            ];

            array_push($insertData,$otherInfo);
        }

        return $insertData;
    }



    /**
     * 解压罗盘数据
     * @param $dataSource string 原始数据
     * @param $matchId integer 比赛ID
     * @param $syncTime integer 同步时间
     * @return array
     * */
    private function handle_compass_data($dataSource,$matchId,$syncTime=0)
    {
        $syncTime   = $syncTime + 40;
        $leng       = 28;
        $dataArr    = str_split($dataSource,$leng);

        $insertData     = [];
        $invalidData    = [
            'x' => 0,
            'y' => 0,
            'z' => 0,
            'timestamp'     => 0,
            'source_data'   => 0,
        ];

        $validDataNum       = 0;

        foreach($dataArr as $key => $data)
        {
            $singleData     = $invalidData;
            $singleData['source_data']  = $data;
            $singleData['match_id']     = $matchId;

            if(strlen($data) < $leng)
            {
                dd("数据长度不够".$data);
                continue;
            }

            $type       = substr($data,0,2);
            $data       = substr($data,2,24);
            $data       = str_split($data,8);

            if($type == "00"){

                foreach($data as $key2 => $v2)
                {
                    $data[$key2]  = HexToFloat($v2);
                }

                $timestamp          = $syncTime + $validDataNum*40;
                $singleData['x']    = $data[0];
                $singleData['y']    = $data[1];
                $singleData['z']    = $data[2];
                $singleData['type'] = "";
                $singleData['timestamp'] = $timestamp;

                $validDataNum++;

            }elseif($type == '88' || $type == '99' || $type == 'aa' || $type == 'bb' || $type == 'cc'){

                $timestamp  = $data[0].$data[1];
                $timestamp  = HexToTime($timestamp);
                $syncTime   = $timestamp;
                $validDataNum = 0;

                if($type == "99"){

                    $matchId    = $this->find_match_by_time($timestamp);
                }

                $type       = $this->types[$type];
                $singleData['type']     = $type;
                $singleData['timestamp']= $timestamp;
                $singleData['match_id'] = $matchId;

            }else{

                dd('类型错误'. $singleData['source_data']);
            }

            array_push($insertData,$singleData);
        }


        return $insertData;
    }



    /**
     * 根据开始时间查找比赛
     * @param $beginTime string 比赛开始时间
     * */
    public function find_match_by_time($beginTime)
    {
        $beginTime  = substr($beginTime,0,10);
        $beginTime  = date('Y-m-d H:i:s',$beginTime);
        $matchInfo = DB::table('match')
            ->where('user_id',$this->userId)
            ->where('time_begin',"<=",$beginTime)
            ->where('time_end',">=",$beginTime)
            ->where('deleted_at')
            ->orderBy('match_id','desc')
            ->first();


        if($matchInfo)
        {
            return $matchInfo->match_id;
        }

        dd('无法找到比赛ID,userId:'.$this->userId.",time:".$beginTime);
    }


    /**
     * 执行方法
     * @param $method string
     * @param $params array
     * @param $host string
     * */
    static function execute($method,$params,$host='api')
    {
        $host   = $host == 'api'? config('app.apihost') : config('app.matlabhost');
        $params = http_build_query($params);
        $url    = $host."/api/matchCaculate/".$method."?".$params;
        file_get_contents($url);
    }

    /**
     * 获得比赛数据的文件夹
     * @param $matchId integer
     * @return string
     * */
    static function matchdir($matchId)
    {
        return public_path("uploads/match/{$matchId}/");
    }

    /**
     * 解析数据结束
     * @param $matchId integer 比赛ID
     * */
    public function finish_parse_data($matchId)
    {
        $matchInfo      = MatchModel::find($matchId);
        $dataDir        = self::matchdir($matchId);


        //1.与球场相关
        if($matchInfo->court_id >0 )
        {
            //1.0 生成热点图占用时间比较久，异步调用
            $params = ['matchId'=>$matchId,'foot'=>"L"];
            self::execute("create_gps_map",$params);


            //1.1 拷贝一份球场配置文件到数据比赛中
            $courtInfo  = CourtModel::find($matchInfo->court_id);
            $configFile = "/".$courtInfo->config_file;
            copy(public_path($configFile),$dataDir."court-config.txt");
        }

        //2.同步两台设备的数据一致性
        $this->sync_file_num_same($matchId);

        //3.计算方向角
        $foots      = ["L","R"];
        foreach($foots as $foot)
        {
            $compassSensorFile  = $this->create_compass_sensor($matchId,$foot);
            $outFile            = $dataDir."angle-{$foot}.txt";

            //计算角度
            $this->compass_translate($compassSensorFile,$outFile);
        }

        //3.角度计算完毕，请求调用算法系统
        $this->call_matlab($this->matchId);

    }


    /**
     * 同步文件数量一致
     * @param $matchId integer 比赛ID
     * */
    private function sync_file_num_same($matchId)
    {
        $dataDir        = self::matchdir($matchId);
        $compassNumL    = get_file_line_num($dataDir."compass-L.txt");
        $compassNumR    = get_file_line_num($dataDir."compass-R.txt");

        //如果两个文件数量差别大于400，发出警报
        if(abs($compassNumL - $compassNumR) > 400)
        {
            $wechat     = new Wechat();
            $template   = WechatTemplate::warningTemplate();
            $template->openId   = config('app.adminOpenId');
            $template->first    = "系统警告";
            $template->warnType = "比赛数据量不一致";
            $template->warnTime = date_time();
            $template->remark   = "比赛ID:$matchId,罗盘左脚：{$compassNumL},罗盘右脚：{$compassNumR}";
            $wechat->template_message($template);
        }
    }



    /**
     * 生成计算角度的罗盘和sensor数据
     * @param $matchId integer 比赛ID
     * @param $foot string 脚
     * @return string
     * */
    public function create_compass_sensor($matchId,$foot)
    {
        //sensor数据
        $dataDir    = self::matchdir($matchId);
        $sensorPath = $dataDir."sensor-{$foot}.txt";
        $fsensor    = fopen($sensorPath,'r');

        //将所有数据读取到数组中
        $sensors    = file($sensorPath);


        //罗盘数据
        $compassPath= $dataDir."compass-{$foot}.txt";
        $fcompass   = fopen($compassPath,'r');


        //结果文件
        $resultPath = $dataDir."sensor-compass-{$foot}.txt";
        $fresult    = fopen($resultPath,'a+');

        $maxlength  = count($sensors)-1;
        $p=1;

        while(!feof($fcompass))
        {
            $linecompass    = fgets($fcompass);
            if(!$linecompass)
            {
                break;
            }
            //移动三条 读一条

            $newp = intval($p*4);

            if($newp > $maxlength)
            {
                break;
            }

            //$linesensor = fgets($fsensor);
            $linesensor   = $sensors[$newp];

            //$str = "[".$p.",".$newp."]".trim($linecompass,"\n")."------------".$linesensor;
            $linesensor = str_replace(" ",",",$linesensor);
            $linecompass= str_replace(" ",",",$linecompass);

            $str = trim(trim($linesensor,"\n")).",".trim(trim($linecompass,"\n"));

            if($str)
            {
                $str .= "\n";
            }

            fputs($fresult,$str);

            $p++;
        }

        fclose($fcompass);
        fclose($fsensor);
        fclose($fresult);

        return $resultPath;
    }



    /**
     * 罗盘角度转换
     * @param $infile string 输入文件
     * @param $outfile string 输出文件
     * @return boolean
     * */
    public function compass_translate($infile,$outfile)
    {
        if(!file_exists($infile))
        {
            return "输入文件不存在";
        }

        file_put_contents($outfile,"");     //清空历史数据

        $command    = "/usr/bin/compass $infile $outfile > /dev/null && echo 'success' ";

        $res        = shell_exec($command);
        $res        = trim($res);

        return $res == "success" ? true : false ;
    }



    /**
     * 调用算法系统
     * @param $matchId integer 比赛ID
     *
     * */
    private function call_matlab($matchId)
    {
        //检查2个文件是否存在
        $ANG_R      = public_path("uploads/match/{$matchId}/angle-R.txt");
        $ANG_L      = public_path("uploads/match/{$matchId}/angle-L.txt");


        if(file_exists($ANG_L) && file_exists($ANG_R))
        {
            $params     = ['matchId'=>$matchId];
            self::execute("run_matlab",$params,'matlab');

        }else{

            mylogger('文件缺失');
        }

    }


    /**
     * 生成GPS热点图
     * @param $matchId integer 比赛ID
     * @param $foot string 脚
     * @param $gpsData  array GPS数据
     * @return boolean
     * */
    public function create_gps_map($matchId,$foot,array $gpsData = [])
    {
        $matchInfo  = MatchModel::find($matchId);

        if($matchInfo->court_id == 0){

            return false;
        }

        $courtInfo  = CourtModel::find($matchInfo->court_id);
        $points     = $courtInfo->boxs;
        $points     =  \GuzzleHttp\json_decode($points);


        //没有数据,从数据库获取
        if(count($gpsData) == 0)
        {
            //GPS存储在文件中，不在数据库中

            $gpsFile    = public_path("uploads/match/".$matchId."/gps-".$foot.".txt");
            $gpsPoints  = file($gpsFile);

            foreach($gpsPoints as $gps)
            {
                $gpsInfo    = explode(' ',$gps);
                if($gpsInfo[0] == 0){

                    continue;
                }

                $lat        = $gpsInfo[0];
                $lon        = trim($gpsInfo[1],"\n");
                array_push($gpsData,['lat'=>$lat,'lon'=>$lon]);
            }
        }


        $court      = new Court();
        $court->set_centers($points->center);

        $mapData    = $court->create_court_hot_map($gpsData);


        //把结果存储到比赛结果表中
        $resultInfo = BaseMatchResultModel::find($matchId);

        if($resultInfo) {

            $resultInfo->gps_map    = \GuzzleHttp\json_encode($mapData);
            $resultInfo->save();

        }else{

            $resultInfo     = new BaseMatchResultModel();
            $resultInfo->match_id   = $matchId;
            $resultInfo->gps_map    = \GuzzleHttp\json_encode($mapData);
            $resultInfo->save();
        }

        return true;
    }


    /**
     * 初始化matlab软件
     *
     * 注意，这里的一切操作都是在算法服务器上，
     *
     * @param $matchId
     * */
    public function run_matlab($matchId)
    {
        $matchInfo      = MatchModel::find($matchId);
        $courtId        = $matchInfo->court_id;
        $localDir       = self::matchdir($matchId);

        $baseApiUrl     = config('app.apihost')."/uploads/match/{$matchId}/";
        mk_dir($localDir);
        //数据文件
        $baseSensorL    = "sensor-L.txt";
        $baseSensorR    = "sensor-R.txt";

        $baseCompassL   = "angle-L.txt";
        $baseCompassR   = "angle-R.txt";
        $baseGps        = "gps-L.txt";
        $baseConfig     = $courtId > 0 ? "court-config.txt" : '';


        //结果文件
        $resultRun      = "result-run.txt";//跑动结果
        $resultPass     = "result-pass.txt";//传球结果
        $resultStep     = "result-step.txt";


        $callbackUrl    = urlencode(config('app.apihost')."/api/matchCaculate/save_matlab_result?matchId={$matchId}&result=");//回调URL

        $files  = [
            "sensor_l"  =>  $baseSensorL,
            "sensor_r"  =>  $baseSensorR,
            "compass_l" =>  $baseCompassL,
            "compass_r" =>  $baseCompassR,
            "gps"       =>  $baseGps,
            "config"    => $baseConfig
        ];

        //===========将远程的文件拉取到本地来 开始==============
        //球场配置文件


        //数据文件
        foreach($files as $key  => $file)
        {
            if ($file == "")
            {
                continue;
            }

            $content    = file_get_contents($baseApiUrl.$file);

            $file       = $localDir.$file;

            file_put_contents($file,$content);
        }

        //===========将远程的文件拉取到本地来 结束==============


        $pythonfile = app_path('python/python_call_matlab.py');
        $matlabCmd  = "LanQi('{$localDir}','{$baseSensorL}','{$baseSensorR}','{$baseCompassL}','{$baseCompassR}','{$baseGps}','{$resultRun}','{$resultPass}','{$resultStep}')";
        $command    = "python {$pythonfile} --command={$matlabCmd}";

        shell_exec($command);
        mylogger("调用matlab成功：".$command);

        $params   = ['matchId'=>$matchId];
        self::execute("save_matlab_result",$params,'api');
    }



    /**
     * 保存matlab算法计算出来的结果
     * @param $matchId integer
     * @return string
     * */
    public function save_matlab_result($matchId)
    {
        //同步文件
        $files      = ["result-run.txt","result-pass.txt","result-step.txt"];
        $matchDir   = self::matchdir($matchId);
        $baseUrl    = config('app.matlabhost')."/uploads/match/{$matchId}/";

        foreach($files as $file){

            file_put_contents($matchDir.$file,file_get_contents($baseUrl.$file));
        }

        //1.提取跑动结果
        $this->save_run_result($matchId);


        //2.提取触球，传球结果

        $this->save_pass_and_touch($matchId);

        return "ok";
    }
    /*
     * @var 足球场信息
     * */
    private $courtInfo = false;


    /**
     * 提取跑动结果并保存
     * @param $matchId integer 比赛ID
     * */
    public function save_run_result($matchId)
    {
        $matchInfo  = MatchModel::find($matchId);

        //从远程服务器读取结果

        //1.速度信息
        //$speedFile  = config('app.matlabhost')."/uploads/match/{$matchId}/result-run.txt";

        $speedFile  = public_path("uploads/match/{$matchId}/result-run.txt");

        $speedsInfo = file($speedFile);

        $maxSpeed   = 0;    //比赛最高速度

        $speedType  = [
            'high'  => ['time'=>0,'dis'=>0,'limit'=>15*1000/60/60,'gps'=>[]],
            'middle'=> ['time'=>0,'dis'=>0,'limit'=>9*1000/60/60,'gps'=>[]],
            'low'   => ['time'=>0,'dis'=>0,'limit'=>3*1000/60/60,'gps'=>[]]
        ];

        $speedHigh  = $speedType['high']['limit'];
        $speedMid   = $speedType['middle']['limit'];
        $speedLow   = $speedType['low']['limit'];


        $avgSpeed   = $speedsInfo[0][0];    //整场比赛的平均速度
        $svgSpeed2  = $speedsInfo[0][1];    //整场比赛的平均加速度
        $distance   = $speedsInfo[0][2];    //整场比赛的跑动距离
        unset($speedsInfo[0]);


        //区别出低速，中速，高速
        foreach ($speedsInfo as $key => &$speedInfo)
        {
            if((trim($key)-1) % 20 != 0)
            {
                //continue;
            }

            $speedInfo = trim($speedInfo,"\r\n");
            $speedInfo = explode(' ',$speedInfo);
            $speed      = $speedInfo[1];
            $maxSpeed   = max($speed,$maxSpeed);

            //速度M/s
            if($speed > $speedHigh)
            {
                $type = "high";

            }elseif($speed > $speedMid){

                $type   = "middle";

            }elseif($speed > $speedLow){

                $type   = "low";
            }

            //将gps恢复到原始状态
            $lat    = gps_to_gps($speedInfo[3]*100);
            $lon    = gps_to_gps($speedInfo[4]*100);

            array_push($speedType[$type]['gps'],['lat'=>$lat,'lon'=>$lon]);

            if((trim($key)-1) % 20 == 0)
            {
                $speedType[$type]['time']++;
                $speedType[$type]['dis'] += $speed;
            }
        }

        mylogger(3);

        //创建高、中、低速跑动热点图
        foreach ($speedType as $key => $type)
        {
            $speedType[$key]['gps'] = $this->gps_map($matchInfo->court_id,$type['gps']);
            mylogger('3-'.$key);
        }

        mylogger(4);
        //11.修改单场比赛的结果
        $matchResult = [
            'run_low_dis'       => $speedType['low']['dis'],
            'run_mid_dis'       => $speedType['middle']['dis'],
            'run_high_dis'      => $speedType['high']['dis'],
            'run_low_time'      => $speedType['low']['time'],
            'run_mid_time'      => $speedType['middle']['time'],
            'run_high_time'     => $speedType['high']['time'],
            'run_speed_max'     => $maxSpeed,
            'map_speed_low'     => $speedType['low']['gps'],
            'map_speed_middle'  => $speedType['middle']['gps'],
            'map_speed_high'    => $speedType['high']['gps'],
        ];
        BaseMatchResultModel::where('match_id',$matchId)->update($matchResult);


        //修改个人的整体数据 在此前一定会创建用户的个人数据
        $userAbility    = BaseUserAbilityModel::find($matchInfo->user_id);

        $userAbility->run_distance_high     += $speedType['high']['dis'];   //总高速
        $userAbility->run_distance_middle   += $speedType['middle']['dis']; //总中速
        $userAbility->run_distance_low      += $speedType['low']['dis'];    //总低速
        $userAbility->run_speed_max         =  max($userAbility->run_speed_max,$maxSpeed); //最高速度
        $userAbility->run_distance_total    += ($speedType['high']['dis'] + $speedType['middle']['dis'] + $speedType['low']['dis']);
        $userAbility->run_time_total        += ($speedType['high']['time'] + $speedType['middle']['time'] + $speedType['low']['time']);
        $userAbility->user_id               =  $matchInfo->user_id;
        $userAbility->save();

    }


    //
    public function save_pass_and_touch($matchId)
    {
        $passFile   = public_path("uploads/match/{$matchId}/result-pass.txt");
        $passlist   = file($passFile);
        $matchInfo  = MatchModel::find($matchId);


        //1：长传 2：短传 3：触球
        $data   = [
            "passLong"  =>["data"=>[],'speedMax'=>0,'speedAvg'=>0,'num'=>0,'gps'=>[]],
            "passShort" =>["data"=>[],'speedMax'=>0,'speedAvg'=>0,'num'=>0,'gps'=>[]],
            "touchball" =>["data"=>[],'speedMax'=>0,'speedAvg'=>0,'num'=>0,'gps'=>[]]
        ];

        foreach($passlist as $pass)
        {
            $pass   = trim($pass,"\r\n");
            $pass   = explode(" ",$pass);
            $type   = intval($pass[0]); //类型

            switch ($type)
            {
                case 1 : array_push($data['passLong']['data'],$pass);break;
                case 2 : array_push($data['passShort']['data'],$pass);break;
                case 3 : array_push($data['touchball']['data'],$pass);break;
            }
        }


        foreach($data as &$pass)
        {
            $speeds = [];
            $gps    = [];

            foreach($pass['data'] as $type)
            {
                $lat    = gps_to_gps($type[3]*100);
                $lon    = gps_to_gps($type[4]*100);
                array_push($speeds,$type[2]);
                array_push($gps,['lat'=>$lat,'lon'=>$lon]);
            }

            $pass['num']        = count($pass['data']);
            $pass['speedMax']   = round(max($speeds));
            $pass['speedAvg']   = round(array_sum($speeds)/count($speeds));
            $pass['gps']        = $this->gps_map($matchInfo->court_id,$gps);
            unset($pass['data']);
        }


        $matchResult    = [
            'pass_s_num'            => $data['passShort']['num'],
            'pass_s_speed_max'      => $data['passShort']['speedMax'],
            'pass_s_speed_avg'      => $data['passShort']['speedAvg'],
            'pass_l_num'            => $data['passLong']['num'],
            'pass_l_speed_max'      => $data['passLong']['speedMax'],
            'pass_l_speed_avg'      => $data['passLong']['speedAvg'],
            'touchball_num'         => $data['touchball']['num'],
            'touchball_speed_max'   => $data['touchball']['speedMax'],
            'touchball_speed_avg'   => $data['touchball']['speedAvg'],
            'map_pass_short'        => $data['passShort']['gps'],
            'map_pass_long'         => $data['passLong']['gps'],
            'map_touchball'         => $data['touchball']['gps']
        ];

        BaseMatchResultModel::where('match_id',$matchId)->update($matchResult);

        //存储用户全局性的数据
        $userAbility    = BaseUserAbilityModel::find($matchInfo->user_id);
        $userAbility->pass_num_short        += $data['passShort']['num'];
        $userAbility->pass_num_long         += $data['passLong']['num'];
        $userAbility->pass_num_total        += ($data['passShort']['num'] + $data['passLong']['num']);
        $userAbility->pass_speed_max        =  max($userAbility->pass_speed_max,$data['passLong']['speedMax']);
        $userAbility->touchball_num_total   += $data['touchball']['num'];
        $userAbility->save();

    }


    /**
     * 创建各种项目的热点图
     * @param  $courtId integer 球场ID
     * @param  $gpsList array GPS列表
     * @return string
     * */
    public function gps_map($courtId,$gpsList)
    {
        //创建GPS图谱
        if($this->courtInfo == false)
        {
            $this->courtInfo    = CourtModel::find($courtId);
        }

        $courtInfo  = $this->courtInfo;
        $points     = $courtInfo->boxs;
        $points     =  \GuzzleHttp\json_decode($points);
        $court      = new Court();
        $court->set_centers($points->center);

        $mapData    = $court->create_court_hot_map($gpsList);
        $mapData    = \GuzzleHttp\json_encode($mapData);
        return $mapData;
    }



}

