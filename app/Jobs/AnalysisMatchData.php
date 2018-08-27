<?php

namespace App\Jobs;

use App\Models\Base\BaseMatchDataProcessModel;
use App\Models\Base\BaseMatchResultModel;
use App\Models\Base\BaseMatchSourceDataModel;
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
    private $matlabHost = "matlab.launchever.cn";


    public function __construct($action,$param = [])
    {
        $this->action   = $action;

        foreach($param as $key => $v)
        {
            if($key == "host")
            {
                $v = "http://".$v;
            }
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

            case 'init_matlab':             $this->init_matlab($this->matchId);                         break;
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
        if($sourceData->status != 0 ) {

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

        //判断同类型的上一条数据是否解析完毕
        $prevSourceDataId   = 0;

        while (true){

            if($prevSourceDataId > 0){

                $prevSourceData = BaseMatchSourceDataModel::find($prevSourceDataId);

            }else{

                $prevSourceData = DB::table('match_source_data')
                    ->select('match_source_id','status')
                    ->where('match_source_id',"<",$this->sourceId)
                    ->where('user_id',$userId)
                    ->where('foot',$foot)
                    ->where('type',$type)
                    ->where('device_sn',$sourceData->device_sn)
                    ->orderBy('match_source_id','desc')
                    ->first();
            }

            //在此之前没有未处理的数据
            if($prevSourceData == null || $prevSourceData->status == 2) {

                break;

            }else{

                $prevSourceDataId   = $prevSourceData->match_source_id;
                sleep(1);
            }
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


            if($data['type'] == "E")
            {
                $matchesData[$matchId]['isFinish']  = 1;
            }

            if($data['type'] == '')
            {
                switch ($type)
                {
                    case "gps":     $str = $data['lat']." ".$data['lon'];               break;
                    case "sensor":  $str = $data['ax']." ".$data['ay']." ".$data['az']; break;
                    case "compass": $str = $data['x']." ".$data['y']." ".$data['z'];    break;
                }

                //$str.= " ".$data['timestamp'];
                file_put_contents($file,$str."\n",FILE_APPEND);

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

        if($this->host != "")
        {
            $nextData = DB::table('match_source_data')
                ->where('user_id',$userId)
                ->where('foot',$foot)
                ->where('type',$type)
                ->where('status',0)
                ->where('match_source_id','>',$this->sourceId)
                ->orderBy('match_source_id')
                ->first();

            if($nextData)
            {
                $params = ['matchSourceId'=>$nextData->match_source_id];
                $params = http_build_sign($params);
                $url    = $this->host."/api/matchCaculate/jiexi_single_data?".$params;
                file_get_contents($url);
            }
        }

        /*========本条数据解析完毕，请求解析下一条数据 end =====*/


        //如何判断一场数据是否传完
        //下一条数据的时间已经大于当前时间


        //上传的最后一条是数据，生成航向角 生成json文件

        foreach($matchesData as $key => $matchData)
        {
            $matchId = $matchData['matchId'];

            if($matchData['isFinish'] == 0 || $matchId == 0)
            {
                continue;
            }

            //sensor，罗盘都上传完毕，开始计算方向角

            //更新数据解析进度
            $matchProcess   = BaseMatchDataProcessModel::find($matchId);
            if($matchProcess){

                BaseMatchDataProcessModel::where('match_id',$matchId)->update([$type."_".$foot => 1]);

            }else{

                BaseMatchDataProcessModel::insert(['match_id'=>$matchId,$type."_".$foot=>1]);
            }


            //生成匹配文件耗时太多，不能在一个线程里单独完成
            //生成罗盘和sensor的匹配文件
            if($this->host)
            {
                $params         = ['matchId'=>$matchId,'foot'=>$foot];
                $params         = http_build_sign($params);
                $createCompassUrl   = $this->host."/api/matchCaculate/create_compass_sensor?".$params;
                file_get_contents($createCompassUrl);
            }


            //如果已经生成了航向角的文件，则发起调用算法系统

            //$this->call_matlab($matchId);


            //生成对应的json文件
            //$this->create_json_data($matchId,[$type],$foot);

            //如果GPS传输完毕,根据解析的数据生成热点图
            if($type == 'gps')
            {
                $params = ['matchId'=>$matchId,'foot'=>$foot];
                $params = http_build_query($params);
                $url    = $this->host."/api/matchCaculate/create_gps_map?".$params;
                file_get_contents($url);
                //$this->create_gps_map($matchId,$foot);
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
     * 生成计算角度的罗盘和sensor数据
     * @param $matchId integer 比赛ID
     * @param $foot string 脚
     * @return boolean
     * */
    public function create_compass_sensor($matchId,$foot)
    {
        //检查sensor和compass是否都解析完毕
        $matchProcess   = BaseMatchDataProcessModel::find($matchId);
        $sensorColum    = "sensor_".$foot;
        $compassColum   = "compass_".$foot;

        if($matchProcess == null ||
            $matchProcess->$sensorColum == 0 ||
            $matchProcess->$compassColum == 0)
        {

            return true;
        }


        //sensor数据
        $sensorPath = public_path("uploads/match/{$matchId}/sensor-{$foot}.txt");
        $fsensor    = fopen($sensorPath,'r');
        //将所有数据读取到数组中
        $sensors    = file($sensorPath);


        //罗盘数据
        $compassPath= public_path("uploads/match/{$matchId}/compass-{$foot}.txt");
        $fcompass   = fopen($compassPath,'r');


        //结果文件
        $resultPath = public_path("uploads/match/{$matchId}/sensor-compass-{$foot}.txt");
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


            if($newp > $maxlength){

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

        //计算角度
        $outFile    = public_path("uploads/match/{$matchId}/angle-{$foot}.txt");
        $this->compass_translate($resultPath,$outFile);

        //角度计算完毕，请求调用算法系统
        $this->call_matlab($this->matchId);
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


        if(file_exists($outfile))
        {
            file_put_contents($outfile,"");
        }

        $command    = "/usr/bin/compass $infile $outfile > /dev/null && echo 'success' ";

        $res        = shell_exec($command);
        $res        = trim($res);

        return $res == "success" ? true : false ;
    }




    private function call_matlab($matchId)
    {
        //检查2个文件是否存在
        $ANG_R      = public_path("uploads/match/{$matchId}/angle-R.txt");
        $ANG_L      = public_path("uploads/match/{$matchId}/angle-L.txt");

        if(file_exists($ANG_L) && file_exists($ANG_R))
        {
            $params     = http_build_sign(['matchId'=>$matchId]);
            $url        = "http://{$this->matlabHost}/api/matchCaculate/call_matlab?".$params;
            $res        = file_get_contents($url);
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
                array_push($gpsData,['lat'=>$gpsInfo[0],'lon'=>$gpsInfo[1]]);
            }
        }


        $court      = new Court();
        $court->set_centers($points->center);
        $mapData    = $court->court_hot_map($gpsData);

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
     * */
    public function init_matlab($matchId)
    {
        mylogger("我已经成功啦");
    }
}

