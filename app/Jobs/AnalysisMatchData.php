<?php

namespace App\Jobs;

use App\Models\Base\BaseMatchResultModel;
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
    public $sourceId= 0;    //要处理的比赛的数据
    public $timeout = 80;
    public $saveToDB= false;
    public $fenpi   = true;


    public function __construct($sourceId,$saveToDB = false)
    {
        $this->sourceId = $sourceId;
        $this->saveToDB = $saveToDB;

    }


    public function create_table($userId,$type)
    {
        /*gps_id
        match_id
        lat
        lon
        speed
        direction
        status
        data_key
        source_data
        created_at
        source_id
        timestamp*/

        $table = "user_" . $userId . "_" . $type;

        $hasTable = Schema::connection('matchdata')->hasTable($table);

        if ($hasTable) {
            return true;
        }

        if ($type == 'gps') {
            Schema::connection('matchdata')->create($table, function (Blueprint $table) {

                $table->increments('id');
                $table->integer('match_id');
                $table->integer('source_id');
                $table->string('lat');
                $table->string('lon');
                $table->string('foot');
                $table->string('source_data',500);
                $table->bigInteger('timestamp');
                $table->dateTime('created_at');
            });

        } elseif($type == 'sensor') {

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

            Schema::connection("matchdata")->create($table, function (Blueprint $table) {

                $table->increments('id');
                $table->integer('source_id');
                $table->integer('match_id');
                $table->string('foot');
                $table->double('x');
                $table->double('y');
                $table->double('z');
                $table->string('type');
                $table->string('source_data');
                $table->bigInteger('timestamp');
                $table->dateTime('created_at');
            });
        } elseif($type == 'compass') {

            Schema::connection("matchdata")->create($table, function (Blueprint $table) {

                $table->increments('id');
                $table->integer('source_id');
                $table->integer('match_id');
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
        //解析数据
        $sourceData = DB::table('match_source_data')->where('match_source_id',$this->sourceId)->first();

        $data       = Storage::disk('local')->get($sourceData->data);
        $type       = $sourceData->type;
        $userId     = $sourceData->user_id;

        $this->create_table($userId,$type);

        //1.切分成单组
        $datas  = explode(",",$data);
        $datas  = $this->delete_head($datas);
        $datas  = implode('',$datas);


        //2.获取上一条的数据
        $prevData   = $this->get_prev_sensor_data($userId,$type);
        $datas      = $prevData.$datas;
        //mylogger("开始解析:".time());
        //3.解析数据
        if($type == 'sensor')
        {
            $datas = $this->handle_sensor_data($datas);

        }elseif($type == 'gps'){

            $datas = $this->handle_gps_data($datas);

        }elseif($type == 'compass'){

            $datas = $this->handle_compass_data($datas);
        }

        //mylogger("解析完毕:".time());

        $createdAt      = date_time();
        $dataBaseInfo   = [
            'source_id'     => $this->sourceId,
            'foot'          => $sourceData->foot,
            'created_at'    => $createdAt,
        ];

        $table  = "user_".$userId."_".$type;

        //获得最近的一场比赛
        //4.存储数据 添加其他数据

        //获得最新一次比赛时间
        $matchTimeInfo  = "";


        //多场数据
        $matchResult    = [];

        //dd($datas);
        //这里将数据产生了两份  一份是原始数据datas,一份是新的数据 $matches

        foreach($datas as $key=>$data)
        {
            //获得比赛场次 开始时间 结束时间  如果在两者之间 则为该场比赛的
            loopbegin:

            if($matchTimeInfo
                && $data['timestamp'] >= $matchTimeInfo->time_begin
                && $data['timestamp'] <= $matchTimeInfo->time_end
                && $data['timestamp'] != 0)
            {
                $matchId    = $matchTimeInfo->match_id;
                $matchResult[$matchId]  = 0;

            }elseif($data['timestamp'] != 0){

                $matchTimeInfo = $this->get_match_time($userId,$data['timestamp']);

                //正常情况上传的数据都是一定能够找到时间的，如果找不到时间则表示一定有异常，应该停止
                if(!$matchTimeInfo)
                {
                    echo "时间发生错误:";
                    dd($data);
                    dd("数据".$sourceData->match_source_id.'没有找到对应的比赛,时间为:'.$data['timestamp']);

                }else{

                    goto loopbegin;
                }
            }else{

                $matchId    = 0 ;
            }

            $data['match_id']   = $matchId;
            $datas[$key]         = array_merge($data,$dataBaseInfo);
        }


        //删除GPS最后一条数据,因为这条数据是不合格的
        if($sourceData->is_finish == 1 && $type == 'gps')
        {
            unset($datas[count($datas)-1]);
        }

        //将数据存入到数据库中
        //如果是分批传输，则解析后的内容必须存储在数据库

        //创建数据表
        $this->create_table($userId,$type);
        $db = DB::connection('matchdata')->table($table);

        //分批插入
        $multyData  = array_chunk($datas,1000);
        foreach($multyData as $key => $data)
        {
            $db->insert($data);
        }

        //如何判断一场数据是否传完
        //下一条数据的时间已经大于当前时间


        //上传的最后一条是数据，生成航向角 生成json文件

        if($sourceData->is_finish == 1 && $type != "gps")
        {
            foreach ($matchResult as $matchId => $isFinish)
            {
                if($matchId != 0)
                {
                    //生成所有json文件
                    //$this->create_compass_data($matchId);

                    $this->create_json_data($matchId);
                }
            }
        }


        //如果GPS传输完毕,根据解析的数据生成热点图
        if($sourceData->is_finish == 1 && $type == 'gps' )
        {
            foreach($matchResult as $matchId => $isFinish)
            {
                if($isFinish == 1 && $matchId != 0)
                {
                    $this->create_gps_map($matchId);
                }
            }
        }

        return true;
    }

    /**
     * 所有传输完毕，创建json文件
     * @param $matchId integer 比赛ID
     * */
    public function create_json_data($matchId)
    {
        //将不同类型的数据保存到json
        //如果是分批传输，则结果只能最终一次性获取
        $matchInfo  = MatchModel::find($matchId);
        $userId     = $matchInfo->user_id;

        $foots      = ['R','L'];

        //1.sensor
        $type       = "sensor";
        $table      = "user_".$userId."_".$type;
        foreach($foots as $foot)
        {
            $matchData  = [
                'ax'    => [],
                'ay'    => [],
                'az'    => []
            ];
            DB::connection('matchdata')
                ->table($table)
                ->select('x','y','z')
                ->where('type','A')
                ->where('foot',$foot)
                ->where('match_id',$matchId)
                ->orderBy('id')
                ->chunk(1000,function($data)use(&$matchData)
                {
                    foreach($data as $d)
                    {
                        array_push($matchData['ax'],$d->x);
                        array_push($matchData['ay'],$d->y);
                        array_push($matchData['az'],$d->z);
                    }
                });

            $resultFile = "match/".$matchId."-".$type."-".$foot.".json";
            Storage::disk('web')->put($resultFile,\GuzzleHttp\json_encode($matchData));
        }


        //2.gps
        $type       = "gps";
        $table      = "user_".$userId."_".$type;
        $matchData  = [
            'lat'    => [],
            'lon'    => [],
        ];

        DB::connection('matchdata')
            ->table($table)
            ->select("lat","lon")
            ->where('match_id',$matchId)
            ->orderBy('id')
            ->chunk(1000,function($data)use(&$matchData)
            {
                foreach($data as $d)
                {
                    array_push($matchData['ax'],$d->x);
                    array_push($matchData['ay'],$d->y);
                    array_push($matchData['az'],$d->z);
                }
            });

        $resultFile = "match/".$matchId."-".$type.".json";
        Storage::disk('web')->put($resultFile,\GuzzleHttp\json_encode($matchData));

        return true;
    }


    /**
     * 获得比赛时间
     * @param $userId integer 用户ID
     * @param $dataTime string 往后一次的比赛时间
     * */
    public function get_match_time($userId,$dataTime)
    {

        $dataTime  = substr($dataTime,0,10);
        $dataTime  = date('Y-m-d H:i:s',$dataTime);
        $matchInfo = DB::table('match')
            ->where('user_id',$userId)
            ->where('time_begin',"<=",$dataTime)
            ->where('time_end',">=",$dataTime)
            ->where('deleted_at')
            ->orderBy('match_id','desc')
            ->first();

        if($matchInfo)
        {
            $matchInfo->time_begin = strtotime($matchInfo->time_begin)*1000;
            $matchInfo->time_end   = strtotime($matchInfo->time_end)*1000;
        }
        return $matchInfo;
    }

    /**
     * 删除头部数据
     * @param $datas array 要处理的数据
     * @return array
     * */
    private function delete_head(array $datas)
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
     * @return string
     * */
    private function get_prev_sensor_data($userId,$type)
    {
        $table  = "user_".$userId."_".$type;

        //将上一次未处理的数据加入到这一条中来
        $prevStr    = "";
        $lastData   = DB::connection('matchdata')
            ->table($table)
            ->orderBy('id','desc')
            ->first();

        if($lastData && $lastData->timestamp == 0 && $type!= 'gps')
        {
            DB::connection('matchdata')->table($table)->where('id',$lastData->id)->delete();
            $prevStr =  $lastData->source_data;
        }


        if($lastData && $type == 'gps')
        {
            DB::connection('matchdata')->table($table)->where('id',$lastData->id)->delete();
            $prevStr =  $lastData->source_data;
        }

        return $prevStr;
    }



    private function call_matlab()
    {
        $url        = "http://matlab.launchever.cn/api/caculate?matchId=".$this->sourceId;
        $http       = new Http();
        $response   = $http->send($url);
        var_dump($response);
    }


    /**
     * 读取sensor数据
     * @param $dataSource string 要解析的json数据
     * @return array
     * */
    private function handle_sensor_data($dataSource)
    {
        $leng       = 42;   //每一条数据的长度为42位 类型：2位 x:8,y:8,z:8,time:16
        $dataArr    = str_split($dataSource,$leng);
//        foreach($dataArr as $key => $d)
//        {
//            $str = substr($d,0,2)."  ";
//            $str .= implode('  ',str_split(substr($d,2,24),8))."  ".substr($d,26);
//            $dataArr[$key] = $str;
//        }
        $insertData = [];

        foreach($dataArr as $key => $d)
        {
            if(strlen($d)<$leng)
            {
                $singleInsertData = [
                    'x'             => 0,
                    'y'             => 0,
                    'z'             => 0,
                    'type'          => "",
                    'timestamp'     => 0,
                    'source_data'   => $d,
                ];

            } else {

                $type       = substr($d,1,1);
                $d          = substr($d,2);
                $single     = str_split($d,8);

                for($i=0;$i<3;$i++)
                {
                    $single[$i] = hexToInt($single[$i]);
                }

                $timeStr        = $single[3].$single[4];

                $timestamp      = hexdec(reverse_hex($timeStr));

                if ($type == 1)  //重力感应
                {
                    $type   = 'G';

                } elseif ($type == 0) { //acc 加速度

                    $type   = "A";

                }else{

                    mylogger("错误类型——————".$type);
                    continue;
                }

                $singleInsertData = [
                    'x'             => $single[0],
                    'y'             => $single[1],
                    'z'             => $single[2],
                    'type'          => $type,
                    'timestamp'     => $timestamp,
                    'source_data'   => $d,
                ];
            }
            array_push($insertData,$singleInsertData);
        }
        return $insertData;
    }



    /**
     * 从数据库读取数据并解析成想要的格式
     * */
    private function handle_gps_data($dataSource)
    {
        $dataList    = explode("23232323",$dataSource); //gps才有232323
        $dataList    = array_filter($dataList);

        $insertData     = [];


        foreach($dataList as $key =>  $single)
        {

            //时间（16）长度（8）数据部分（n）
            $time       = substr($single,0,16);
            //$length     = substr(16,24);

            $data       = substr($single,24);   //数据部分起始
            $data       = strToAscll($data);
            $detailInfo = explode(",",$data);

            if(count($detailInfo)<15) //数据即便不合格，也不能丢弃
            {

                $otherInfo  = [
                    'source_data'   => $single,
                    'lat'           => 0,
                    'lon'           => 0,
                    'timestamp'     => strlen($time) == 16 ? hexdec(reverse_hex($time)) : 0
                ];

            }else{

                $timestamp  = hexdec(reverse_hex($time));
                $tlat       = $detailInfo[2];
                $tlon       = $detailInfo[4];
                $otherInfo  = [
                    'source_data'   => $single,
                    'lat'           => floatval($tlat),//gps_to_gps($tlat)*1,
                    'lon'           => floatval($tlon),//gps_to_gps($tlon)*1,
                    'timestamp'     => $timestamp
                ];
            }
            array_push($insertData,$otherInfo);
        }
        return $insertData;
    }


    private function handle_compass_data($dataSource)
    {

        $leng   = 40;
        $dataArr= str_split($dataSource,$leng);
        //dd($dataArr);
        $insertData     = [];

        foreach($dataArr as $key => $data)
        {
            $sourceData     = $data;
            if(strlen($data) < $leng)
            {
                continue;
            }



            $data       = str_split($data,8);

            $timestamp  = hexdec(reverse_hex($data[3].$data[4]));



            foreach($data as $key2 => $v2)
            {
                $data[$key2]  = HexToFloat($v2);
            }

            array_push($insertData,[
                'x' => $data[0],
                'y' => $data[1],
                'z' => $data[2],
                'timestamp'     => $timestamp,
                'source_data'   => $sourceData,
            ]);
        }

        return $insertData;
    }



    public function create_compass_data($matchId)
    {
        $matchModel = new MatchModel();
        $matchInfo  = $matchModel->get_match_detail($matchId);

        $compassTable   = "user_".$matchInfo->user_id."_compass";
        $sensorTable    = "user_".$matchInfo->user_id."_sensor";
        mk_dir(public_path("uploads/temp"));

        //两只脚的数据
        $foots  = ['R','L'];
        foreach($foots as $foot)
        {
            $infile         = public_path("uploads/temp/".$matchId."-".$foot.".txt");
            $outfile        = public_path("uploads/match/".$matchId."-compass-".$foot.".json");

            if(file_exists($infile))
            {
                unlink($infile);
            }

            $id = 0;
            DB::connection('matchdata')
                ->table($compassTable)
                ->where('match_id',$matchId)
                ->where('foot',$foot)
                ->orderBy('id')
                ->chunk(1000,function($compasses) use($sensorTable,$matchId,$id,$infile,$foot)
                {
                    foreach($compasses as $compass)
                    {
                        $timestamp = $compass->timestamp;

                        $sensor = DB::connection("matchdata")
                            ->table($sensorTable)
                            ->where('id',">=",$id)
                            ->where("match_id",$matchId)
                            ->where('foot',$foot)
                            ->where('timestamp',">=",$timestamp)
                            ->where('type','A')
                            ->orderBy('id')
                            ->first();


                        //罗盘之后没有sensor了
                        if($sensor == null)
                        {
                            break;
                        }

                        $id = $sensor->id;
                        $info = [
                            "ax"    => $sensor->x,
                            "ay"    => $sensor->y,
                            "az"    => $sensor->z,
                            "cx"    => $compass->x,
                            "cy"    => $compass->y,
                            "cz"    => $compass->z
                        ];
                        file_put_contents($infile, implode(",",$info)."\n",FILE_APPEND);
                    }
                });

            //由罗盘信息转换成航向角
            $this->compass_translate($infile,$outfile);

        }

        return "success";
    }



    public function compass_translate($infile,$outfile)
    {
        if(file_exists($outfile))
        {
            file_put_contents($outfile,"");
        }

        $command    = "/usr/bin/compass $infile $outfile";


        $res        = shell_exec($command);
        $text       = file_get_contents($outfile);
        $text       = substr($text,0,-2)."]";
        $compass    = json_decode($text,true);

        //转换成经纬度
        $azimuth    = [];
        $pitch      = [];
        $roll       = [];

        foreach($compass as $v)
        {
            array_push($azimuth,$v[0]);
            array_push($pitch,$v[1]);
            array_push($roll,$v[2]);
        }

        $compass    = \GuzzleHttp\json_encode(['azimuth'=>$azimuth,'pitch'=>$pitch,'roll'=>$roll]);
        file_put_contents($outfile,$compass);

        return true;
    }


    /**
     * 生成GPS热点图
     * @param $matchId integer 比赛ID
     * @param $gpsData  array GPS数据
     * @return boolean
     * */
    public function create_gps_map($matchId,array $gpsData = [])
    {

        $matchInfo  = MatchModel::find($matchId);
        $courtInfo  = CourtModel::find($matchInfo->court_id);

        $points     = $courtInfo->boxs;
        $points     =  \GuzzleHttp\json_decode($points);

        $court      = new Court();
        $court->set_centers($points->center);

        //没有数据,从数据库获取
        if(count($gpsData) == 0)
        {
            DB::connection('matchdata')
                ->table('user_'.$matchInfo->user_id."_gps")
                ->where('match_id',$matchId)
                ->where('lat','>',0)
                ->where('lon','>',0)
                ->orderBy('id')
                ->chunk(1000,function($gpsList) use(&$gpsData)
                {
                    foreach($gpsList as $gps)
                    {
                        array_push($gpsData,['lat'=>gps_to_gps($gps->lat),'lon'=>gps_to_gps($gps->lon)]);
                    }
                });
        }
        //dd($gpsData);
        $mapData= $court->court_hot_map($gpsData);
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


    public function failed(Exception $e)
    {

    }
}

