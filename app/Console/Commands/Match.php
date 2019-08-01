<?php

namespace App\Console\Commands;

use App\Jobs\AnalysisMatchData;
use App\Models\Base\BaseMatchDataProcessModel;
use App\Models\V1\CourtModel;
use Illuminate\Console\Command;
use App\Jobs\ParseData;
use App\Jobs\CreateAngle;
use App\Jobs\SyncTime;
use App\Jobs\TranslateGps;

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
        $parseEngine= new ParseData();  //解析引擎

        if($type != '-' && $foot != '-'){ //解析单类型数据

            return $parseEngine->parse_single_type_data($matchId,$type,$foot);

        }elseif($fid > 0){  //解析单条数据

            return $parseEngine->parse_single_data($fid);
        }

        // 0.检查数据是否解析完毕
        $this->waiting_parse_finish($matchId);

        $matchInfo  = BaseMatchModel::find($matchId);
        $courtId    = $matchInfo->court_id;
        $courtInfo  = BaseFootballCourtModel::find($courtId);

        //1.同步时间
        $syncApp    = new SyncTime($matchId);
        $syncApp->handle();

        //2.生成罗盘角度
        $angleApp   = new CreateAngle($matchId);
        $angleApp->handle();

        //3.转换GPS的坐标
        $gpsApp     = new TranslateGps($matchId);
        $gpsApp->handle();


        /* 4.球场规则检查
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
        if(empty($courtInfo->boxs)){

            (new Court())->cut_court_to_box_and_create_config($courtId);
        }

        /**6.准备球场配置文 **/
        mylogger("同步球场配置文件开始");
        AnalysisMatchData::sync_court_config($matchId,$courtId);
        mylogger("同步球场配置文件结束");

        /**7.调用matlab **/
        $res = AnalysisMatchData::call_matlab_calculate("match",$matchId);
        mylogger("计算运行结果".$res);
        if($res != "success"){

            BaseMatchModel::match_process($matchId,"角度计算完毕");
            die("调用matlab计算比赛失败");
        }

        /**8.处理结果**/
        (new AnalysisMatchData())->save_matlab_result($matchId);    //处理其他计算结果
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

}
