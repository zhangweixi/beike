<?php

namespace App\Jobs;

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

class AnalysisMatchData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $tries   = 3;
    public $sourceId= 0;    //要处理的比赛的数据
    public $timeout = 50;

    public function __construct($sourceId)
    {
        $this->sourceId = $sourceId;
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
                $table->double('speed');
                $table->string('direction');
                $table->tinyInteger('status');
                $table->string('source_data');
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
        //return "ok";
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
        mylogger("开始解析:".time());
        //3.解析数据
        if($type == 'sensor')
        {
            $datas = $this->handle_sensor_data($datas);

        }elseif($type == 'gps'){

            $datas = $this->handle_gps_data($datas);

        }elseif($type == 'compass'){

            $datas = $this->handle_compass_data($datas);
        }

        mylogger("解析完毕:".time());

        $createdAt      = date_time();
        $dataBaseInfo   = [
            'source_id'     => $this->sourceId,
            'created_at'    => $createdAt,
        ];

        $table  = "user_".$userId."_".$type;

        //获得最近的一场比赛
        //4.存储数据 添加其他数据

        //获得最新一次比赛时间
        $matchTimeInfo  = "";
        $this->create_table($userId,$type);

        $beginTime  = time();
        $matches    = [];

        $validColum     = $this->validColum[$type];

        //dd($datas);

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

            }elseif($data['timestamp'] != 0){

                $matchTimeInfo = $this->get_match_time($userId,$data['timestamp']);

                if(!$matchTimeInfo)
                {
                    return false;
                }

                goto loopbegin;

            }else{

                $matchId    = 0 ;
            }



            $validData  = [];//有效数据
            foreach($validColum as $colum)
            {
                if(isset($data[$colum]))
                {
                    if($type == 'sensor'){

                        $validData[strtolower($data['type']).$colum] = $data[$colum];

                    }else{

                        $validData[$colum] = $data[$colum];

                    }

                }
            }


            foreach($validData as $validKey => $validValue)
            {
                $matches['result-'.$matchId] ?? $matches['result-'.$matchId] = [];
                $matches['result-'.$matchId][$validKey] ?? $matches['result-'.$matchId][$validKey] = [];

                array_push($matches['result-'.$matchId][$validKey],$validValue);
            }
        }

        foreach($matches as $key => $matchData)
        {
            $resultFile = "match/".$key."-".$type.".json";
            Storage::disk('web')->put($resultFile,\GuzzleHttp\json_encode($matchData));
        }

        return true;

        mylogger("查询时间所消耗:".time());
        $multyData  = array_chunk($datas,10000);
        mylogger("切割消耗时间:".time());
        $db = DB::connection('matchdata')->table($table);

        foreach($multyData as $key => $data)
        {
            $db->insert($data);
            mylogger("插入-".$key.":".time());
        }

        mylogger("插入数据完毕:".time());
    }


    public function handle1()
    {
        //解析数据
        $sourceData = DB::table('match_source_data')->where('match_source_id',$this->sourceId)->first();
        //return "ok";
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
            'created_at'    => $createdAt,
        ];

        $table  = "user_".$userId."_".$type;

        //获得最近的一场比赛
        //4.存储数据 添加其他数据

        //获得最新一次比赛时间
        $matchTimeInfo  = "";
        $this->create_table($userId,$type);

        $beginTime  = time();
        $matches    = [];

        $validColum     = $this->validColum[$type];



        foreach($datas as $key=>$data)
        {
            //获得比赛场次 开始时间 结束时间  如果在两者之间 则为该场比赛的
            loopbegin:
            //mylogger('begin'.$key);
            if($matchTimeInfo
                && $data['timestamp'] >= $matchTimeInfo->time_begin
                && $data['timestamp'] <= $matchTimeInfo->time_end
                && $data['timestamp'] != 0)
            {
                $matchId    = $matchTimeInfo->match_id;

            }elseif($data['timestamp'] != 0){

                $matchTimeInfo = $this->get_match_time($userId,$data['timestamp']);

                if(!$matchTimeInfo)
                {
                    mylogger('没有找到对应的比赛时间');
                    return false;
                }

                goto loopbegin;

            }else{

                $matchId    = 0 ;
            }


            $data['match_id']   = $matchId;

            $validData  = [];//有效数据
            foreach($validColum as $colum)
            {
                if(isset($data[$colum]))
                {
                    if($type == 'sensor'){

                        $validData[strtolower($data['type']).$colum] = $data[$colum];

                    }else{

                        $validData[$colum] = $data[$colum];

                    }

                }
            }


            foreach($validData as $validKey => $validValue)
            {
                $matches['result-'.$matchId] ?? $matches['result-'.$matchId] = [];
                $matches['result-'.$matchId][$validKey] ?? $matches['result-'.$matchId][$validKey] = [];

                array_push($matches['result-'.$matchId][$validKey],$validValue);
            }

           // mylogger('end'.$key);
            $datas[$key] = array_merge($dataBaseInfo,$data);
        }

        //mylogger("开始创建json");


        foreach($matches as $key => $matchData)
        {
            $resultFile = "match/".$key."-".$type.".json";
            //mylogger('创建json文件:'.$resultFile);
            Storage::disk('web')->put($resultFile,\GuzzleHttp\json_encode($matchData));
        }


        //mylogger('over');

        //mylogger("查询时间所消耗:".time());
        $multyData  = array_chunk($datas,1000);
        //mylogger("切割消耗时间:".time());
        $db = DB::connection('matchdata')->table($table);

        foreach($multyData as $key => $data)
        {
            $db->insert($data);
            //mylogger("插入-".$key.":".time());
        }

        mylogger("插入数据完毕:".time());
    }
    private $validColum     = [
        'gps'       => ['lat','lon'],
        'sensor'    => ['x','y','z'],
        'compass'   => ['x','y','z']
    ];


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

        if($lastData && $lastData->timestamp == 0)
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
                    'lat'      => 0,
                    'lon'     => 0,
                    'speed'         => 0,
                    'direction'     => "",
                    'status'        => 0,
                    'timestamp'     => 0
                ];
            }else{


                $timestamp  = hexdec(reverse_hex($time));


                $tlat       = $detailInfo[2];
                $tlon       = $detailInfo[4];
                $tspe       = $detailInfo[11];
                $tdir       = $detailInfo[3]."/".$detailInfo[5];

                $otherInfo  = [
                    'source_data'   => $single,
                    'lat'      => $tlat,
                    'lon'     => $tlon,
                    'speed'         => $tspe?$tspe : 0,
                    'direction'     => $tdir,
                    'status'        => $detailInfo[6],
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

}

