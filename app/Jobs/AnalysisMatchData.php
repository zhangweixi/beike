<?php

namespace App\Jobs;

use App\Common\Geohash;
use App\Common\WechatTemplate;
use App\Http\Controllers\Service\GPSPoint;
use App\Http\Controllers\Service\MatchGrade;
use App\Http\Controllers\Service\Wechat;
use App\Models\Base\BaseFootballCourtModel;
use App\Models\Base\BaseMatchDataProcessModel;
use App\Models\Base\BaseMatchModel;
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
    public $timeout = 250;

    private $sourceId   = 0;    //要处理的比赛的数据
    private $userId     = 0;    //用户ID
    private $action     = "";   //要调用的方法
    private $infile     = "";   //转换角度的文件，转换角度的输入文件
    private $outfile    = "";   //转换角度的输出文件
    private $matchId    = 0;    //比赛ID
    private $courtId    = 0;
    private $type       = "";   //数据类型
    private $foot       = "";   //哪只脚
    private $jxNext     = false;



    public function __construct($action='',$param = [])
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

            case 'call_matlab_court_action':$this->call_matlab_court_action($this->courtId);            break;
        }
    }
    /**
     * 解析数据
     * */
    public function parse_data()
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
            logbug('文件['.$this->sourceId.']不存在'.$sourceData->data);
            return false;
        }

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

        //标记处于解析状态中
        MatchModel::update_match_data($this->sourceId,['status'=>1]);

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
            case     "gps": $datas = $this->handle_gps_data($dataStr,$matchId,$syncTime);       break;
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
        //多场数据 按比赛场次来对数据进行分组
        $matchesData    = [];

        foreach($datas as $data)
        {
            $matchId    = $data['match_id'];

            if(!isset($matchesData[$matchId])) {

                $matchesData[$matchId]  = [
                    'isFinish'  => 0,
                    'matchId'   => $matchId,
                    'data'      => []
                ];
            }
            array_push($matchesData[$matchId]['data'],$data);
        }

        foreach($matchesData as $matchId => $matchData)
        {
            //检查数据是否为空
            if(count($matchData['data']) == 0){

                unset($matchesData[$matchId]);
                continue;
            }

            $dir        = matchdir($matchId);mk_dir($dir);
            $file       = $dir.$type."-".$foot.".txt";
            $hasFile    = file_exists($file);
            $fd         = fopen($file,'a');
            if(!$hasFile){
                chmod($file,0777);
            }

            $flags      = [];
            $isSyncTime = "";

            foreach($matchData['data'] as $data){

                $flagType   = $data['type'];

                if($flagType == ""){

                    $data['type'] = $isSyncTime;

                    switch ($type)
                    {
                        case "gps":     $str = self::join_array($data,['lat','lon','timestamp',"type"]);   break;
                        case "sensor":  $str = self::join_array($data,['ax','ay','az','timestamp',"type"]);break;
                        case "compass": $str = self::join_array($data,['x','y','z','timestamp',"type"]);   break;
                    }

                    fwrite($fd,$str."\n");//将数据写入到文件中
                    $isSyncTime = 0;

                }else{

                    $isSyncTime = 1;

                    if($flagType == "E")    //END 数据结束
                    {
                        $matchesData[$matchId]['isFinish']  = 1;    //比赛结束标记

                    }

                    array_push($flags,array_merge($data,$dataBaseInfo));
                }
            }

            fclose($fd);

            //插入标记，比如同步时间，暂停标记等
            $flags  = array_chunk($flags,1000);


            foreach($flags as $key => $data)
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

        if($nextData && $this->jxNext == true)
        {
            $params = ['matchSourceId'  =>  $nextData->match_source_id];
            self::execute('jiexi_single_data',$params,'api');
        }


        /*========本条数据解析完毕，请求解析下一条数据 end =====*/


        //上传的最后一条是数据，生成航向角 生成json文件

        foreach($matchesData as $key => $matchData)
        {
            $matchId = $matchData['matchId'];

            if(($matchData['isFinish'] == 0 && $sourceData->is_finish == 0 ) || $matchId == 0) //比赛未结束并且也没有上传结束标记
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
                mylogger("解析结束");
                self::execute("finish_parse_data",["matchId"=>$matchId]);
            }
        }

        return true;
    }

    /* *
     * 连接数组中的某些值
     * @param $array array
     * @param $keys array
     * @flag string
     * @return string
     * */
    static function join_array($array,$keys,$flag=' '){

        $tempArr = [];

        foreach($keys as $key){

            $tempArr[$key] = $array[$key];
        }

        return implode($flag,$tempArr);
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
        '99'    => "B", //开始
        '88'    => "T", //同步
        'aa'    => "P", //暂停
        'bb'    => "C", //继续
        'cc'    => "E"  //结束
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
        //每一条数据的长度为20位 类型：2位 x:4,y:4,z:4,校验:2

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
    private function handle_gps_data($dataSource,$matchId,$syncTime)
    {
        $dataList       = explode("23232323",$dataSource); //gps才有232323
        $dataList       = array_filter($dataList);

        $insertData     = [];
        $types          = ["00000000","01000000","02000000","03000000","04000000"];
        $validDataNum   = 0;


        foreach($dataList as $key =>  $single)
        {
            //时间（16）长度（8）数据部分（n）
            $timestamp  = substr($single,0,16);
            $length     = substr($single,16,8);
            $timestamp  = HexToTime($timestamp);

            if(in_array($length,$types)){ //非GPS正式内容

                if($length == "00000000"){

                    $matchId    = $this->find_match_by_time($timestamp);
                }

                switch ($length)
                {
                    case "00000000": $type = "B";break;
                    case "01000000": $type = "P";break;
                    case "02000000": $type = "C";break;
                    case "03000000": $type = "E";break;
                    case "04000000": $type = "T";break;
                }

                $lon    = 0;
                $lat    = 0;
                $syncTime   = $timestamp;

            }else{

                $data       = substr($single,24);   //数据部分起始
                $data       = strToAscll($data);
                $detailInfo = explode(",",$data);
                $type       = "";

                //数据即便不合格，也不能丢弃
                $validDataNum++;
                $timestamp  = $syncTime + $validDataNum * 10;

                if(count($detailInfo)<15) {

                    $lat    = 0;
                    $lon    = 0;

                }else{

                    $lat       = gps_to_gps(floatval($detailInfo[2]));
                    $lon       = gps_to_gps(floatval($detailInfo[4]));

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
                BaseMatchModel::match_process($matchId,"数据".$this->sourceId.":数据长度不足,前一条:".$dataArr[$key-1].",本条:".$data);
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
        BaseMatchModel::match_process(0,$this->sourceId."无法找到比赛ID，time:".$beginTime);
        dd('无法找到比赛ID,userId:'.$this->userId.",time:".$beginTime);
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
     * 解析数据结束
     * @param $matchId integer 比赛ID
     * */
    public function finish_parse_data($matchId)
    {
        $matchInfo      = MatchModel::find($matchId);
        $dataDir        = self::matchdir($matchId);
        $courtId        = $matchInfo->court_id;
        $courtInfo      = self::get_court_info($courtId);


        //1.同步两台设备的数据一致性
        $files  = [
            ['file'=>'gps-L.txt',       'typeKey'  => 3,'timeKey'  =>2,'hz'=>100],
            ['file'=>'sensor-L.txt',    'typeKey'  => 4,'timeKey'  =>3,'hz'=>10],
            ['file'=>'sensor-R.txt',    'typeKey'  => 4,'timeKey'  =>3,'hz'=>10],
            ['file'=>'compass-L.txt',   'typeKey'  => 4,'timeKey'  =>3,'hz'=>25],
            ['file'=>'compass-R.txt',   'typeKey'  => 4,'timeKey'  =>3,'hz'=>25],
        ];

        BaseMatchModel::match_process($matchId,"同步数据开始");
        foreach($files as $file)
        {
            copy($dataDir.$file['file'],$dataDir.$file['file'].".back");
            self::reset_data_time($dataDir.$file['file'],$file['typeKey'],$file['timeKey'],$file['hz']);
        }

        BaseMatchModel::match_process($matchId,"同步数据结束，同步时间开始");

        //同步时间阶段
        self::sync_file_time_stage($matchId);

        BaseMatchModel::match_process($matchId,"同步时间开始");

        //2.将国际GPS转换成百度GPS

        $inputGps   = $dataDir."gps-L.txt";
        $outGps     = $dataDir."gps-L.txt";
        $cmd        = "node ". app_path('node/gps.js') . " --outtype=file --input={$inputGps} --output={$outGps} ";
        $cmd        = str_replace("\\","/",$cmd);
        $result     = shell_exec($cmd);
        BaseMatchModel::match_process($matchId,"GPS转换成功");

        //检查球场是否合格，如果不合格则根据GPS来生成球场
        BaseMatchModel::match_process($matchId,"球场,width:{$courtInfo->width},height:{$courtInfo->length}");

        if(!Court::check_court_is_valid($courtInfo->width,$courtInfo->length))
        {

            BaseMatchModel::match_process($matchId,"球场无效,width:{$courtInfo->width},height:{$courtInfo->length}，创建虚拟球场");
            Court::create_visual_match_court($matchId,$courtId);
        }


        (new Court())->cut_court_to_box_and_create_config($courtId); //创建配置文件




        //3.0 生成热点图占用时间比较久，异步调用
        //self::execute("create_gps_map",['matchId'=>$matchId,'foot'=>"L"]); 移到后面统一处理


        //3.1 拷贝一份球场配置文件到数据比赛中
        $courtInfo  = CourtModel::find($matchInfo->court_id);
        $configFile = "/".$courtInfo->config_file;
        copy(public_path($configFile),$dataDir."court-config.txt");

        BaseMatchModel::match_process($matchId,"拷贝球场配置成功");

        $this->caculate_angle($matchId);    //计算角度
        BaseMatchModel::match_process($matchId,"角度计算完毕");

        //3.角度计算完毕，请求调用算法系统
        //$params     = ['matchId'=>$matchId];
        //self::execute("run_matlab",$params,'matlab');
        //BaseMatchModel::match_process($matchId,'请求算法系统');
        $this->run_matlab($matchId);
    }

    public function caculate_angle($matchId){

        $dataDir    = self::matchdir($matchId);
        //3.计算方向角
        foreach(["L","R"] as $foot)
        {
            $compassSensorFile  = $this->create_compass_sensor($matchId,$foot);
            $outFile            = $dataDir."angle-{$foot}.txt";

            //计算角度
            $this->compass_translate($compassSensorFile,$outFile);
        }
    }


    public static function sync_file_time_stage($matchId){

        $dir = matchdir($matchId);

        $files = [
            ['file'=>$dir."gps-L.txt"],
            ['file'=>$dir."sensor-L.txt"],
            ['file'=>$dir."sensor-R.txt"],
            ['file'=>$dir."compass-L.txt"],
            ['file'=>$dir."compass-R.txt"]
        ];

        $nums   = [];

        //找到最小的时间
        foreach($files as $key=> $f){

            $data = trim(tail($f['file'],1));
            $data = explode(" ",$data);

            $num  = $data[count($data)-1];

            $files[$key]['num'] = $num;

            array_push($nums,$num);
        }

        $minNum = min($nums);

        //同步为最小的时间
        foreach($files as $f){

            if($f['num'] > $minNum){

                $data   = file_to_array($f['file']);

                $fd     = fopen($f['file'],'w+');
                $count  = count($data[0])-1;

                foreach($data as $d){

                    if($d[$count] <= $minNum){

                        fwrite($fd,implode(" ",$d)."\n");
                    }else{
                        break;
                    }
                }

                fclose($fd);
                unset($data);
            }
        }


    }
    /**
     * 重置数据的时间
     * @param $file string 文件路径
     * @param $typeKey integer 类型KEY
     * @param $timeKey integer 时间主键
     * @param $hz integer 数据频率
     * */
    public static function reset_data_time($file,$typeKey,$timeKey,$hz){

        $dataArr    = file_to_array($file);

        //拆分成许多同步段
        $stagesData  = [];

        //阶段序数，实际上就是时间(分钟)
        $synctimeNum = -1;

        foreach($dataArr as $data){

            if($data[$typeKey] == 1){


                if($synctimeNum > -1){

                    $stagesData[$synctimeNum]['endTime'] = $data[$timeKey];
                }

                $synctimeNum++;

                $stagesData[$synctimeNum] = [
                    'data'      => [],
                    'beginTime' => $data[$timeKey],
                    'endTime'   => 0,
                    'num'       => 0
                ];
            }

            if($synctimeNum == -1){

                $admin  = config("sys.ADMIN_USER_ID");
                jpush_content("提示","文件{$file}无法在开头找到同步时间，请检查",0,1,$admin);
                $synctimeNum++;
            }
            $stagesData[$synctimeNum]['num']++;
            array_push($stagesData[$synctimeNum]['data'],$data);
        }

        $fs         = fopen($file,'w+');

        foreach ($stagesData as $type => $stage){

            if($stage['endTime'] > 0){

                $perTime    = ($stage['endTime'] - $stage['beginTime']) / $stage['num'];

            }else{

                $perTime    = $hz;
            }

            foreach($stage['data'] as $num => $data)
            {

                $data[$timeKey] = substr($stage['beginTime'] + $perTime * $num,0,13);
                $data[$typeKey] = $type;
                fwrite($fs,implode(" ",$data)."\n");
            }
        }

        fclose($fs);
        unset($dataArr);
        unset($stagesData);
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

        //将所有数据读取到数组中
        $sensorPath = $dataDir."sensor-{$foot}.txt";
        $sensor     = file_to_array($sensorPath);
        $sensors    = [];

        foreach($sensor as $data){

            $timeStage  = $data[4];

            if(!isset($sensors[$timeStage]))
            {
                $sensors[$timeStage] = [];
            }
            array_push($sensors[$timeStage],$data);
        }


        //罗盘数据
        $compassPath= $dataDir."compass-{$foot}.txt";
        $compass    = file_to_array($compassPath);
        $compassArr = [];

        foreach($compass as $data){

            $timeStage  = $data[4];

            if(!isset($compassArr[$timeStage]))
            {
                $compassArr[$timeStage] = [];
            }
            array_push($compassArr[$timeStage],$data);
        }



        //结果文件
        $resultPath = $dataDir."sensor-compass-{$foot}.txt";
        $fresult    = fopen($resultPath,'w+');

        foreach($compassArr as $stage   => $compass)
        {
            $stageSensors   = $sensors[$stage];
            $sensorNum      = count($stageSensors);
            $begin          = 0;

            foreach($compass as $singleCompass)
            {
                $time           = $singleCompass[3];
                $currentSensor  = null;

                for($i=$begin;$i<$sensorNum;$i++){

                    $begin++;

                    $sensor     = $stageSensors[$i];

                    if($sensor[3] > $time){

                        $currentSensor  = $sensor;
                        break;
                    }
                }

                if(is_null($currentSensor)){ //如果找不到，则取最后一条

                    $currentSensor  = $stageSensors[$sensorNum-1];
                }

                $data   = array_merge(array_splice($currentSensor,0,3),$singleCompass);
                fwrite($fresult,implode(",",$data)."\n");
            }
        }

        fclose($fresult);
        unset($compassArr);
        unset($sensors);
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
     * 生成全场跑动GPS热点图
     * @param $matchId integer 比赛ID
     * @param $foot string 脚
     * @param $gpsData  array GPS数据
     * @return boolean
     * */
    public function create_gps_map($matchId,$foot="L",array $gpsData = [])
    {
        $matchInfo  = MatchModel::find($matchId);

        if($matchInfo->court_id == 0)
        {
            return false;
        }

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

        /***************/
        self::get_court_info($matchInfo->court_id,true);
        $result = $this->gps_map($matchInfo->court_id,$gpsData);

        $data = ['map_gps_run'=>\GuzzleHttp\json_encode($result)];
        BaseMatchResultModel::where('match_id',$matchId)->update($data);

        /***************/

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
        $matchInfo  = MatchModel::select('court_id')->find($matchId);
        self::sync_court_config($matchId,$matchInfo->court_id);     //同步球场配置文件
        $result     = self::call_matlab_calculate($matchId);        //计算

        if(trim($result) == "success") {

            $this->save_matlab_result($matchId);

        }else{

            logbug("比赛:".$matchId."执行算法失败");
            mylogger($matchId."调用结果:".$result);

        }
    }

    /**
     * @param $act string   match:计算比赛 court:计算球场
     * @param $id string    当act为match时，为比赛ID，当act为court时，为:courtId
     * @return string
     * */
    static function call_matlab_calculate($act,$id)
    {
        if($act == "match"){
            $fpath      = public_path("uploads/match/".$id."/");
        }elseif($act == "court"){
            $fpath      = public_path("uploads/court-config/".$id."/");
        }

        $filepath     = app_path("python/matlabsrc");
        mylogger("cd {$filepath} && python run --path={$fpath} --act=".$act);
        return shell_exec("cd {$filepath} && python run --path={$fpath} --act=".$act);
    }


    static function sync_court_config($matchId,$courtId)
    {
        $courtInfo  = CourtModel::find($courtId);
        $configFile = public_path($courtInfo->config_file);
        $desConfig  = matchdir($matchId)."court-config.txt";
        copy($configFile,$desConfig);
    }

    /**
     * 保存matlab算法计算出来的结果
     * @param $matchId integer
     * @return string
     * */
    public function save_matlab_result($matchId)
    {
        /*
         * result-run.txt   跑动结果
         * result-pass.txt  传球结果
         * result-step.txt  步数结果
         * result-turn.txt  转身结果
         * result-shoot.txt 射门结果
         * */

        $matchInfo  = self::get_temp_match_info($matchId);

        //比赛数据处理完毕，将比赛数量增加1 检查是否有了分数，有分数表示已经累积过
        $matchResult= BaseMatchResultModel::where('match_id',$matchId)->select("grade")->first();
        if($matchResult->grade == 0)
        {
            BaseUserAbilityModel::where('user_id',$matchInfo->user_id)->increment("match_num");
        }


        //1.提取跑动结果
        $runResult      = $this->save_run_result($matchId);


        //2.提取触球，传球结果
        $passTouchResult = $this->save_pass_and_touch($matchId);



        //3.射门数据
        $shootResult    = $this->save_shoot_result($matchId);


        //4.转向和急停
        $changeDictResult = $this->save_direction_result($matchId);


        //5.带球与回追
        //$dribbleResult  = $this->save_backrun_result($matchId);

        //6.生成跑动GPS热点图
        $this->create_gps_map($matchId);

        //暂时将计算分值的放在这里，由于计算分值是一个耗时的工作，需要另外使用线程
        $gradeService   = new MatchGrade();
        $matchGrade     = $gradeService->get_match_new_grade($matchId);

        $globalGrade    = $gradeService->get_global_new_grade($matchInfo->user_id);


        BaseMatchResultModel::where('match_id',$matchId)->update($matchGrade);

        BaseUserAbilityModel::where('user_id',$matchInfo->user_id)->update($globalGrade);

        //销毁比赛的历史信息
        self::destory_match_cache($matchId,$matchInfo->court_id);

        BaseMatchModel::remove_minitor_match($matchId);

        return "success";
    }

    /**
     * 销毁比赛数据
     * */
    static function destory_match_cache($matchId,$courtId){

        //销毁球场
        if(isset(self::$courtInfo[$courtId])){

            unset(self::$courtInfo[$courtId]);
        }


        //销毁比赛
        if(isset(self::$tempMatchInfo[$matchId])){

            unset(self::$tempMatchInfo[$matchId]);
        }
    }

    /*
     * @var 足球场信息
     * */
    static $courtInfo       = [];

    static function get_court_info($courtId,$fresh=false)
    {

        if(!isset(self::$courtInfo[$courtId]) || $fresh){

            $courtInfo    = CourtModel::find($courtId);
            $points       = [
                'pa'  => $courtInfo->p_a,
                'pa1' => $courtInfo->p_a1,
                'pd'  => $courtInfo->p_d,
                'pd1' => $courtInfo->p_d1
            ];

            foreach ($points as $key => $p)
            {
                $p = explode(",",$p);
                $points[$key] = ['x'=>$p[1],'y'=>$p[0]];
            }
            $points['width']    = $courtInfo->width;
            $points['length']   = $courtInfo->length;
            self::$courtInfo[$courtId] = (object)$points;
        }

        return self::$courtInfo[$courtId];
    }


    /*
     * var 比赛信息
     * */
    private static $tempMatchInfo = [];

    /**
     * 比赛信息
     * @param $matchId integer
     * @return object
     * */
    private static function get_temp_match_info($matchId)
    {
        $matchInfo  = self::$tempMatchInfo;

        if(!isset($matchInfo[$matchId])){


            self::$tempMatchInfo[$matchId]  = MatchModel::find($matchId);
        }

        return self::$tempMatchInfo[$matchId];
    }



    /**
     * 提取跑动结果并保存
     * @param $matchId integer 比赛ID
     * */
    public function save_run_result($matchId)
    {
        $matchInfo  = self::get_temp_match_info($matchId);
        BaseMatchModel::match_process($matchId,"计算跑动结果:".\GuzzleHttp\json_encode($matchInfo));
        //1.速度信息
        $speedFile  = self::matchdir($matchId)."result-run.txt";

        $speedsInfo = file_to_array($speedFile);
        $maxSpeed   = 0;    //比赛最高速度

        $speedType  = [
            'static'=> ['time'=>0,'dis'=>0,'limit'=>0,'gps'=>[]],
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

        $gpsHz     = 10;   //频率 赫兹

        array_splice($speedsInfo,0,1);  //去掉首行


        $adruptStop    = [

            "prev1Speed"    => 0,
            "prev2Speed"    => 0,
            "prev1Point"    => null,
            "prev2Point"    => null,
            "list"          => [],
            "speedRang"     => 15
        ];//急停数据

        $timeSpeeds = [];   //实时速度
        $timeDis    = [0];  //实时距离


        //区别出低速，中速，高速
        $speedsInfo     = array_chunk($speedsInfo, $gpsHz);
        foreach($speedsInfo as $key => $speedArr){

            //求平均速度
            $tempSpe    = [];
            $tempLat    = [];
            $tempLon    = [];

            foreach($speedArr as $speed){

                if(abs($speed[3]) == 0 || abs($speed[4]) == 0){

                    continue;
                }
                array_push($tempSpe, abs($speed[1]));
                array_push($tempLat, abs($speed[3]));
                array_push($tempLon, abs($speed[4]));
            }

            $tempCount  = count($tempSpe);
            $speedsInfo[$key] = [
                "speed"     => $tempCount == 0 ? 0 : array_sum($tempSpe)/$tempCount,
                "lat"       => $tempCount == 0 ? 0 : array_sum($tempLat)/$tempCount,
                'lon'       => $tempCount == 0 ? 0 : array_sum($tempLon)/$tempCount
            ];

        }


        foreach ($speedsInfo as $key => &$speedInfo)
        {
            $speed      = $speedInfo['speed'];
            $lat        = $speedInfo["lat"];
            $lon        = $speedInfo["lon"];

            array_push($timeSpeeds,$speed);
            array_push($timeDis,end($timeDis)+$speed);

            $maxSpeed   = max($speed,$maxSpeed);

            //速度M/s
            if($speed > $speedHigh)
            {
                $type = "high";

            }elseif($speed > $speedMid){

                $type   = "middle";

            }elseif($speed > $speedLow){

                $type   = "low";

            }else{

                $type   = "static";
            }

            array_push($speedType[$type]['gps'],['lat'=>$lat,'lon'=>$lon]);

            $speedType[$type]['time']++;
            $speedType[$type]['dis'] += $speed;


            //判断急停
            if($adruptStop['prev2Speed'] - $speed > $adruptStop['speedRang']){ //速速之差达到急停要求

                array_push($adruptStop['list'],[$adruptStop['prev2Point'],$speedInfo]);

                $adruptStop['prev2Point']   = $speedInfo;
                $adruptStop['prev1Point']   = $speedInfo;

                $adruptStop['prev2Speed']   = $speed;
                $adruptStop['prev1Speed']   = $speed;

            }else{

                $adruptStop['prev2Point']   = $adruptStop['prev1Point'];
                $adruptStop['prev1Point']   = $speedInfo;
                $adruptStop['prev2Speed']   = $adruptStop['prev1Speed'];
                $adruptStop['prev1Speed']   = $speed;
            }
        }
        $timeStage   = 5; //平均的最小时间 单位S
        $timeSpeeds  = array_chunk($timeSpeeds,$timeStage);
        foreach($timeSpeeds as $key1 => $stepSpeed)
        {
            $timeSpeeds[$key1] = array_sum($stepSpeed)/$timeStage;
        }

        //把第一个为0的删除
        array_splice($timeDis,0,1);
        $timeDis    = array_chunk($timeDis,$timeStage);
        foreach ($timeDis as $key2 => $dis)
        {
            $timeDis[$key2] = end($dis);
        }
        array_unshift($timeDis,0);
        

        foreach($timeSpeeds as $key1 => $speed)
        {
            $timeSpeeds[$key1] = speed_second_to_hour($speed);
        }
        array_unshift($timeSpeeds,0);

        $timeSpeeds     = implode(",",$timeSpeeds);
        $timeSpeeds     = "[".$timeSpeeds."]";

        foreach ($timeDis as $key2 => $dis)
        {
            $timeDis[$key2] = round($dis / 1000,2);
        }

        $timeDis    = implode(",",$timeDis);
        $timeDis    = "[".$timeDis."]";

        //需要计算在不同的时间点，不同类型的速度跑动的距离
        //创建高、中、低速跑动热点图
        foreach ($speedType as $key => $type)
        {
            $speedType[$key]['gps'] = \GuzzleHttp\json_encode($this->gps_map($matchInfo->court_id,$type['gps']));
        }

        //11.修改单场比赛的结果
        $matchResult = [
            'run_low_dis'       => $speedType['low']['dis'],
            'run_mid_dis'       => $speedType['middle']['dis'],
            'run_high_dis'      => $speedType['high']['dis'],
            'run_static_dis'    => $speedType['static']['dis'],
            'run_low_time'      => $speedType['low']['time'],
            'run_mid_time'      => $speedType['middle']['time'],
            'run_high_time'     => $speedType['high']['time'],
            'run_static_time'   => $speedType['static']['time'],
            'run_speed_max'     => $maxSpeed,
            'run_time_speed'    => $timeSpeeds,
            'run_time_dis'      => $timeDis,
            "abrupt_stop_num"   => count($adruptStop['list']),
            'run_high_speed_avg'=> $speedType['high']['time'] > 0 ? $speedType['high']['dis']/$speedType['high']['time'] : 0,//高速平均跑动速度
            'map_speed_static'  => $speedType['static']['gps'],
            'map_speed_low'     => $speedType['low']['gps'],
            'map_speed_middle'  => $speedType['middle']['gps'],
            'map_speed_high'    => $speedType['high']['gps'],
        ];

        BaseMatchResultModel::where('match_id',$matchId)->update($matchResult);


        //修改个人的整体数据 在此前一定会创建用户的个人数据
        $userAbility    = BaseUserAbilityModel::find($matchInfo->user_id);


        //1.跑动距离
        $disHigh    = $speedType['high']['dis'];
        $disMid     = $speedType['middle']['dis'];
        $disLow     = $speedType['low']['dis'];
        $disStatic  = $speedType['static']['dis'];

        $userAbility->run_distance_high     += $disHigh;    //总高速
        $userAbility->run_distance_middle   += $disMid;     //总中速
        $userAbility->run_distance_low      += $disLow;     //总低速
        $userAbility->run_distance_static   += $disStatic;  //总走动
        $userAbility->run_distance_total    += ($disHigh + $disMid + $disLow + $disStatic);


        //跑动时间
        $timeHigh   = $speedType['high']['time'];
        $timeMid    = $speedType['middle']['time'];
        $timeLow    = $speedType['low']['time'];
        $timeStatic = $speedType['static']['time'];

        $userAbility->run_time_high         += $timeHigh;
        $userAbility->run_time_middle       += $timeMid;
        $userAbility->run_time_low          += $timeLow;
        $userAbility->run_time_static       += $timeStatic;
        $userAbility->run_time_total        += ($timeHigh + $timeMid + $timeLow + $timeStatic);

        //速度
        $userAbility->run_speed_max         =  max($userAbility->run_speed_max,$maxSpeed);                      //最高速度
        $userAbility->run_high_speed        = $userAbility->run_distance_high / $userAbility->run_time_total;   //高速平均速度

        $userAbility->abrupt_stop_num       += $matchResult['abrupt_stop_num'];
        $userAbility->user_id               =  $matchInfo->user_id;

        $userAbility->save();

    }
    /**
     * 传球和触球
     * */
    public function save_pass_and_touch($matchId)
    {
        $passFile   = self::matchdir($matchId)."result-pass.txt";
        $passlist   = file_to_array($passFile);
        $matchInfo  = self::get_temp_match_info($matchId);

        //1：长传 2：短传 3：触球
        $typeDataes   = [
            "passLong"  =>["data"=>[],'speedMax'=>0,'speedAvg'=>0,'num'=>0,'gps'=>[]],
            "passShort" =>["data"=>[],'speedMax'=>0,'speedAvg'=>0,'num'=>0,'gps'=>[]],
            "touchball" =>["data"=>[],'speedMax'=>0,'speedAvg'=>0,'num'=>0,'gps'=>[]]
        ];

        //传球类型临界距离
        //5人制：10米
        //7人制：18米
        //11人制：25米

        foreach($passlist as $passData)
        {
            $foot   = intval($passData[0]);//脚
            $type   = intval($passData[1]);//类型

            switch ($type)
            {
                case 1 : array_push($typeDataes['passLong']['data'],$passData);break;
                case 2 : array_push($typeDataes['passShort']['data'],$passData);break;
                case 3 : array_push($typeDataes['touchball']['data'],$passData);break;
            }
        }

        $dribbleFlag = [
            "list"      => [],//触球的数据列表
            "prevTime"  => 0,//前一次的触球时间
            "prevData"  => []
        ];

        foreach($typeDataes as $type => &$typeData)
        {
            $speeds = [];
            $gps    = [];


            foreach($typeData['data'] as $singleData)
            {
                $time   = $singleData[2];
                $lat    = $singleData[4];
                $lon    = $singleData[5];
                array_push($speeds,$singleData[6]);
                array_push($gps,['y'=>$lat,'x'=>$lon]);


                //为触球时，根据连续触球的时间来判断是否是带球，连续触球的时间间隔小于2秒，当做带球一次
                if($type == 'touchball'){

                    if($time - $dribbleFlag['prevTime'] <=2 ){ //间隔时间小于2秒，加入到一次触球中

                        array_push($dribbleFlag['prevData'],$singleData);

                        continue;

                    }else{

                        if(count($dribbleFlag['prevData']) > 1) {

                            array_push($dribbleFlag['list'], $dribbleFlag['prevData']); //把以前的完整的带球放入到列表中
                        }

                        $dribbleFlag['prevData']  = [$singleData];
                    }

                    $dribbleFlag['prevTime']  = $time;
                }
            }
            $dataNum                = count($speeds);
            $typeData['num']        = $dataNum;
            $typeData['speedMax']   = $dataNum > 0 ? round(max($speeds)) : 0;
            $typeData['speedAvg']   = $dataNum > 0 ? round(array_sum($speeds)/count($speeds)) : 0;

            //$typeData['gps']        = $this->gps_map($matchInfo->court_id,$gps);

            $court                  = self::get_court_info($matchInfo->court_id);
            $gpsMap                 = Court::create_gps_map($court->pa,$court->pa1,$court->pd,$court->pd1,$gps);
            foreach($gpsMap as $key => $gps)
            {
                $gpsMap[$key]      = [intval($gps['x']),intval($gps['y'])];
            }
            $typeData['gps']        = \GuzzleHttp\json_encode($gpsMap);

            unset($typeData['data']);
        }


        $matchResult    = [
            'pass_s_num'            => $typeDataes['passShort']['num'],     //短传数量
            'pass_s_speed_max'      => $typeDataes['passShort']['speedMax'],//短传最大速度
            'pass_s_speed_avg'      => $typeDataes['passShort']['speedAvg'],//短传平均速度
            'pass_l_num'            => $typeDataes['passLong']['num'],      //长传数量
            'pass_l_speed_max'      => $typeDataes['passLong']['speedMax'], //长传最大速速
            'pass_l_speed_avg'      => $typeDataes['passLong']['speedAvg'], //长传平均速度
            'touchball_num'         => $typeDataes['touchball']['num'],     //触球数量
            'touchball_speed_max'   => $typeDataes['touchball']['speedMax'],//触球最大速度
            'touchball_speed_avg'   => $typeDataes['touchball']['speedAvg'],//触球平均速度
            'dribble_num'           => count($dribbleFlag['list']),         //触球数量
            'dribble_dis_total'     => 0,                                   //带球总的距离
            'map_pass_short'        => $typeDataes['passShort']['gps'],     //短传图谱
            'map_pass_long'         => $typeDataes['passLong']['gps'],      //长传图谱
            'map_touchball'         => $typeDataes['touchball']['gps']      //触球图谱
        ];

        BaseMatchResultModel::where('match_id',$matchId)->update($matchResult);

        //存储用户全局性的数据
        $userAbility    = BaseUserAbilityModel::find($matchInfo->user_id);
        $userAbility->pass_num_short        += $typeDataes['passShort']['num'];
        $userAbility->pass_num_long         += $typeDataes['passLong']['num'];
        $userAbility->pass_num_total        += ($typeDataes['passShort']['num'] + $typeDataes['passLong']['num']);
        $userAbility->pass_speed_max        =  max($userAbility->pass_speed_max,$typeDataes['passLong']['speedMax']);
        $userAbility->touchball_num_total   += $typeDataes['touchball']['num'];
        $userAbility->dribble_num           += $matchResult['dribble_num'];
        $userAbility->dribble_dis_total     += $matchResult['dribble_dis_total'];
        $userAbility->save();
    }



    /**
     * 保存射门结果
     * @param $matchId integer 比赛ID
     * @return mixed
     * */
    public function save_shoot_result($matchId)
    {
        $shootFile  = self::matchdir($matchId)."result-shoot.txt";
        $shootData  = file_to_array($shootFile);
        $matchInfo  = self::get_temp_match_info($matchId);

        if(count($shootData) == 0){

            return false;
        }

        //射门类型临界距离
        $shootTypeDis   = 8;    //长短处的分割距离

        //传球类型临界距离
        //5人制：8米
        //7人制：14米
        //11人制：20米

        $matchResult    = [
            'shoot_num_short'   => 0,
            'shoot_num_far'     => 0,
            'shoot_num_total'   => 0,
            'shoot_speed_max'   => 0,
            'shoot_speed_avg'   => 0,
            'shoot_dis_max'     => 0,
            'shoot_dis_avg'     => 0,
        ];
        $gpsArr     = [];

        foreach ($shootData as $shoot)
        {

            array_push($gpsArr,['y'=>$shoot[0],'x'=>$shoot[1]]);
            //array_push($gps,['y'=>$lat,'x'=>$lon]);
            $speed      = $shoot[4];
            $distance   = $shoot[7];

            $matchResult['shoot_num_total']++;
            $distance < $shootTypeDis ? $matchResult['shoot_num_short']++ : $matchResult['shoot_num_far']++;
            $matchResult['shoot_speed_max'] = max($speed,$matchResult['shoot_speed_max']);
            $matchResult['shoot_dis_max']   = max($distance,$matchResult['shoot_dis_max']);

            $matchResult['shoot_speed_avg'] += $speed;
            $matchResult['shoot_dis_avg']   += $distance;
        }

        //射门热点图
        $court                  = self::get_court_info($matchInfo->court_id);
        $gpsMap                 = Court::create_gps_map($court->pa,$court->pa1,$court->pd,$court->pd1,$gpsArr);
        foreach($gpsMap as $key => $gps)
        {
            $gpsMap[$key]      = [intval($gps['x']),intval($gps['y'])];
        }
        $gpsMap                 = \GuzzleHttp\json_encode($gpsMap);
        //$matchResult['map_shoot']       = $this->gps_map($matchInfo->court_id,$gpsArr);
        $matchResult['map_shoot']       = $gpsMap;

        $matchResult['shoot_speed_avg'] = bcdiv($matchResult['shoot_speed_avg'], $matchResult['shoot_num_total'],2);
        $matchResult['shoot_dis_avg']   = bcdiv($matchResult['shoot_dis_avg'], $matchResult['shoot_num_total'],2);

        BaseMatchResultModel::where('match_id',$matchId)->update($matchResult);

        //个人全局信息
        $userAbility                    = BaseUserAbilityModel::find($matchInfo->user_id);
        $totalSpeed                     = $userAbility->shoot_num_total * $userAbility->shoot_speed         + $matchResult['shoot_num_total'] * $matchResult['shoot_speed_avg'];
        $totalDis                       = $userAbility->shoot_num_total * $userAbility->shoot_distance_avg  + $matchResult['shoot_num_total'] * $matchResult['shoot_dis_avg'];
        $userAbility->shoot_num_near    = $userAbility->shoot_num_near  + $matchResult['shoot_num_short'];
        $userAbility->shoot_num_far     = $userAbility->shoot_num_far   + $matchResult['shoot_num_far'];
        $userAbility->shoot_num_total   = $userAbility->shoot_num_total + $matchResult['shoot_num_total'];
        $userAbility->shoot_speed       = $totalSpeed / $userAbility->shoot_num_total;
        $userAbility->shoot_speed_max   = max($userAbility->shoot_speed_max,$matchResult['shoot_speed_max']);
        $userAbility->shoot_distance_avg= $totalDis / $userAbility->shoot_num_total;
        $userAbility->save();

        return true;
    }


    /**
     * @param $matchId integer 比赛ID
     * @return mixed
     * */
    public function save_direction_result($matchId)
    {
        //类型，开始方向，结束方向，维度，经度 1	23	30	3131.2356	12132.256458
        $file       = self::matchdir($matchId)."result-turn.txt";
        $resultData = file_to_array($file);
        $matchInfo  = self::get_temp_match_info($matchId);

        if(count($resultData) == 0){

            return false;
        }

        $matchResult    = [
            "change_direction_num"  => 0,   //转向
            "turn_around_num"       => 0,   //转身
        ];

        foreach($resultData as $data)
        {
            $type   = intval($data[0]);
            $type   == 1 ? $matchResult['change_direction_num']++ : $matchResult['turn_around_num']++;
        }

        BaseMatchResultModel::where('match_id',$matchId)->update($matchResult);

        $userAbility                        = BaseUserAbilityModel::find($matchInfo->user_id);
        $userAbility->change_direction_num  += $matchResult['change_direction_num'];
        $userAbility->turn_around_num       += $matchResult['turn_around_num'];
        $userAbility->save();
    }


    /**
     * 保存带球与回追数据
     * @param $matchId integer 比赛ID
     * @return mixed
     * */
    public function save_backrun_result($matchId)
    {
        $file       = self::matchdir($matchId)."result-dribble-backrun.txt";
        $matchData  = file_to_array($file);
        $matchInfo  = self::get_temp_match_info($matchId);

        $matchResult    = [
            "backrun_num"       => 0,
            "backrun_dis_total" => 0
        ];

        foreach($matchData as $data)
        {
            $type   = $data[0];
            $dis    = $data[6];

            if ($type == "2"){

                $matchResult["backrun_num"]++;
                $matchResult['backrun_dis_total']+=$dis;
            }
        }

        BaseMatchResultModel::where('match_id',$matchId)->update($matchResult);

        $userAbility    = BaseUserAbilityModel::find($matchInfo->user_id);


        $userAbility->backrun_num       += $matchResult['backrun_num'];
        $userAbility->backrun_dis_total += $matchResult['backrun_dis_total'];
        $userAbility->save();

        return true;
    }

    /**
     * 保存步数结果
     * @param $matchId integer
     * */
    public function save_step_result($matchId){

        $stepFile   = matchdir($matchId)."result-step.txt";

        $data       = file_get_contents($stepFile);

        $data       = substr($data,0,20);

        if(strlen($data) == 0){

            return ;
        }

        $data       = explode(" ",$data);
        return $data[0];
    }

    /**
     * 创建各种项目的热点图
     * @param  $courtId integer 球场ID
     * @param  $gpsData array GPS列表
     * @return string
     * */
    public function gps_map($courtId,$gpsData,$note='')
    {
        if($courtId == 0)
        {
            return "";
        }

        foreach ($gpsData as $key => $gps){

            $gpsData[$key]  = ['x'=>$gps['lon'],'y'=>$gps['lat']];
        }

        $courtInfo  = self::get_court_info($courtId);

        BaseMatchModel::match_process($courtId,"计算".$note."热点图,球场信息:".\GuzzleHttp\json_encode($courtInfo));



        $gpsData    = Court::create_gps_map($courtInfo->pa,$courtInfo->pa1,$courtInfo->pd,$courtInfo->pd1,$gpsData);

        //长20 宽10
        $result   = [];

        //初始化一个二维数组
        for($i=0;$i<10;$i++)
        {
            for($j=0;$j<20;$j++)
            {
                $result[$i][$j] = 0;
            }
        }

        //1000/20=50   557/10 =57.7 这里的意思是球场比例为1000:557,把球场长度切分为20分，宽度切分为10分，如此得到每份的坐标

        foreach($gpsData as $gps)
        {
            $x =    $gps['x'] / 50;
            $y =    $gps['y'] / 57.7;

            $x =    abs($x) >= 1 ? intval($x) : ceil($x);
            $y =    abs($y) >= 1 ? intval($y) : ceil($y);

            if($x > 0 && $x < 20 && $y > 0 && $y < 10)
            {
                $result[$y][$x] ++ ;
            }
        }

        BaseMatchModel::match_process($courtId,"热点图结果".\GuzzleHttp\json_encode($result));

        return $result;
    }


}