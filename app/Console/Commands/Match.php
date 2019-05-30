<?php

namespace App\Console\Commands;

use App\Models\Base\BaseMatchDataProcessModel;
use Illuminate\Console\Command;
use App\Jobs\ParseData;
use App\Jobs\CreateAngle;
use App\Jobs\SyncTime;
use App\Jobs\TranslateGps;

use App\Models\Base\BaseMatchSourceDataModel;
use App\Models\Base\BaseMatchModel;
use App\Models\Base\BaseFootballCourtModel;
use App\Http\Controllers\Service\Court;

class Match extends Command
{
    protected $signature    = "match:run {matchId} {type=-} {foot=-} {fid=0}";
    protected $description  = 'parse match data , must input matchid:integer';
    protected $timeout      = 20;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 在执行本端代码之前，数据已经解析成可被使用的数据，所以解析数据的过程是不在本程序之内的
     *
     * @return mixed
     */
    public function handle()
    {
        $matchId    = $this->argument("matchId");
        $type       = $this->argument("type");
        $foot       = $this->argument("foot");
        $fid        = $this->argument("fid");

        if($type != '-' && $foot != '-'){ //解析单条数据

            $this->parse_single_type_data($matchId,$type,$foot);
            $this->finish_parse_type_data($matchId,$type,$foot);
            return ;

        }elseif($fid > 0){

            return $this->parse_single_data();
        }

        // 0.检查数据是否解析完毕
        $this->waiting_parse_finish($matchId);

        $matchInfo  = BaseMatchModel::find($matchId);
        $courtId    = $matchInfo->court_id;
        $courtInfo  = BaseFootballCourtModel::find($courtId);

        $begin      = time();

        //1.同步时间
        $syncApp    = new SyncTime($matchId);
        $syncApp->handle();

        //2.生成罗盘角度
        $angleApp   = new CreateAngle($matchId);
        $angleApp->handle();

        exit();
        //3.转换GPS的坐标
        $gpsApp     = new TranslateGps($matchId);
        $gpsApp->handle();


        /* 4.调用matlab执行任务
         * 开始测量的球场由于信号原因可能无法得到一个正常大小的球场
         * 因而在此对球场的比例及大小进行检查
         * 如果球场不合格，使用GPS实际地图来模拟一个球场
         * */
        if(!Court::check_court_is_valid($courtInfo->width,$courtInfo->length))
        {
            BaseMatchModel::match_process($matchId,"球场无效,width:{$courtInfo->width},height:{$courtInfo->length}，创建虚拟球场");
            Court::create_visual_match_court($matchId,$courtId);
        }


        /* 5.创建球场角度配置文件
         * 算法需要一个球场划分区域与角度的配置文件，在此创建
         * 这个工作原本是在球场测量结束就进行的，放在这里出现的原因是:
         * 有时候测量的球场不达标，需要使用GPS求出的虚拟球场
         * */
        (new Court())->cut_court_to_box_and_create_config($courtId);



        // 6.拷贝一份球场配置文件到数据比赛中
        $configFile = "/".$courtInfo->config_file;
        $newFile    = matchdir($matchId)."court-config.txt";
        copy(public_path($configFile),$newFile);


        // 7.算法系统位于另外一台服务器上，需要通知算法服务器进行计算工作
        $host   = config('app.matlabhost');
        $url    = $host."/api/matchCaculate/run_matlab?matchId=".$matchId;
        file_get_contents($url);


        //3.0 生成热点图占用时间比较久，异步调用
        //self::execute("create_gps_map",['matchId'=>$matchId,'foot'=>"L"]);
    }

    /**
     * 等待数据解析完毕
     * @param $matchId integer
     * */
    public function waiting_parse_finish($matchId){

        for($i = $this->timeout;$i>0;$i--){

            $proc   = BaseMatchDataProcessModel::find($matchId);

            $res    = $proc->gps_L+$proc->sensor_L+$proc->sensor_R+$proc->compass_L+$proc->compass_R;

            if($res == 5){
                break;
            }else{
                sleep(1);
            }
        }

        if($i == 0){ //超时
            mylogger($matchId."——解析超时");
            BaseMatchModel::match_process($matchId,"解析数据时间过久");
            exit;
        }
    }

    /**
     * 标记结束一条数据
     * @param $matchId integer
     * @param $type string
     * @param $foot string
     * */
    public function finish_parse_type_data($matchId,$type,$foot){

        BaseMatchDataProcessModel::where('match_id',$matchId)->update([$type."_".$foot=>1]);
    }


    /**
     * 解析单类型的数据
     * @param $matchId integer
     * @param $type string
     * @param $foot string
     * */
    public function parse_single_type_data($matchId,$type,$foot){

        $parseEngine    = new ParseData();
        $parseEngine->parse_single_type_data($matchId,$type,$foot);

    }

    /**
     * 解析单条数据
     * */
    public function parse_single_data(){

    }

}
