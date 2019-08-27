<?php

namespace App\Jobs;

use App\Models\Base\BaseMatchDataProcessModel;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Base\BaseMatchSourceDataModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ParseData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $fileId;
    private $foot;
    private $type;
    private $matchId;


    /**
     * Create a new job instance.
     * @param $fileId string
     *
     */
    public function __construct($fileId=0)
    {
        $this->fileId = $fileId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //从数据库获取信息

        $fileInfo   = BaseMatchSourceDataModel::find($this->fileId);
        /*
        $fileInfo   = new \stdClass();
        $fileInfo->foot = "R";
        $fileInfo->type = "sensor";
        $fileInfo->match_id = 1329;
        $fileInfo->data = "2019/05/28/1313/compass-L-170-162347.bin";
        //*/
        $this->foot = $fileInfo->foot;
        $this->type = $fileInfo->type;
        $this->matchId= $fileInfo->match_id;

        //获取数据内容

        $content    = Storage::disk('local')->get($fileInfo->data);
        $content    = bin2hex($content);

        //$content    = $this->cut_head($content);

        switch ($fileInfo->type){
            case 'sensor':  $this->parse_sensor($content);   break;
            case 'compass': $this->parse_compass($content);  break;
            case 'gps':     $this->parse_gps($content);      break;
        }

        //如果这条数据属于最后一条数据，启动其他工作的队列

    }

    /**
     * 解析单一类型的数据
     * @param $matchId integer
     * @param $type string
     * @param $foot string
     * */
    public function parse_single_type_data($matchId,$type,$foot){

        $this->foot = $foot;
        $this->type = $type;
        $this->matchId= $matchId;

        $files = DB::table('match_source_data')
            ->where('match_id',$matchId)
            ->where('type',$type)
            ->where('foot',$foot)
            ->orderBy('match_source_id')
            ->get();

        $content = "";
        foreach($files as $file)
        {
            $temp       = Storage::disk('local')->get($file->data);
            $content   .= bin2hex($temp);
        }

        switch ($type){
            case 'sensor':  $this->parse_sensor($content);   break;
            case 'compass': $this->parse_compass($content);  break;
            case 'gps':     $this->parse_gps($content);      break;
        }

        //标记单类型解析完毕
        BaseMatchDataProcessModel::where('match_id',$matchId)->update([$type."_".$foot=>1]);
    }


    /**
     * 解析单条数据
     * @param $fid integer
     * */
    public function parse_single_data($fid){
        $this->fileId = $fid;
        $this->handle();
    }


    public function cut_head($content){

        $datas    = explode(",",$content);
        foreach($datas as $key => $data)
        {
            $p      = strpos($data,"2c");
            $data   = substr($data,$p+2);   //删除2c及以前

            $p      = strpos($data,'2c');
            $data   = substr($data,$p+2);   //删除2c及以前

            //$data   = substr($data,2);//删除两个0

            $datas[$key] = $data;
        }
        return implode("",$datas);
    }

    /**
     * 解析sensor文件
     * @param $content string
     * */
    public function parse_sensor($content)
    {
        //每一条数据的长度为20位 类型：2 x:4,y:4,z:4,校验:2

        $leng       = 20;
        $dataArr    = str_split($content,$leng);
        unset($content);
        $insertData = [];

        $invalidData   = [
            'type'          => '-',
            'timestamp'     => '-',
            'ax'            => 0,
            'ay'            => 0,
            'az'            => 0,
        ];

        foreach($dataArr as $key => $data)
        {
            $singleInsertData                   = $invalidData;
            //$singleInsertData['source_data']    = $data;

            if(strlen($data)<$leng) {

                break;
            }

            $type       = substr($data,0,2);
            $data       = substr($data,2,12);

            if($type == "01" || $type == "00"){ //正文数据

                $single     = str_split($data,4);

                for($i=0;$i<3;$i++)
                {
                    $single[$i] = hexToInt($single[$i],'s');
                }

                list($x,$y,$z)  = $single;

                $singleInsertData['ax']     = bcdiv(bcadd(bcmul($x,488),500),1000,0);
                $singleInsertData['ay']     = bcdiv(bcadd(bcmul($y,488),500),1000,0);
                $singleInsertData['az']     = bcdiv(bcadd(bcmul($z,488),500),1000,0);

            }elseif($type == "88" || $type == "99" || $type == 'aa' || $type == 'bb' || $type == 'cc' ) {

                // 同步 开始 暂停 继续 结束

                $timestamp  = HexToTime($data);
                $singleInsertData['timestamp']  = $timestamp;
                $singleInsertData['type']       = $type;

            }else{

                continue;
            }

            $insertData[] = $singleInsertData;
        }

        $this->save_result($insertData);
    }

    /**
     * 解析罗盘文件
     * @param $content string
     * */
    public function parse_compass($content){
        //$syncTime   = $syncTime + 40;
        $leng       = 28;
        $dataArr    = str_split($content,$leng);
        $content     = [];

        foreach($dataArr as $key => $data)
        {
            $singleData = [
                'type'          => "-",
                'timestamp'     => "-",
                'x'             => 0,
                'y'             => 0,
                'z'             => 0,
            ];

            //$singleData['source_data']  = $data;

            if(strlen($data) < $leng)
            {
                break;
            }

            $type       = substr($data,0,2);
            $data       = substr($data,2,24);
            $data       = str_split($data,8);

            if($type == "00"){

                foreach($data as $key2 => $v2)
                {
                    $data[$key2]  = HexToFloat($v2);
                }

                $singleData['x']    = $data[0];
                $singleData['y']    = $data[1];
                $singleData['z']    = $data[2];


            }elseif($type == '88' || $type == '99' || $type == 'aa' || $type == 'bb' || $type == 'cc'){

                $timestamp  = $data[0].$data[1];
                $timestamp  = HexToTime($timestamp);
                $singleData['timestamp']= $timestamp;
                $singleData['type']     = $type;

            }else{

                continue;
            }

            array_push($content,$singleData);
        }

        $this->save_result($content);
    }


    /**
     * 解析GPS文件
     * @param $content string
     * */
    public function parse_gps($content){

        $dataList       = explode("23232323",$content); //gps才有232323
        $dataList       = array_filter($dataList);

        $content     = [];
        $types          = ["00000000","01000000","02000000","03000000","04000000"];

        foreach($dataList as $key =>  $single)
        {
            //时间（16）长度（8）数据部分（n）
            $timestamp  = substr($single,0,16);
            $length     = substr($single,16,8);


            if(in_array($length,$types)){ //非GPS正式内容


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
                $timestamp  = HexToTime($timestamp);
            }else{

                $data       = substr($single,24);   //数据部分起始
                $data       = strToAscll($data);
                $detailInfo = explode(",",$data);
                $type       = "-";
                $timestamp  = "-";
                //数据即便不合格，也不能丢弃
                if(count($detailInfo)<15) {

                    $lat    = 0;
                    $lon    = 0;

                }else{

                    $lat       = gps_to_gps(floatval($detailInfo[2]));
                    $lon       = gps_to_gps(floatval($detailInfo[4]));
                }
            }

            $content[]  = [
                'type'          => $type,
                'timestamp'     => $timestamp,
                'lat'           => $lat,
                'lon'           => $lon,
            ];
        }
        $this->save_result($content);
    }


    /**
     * 统一保存解析结果
     * @param $content string
     * */
    public  function save_result($content){

        $dir    = matchdir($this->matchId);
        if(!is_dir($dir)){

            mkdir($dir);
        }

        $file = "{$dir}{$this->type}-{$this->foot}.txt";
        $tempStr = "";
        foreach($content as $con){

            $tempStr .= implode(" ",$con)."\n";
        }

        $content = $tempStr;

        file_put_contents($file,$content,FILE_APPEND);
    }


    /**
     * 删除头部数据,解析老数据时使用的
     * @param $file string 要处理的数据
     * @return array
     * */
    function delete_head($file)
    {
        $dataStr    = Storage::disk('local')->get($file);
        $dataArr    = explode(",",$dataStr);
        $datas      = [];

        foreach($dataArr as $key => $data)
        {
            $p      = strpos($data,"2c");
            $data   = substr($data,$p+2);   //删除2c及以前

            $p      = strpos($data,'2c');
            $data   = substr($data,$p+2);   //删除2c及以前

            $datas[$key] = $data;
        }

        return  implode("",$datas);
    }

}
