<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\V1\MatchController;
use App\Http\Controllers\Service\Court;
use App\Http\Controllers\Service\MatchCaculate;
use App\Jobs\AnalysisMatchData;
use App\Jobs\CreateAngle;
use App\Jobs\ParseData;
use App\Jobs\SyncTime;
use App\Models\Base\BaseFootballCourtModel;
use App\Models\Base\BaseMatchModel;
use App\Models\V1\MatchModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Jobs\CommonJob;
use DB;



class zhangweixi extends Command
{
    protected $signature = 'zhang:test';
    protected $description = 'Command description';
    public function __construct()
    {
        parent::__construct();
    }
    public function handle()
    {
        return (new CreateAngle(321))->handle();

        $a = ["compass-R",'compass-L','sensor-R','sensor-L','gps-L'];
        foreach($a as $b){
            (new SyncTime())->resetTime("E:\phpstudy\PHPTutorial\WWW\launchever\api.launchever.cn\public\uploads\match\\321\\".$b.".txt");
        }

        return;

        $files = DB::table('match_source_data')->where('match_id',321)->get();

        foreach($files as $file){

            (new ParseData($file->match_source_id))->handle();
        }

        return;

        return (new ParseData(9653))->handle();

        $data           = ['sourceId'=>8967,'jxNext'=>false];
        return (new AnalysisMatchData('xx',$data))->parse_data();


        return Court::create_visual_match_court(179,177);


        return (new  AnalysisMatchData('xx',[]))->save_run_result(174);
        BaseFootballCourtModel::join_minitor_court(167);exit;

        BaseMatchModel::join_minitor_match(1);
        return;
        return BaseMatchModel::minitor_match();

        return BaseMatchModel::join_minitor_match(30);

        $data           = ['sourceId'=>2550,'jxNext'=>true];

        return (new AnalysisMatchData('xx',$data))->parse_data();



        //return (new Court())->cut_court_to_box_and_create_config(10); //创建配置文件
        BaseMatchModel::match_process(10,"开始解析");
        return;
        echo Court::check_court_is_valid(2.67,6.46);

        return;



        file_put_contents("log.txt",time()."\n",FILE_APPEND);


        //结果文件
        $baseSensorL    = "sensor-L.txt";
        $baseSensorR    = "sensor-R.txt";

        $baseCompassL   = "angle-L.txt";
        $baseCompassR   = "angle-R.txt";
        $baseGps        = "gps-L.txt";

        $files  = [
            "sensor_l"  =>  $baseSensorL,
            "sensor_r"  =>  $baseSensorR,
            "compass_l" =>  $baseCompassL,
            "compass_r" =>  $baseCompassR,
            "gps"       =>  $baseGps,
        ];

        $baseApiUrl     = config('app.apihost')."/uploads/match/1257/";
        $dir            = matchdir(1258);
        mk_dir($dir);
        foreach($files as $f){

            $oldFile    = $baseApiUrl.$f;
            $newFile    = $dir.$f;

            $phpfile    = app_path("Http/Controllers/Service/DownMatchData.php");

            pclose(popen('start /B php '.$phpfile." ".$oldFile." ".$newFile, 'r'));       //windows
        }

        return;

        //pclose(popen("/home/xinchen/backend.php &", 'r'));  //linux


        return (new AnalysisMatchData('xx',['matchId'=>1256]))->save_matlab_result(1256);
        exit;





        (new MatchCaculate())->reset_data_time(matchdir(1238)."gps-L.txt",3,2,100);
        return;
        (new MatchController())->get_weather();

        return;

        //jpush_content("比赛通知","亲，您的比赛已经出结果啦!",4001,1,12,['matchId'=>1187]);

        (new AnalysisMatchData('xx'))->create_gps_map(1189,'L');

        //Court::create_visual_match_court(1189,371);//371 创建虚拟球场

        //(new AnalysisMatchData('xx'))->finish_parse_data(1128);
        //echo (new AnalysisMatchData('xx'))->find_match_by_time("1542613605918");
    }

    //读取SQLlite
    public function read_sqlite(){
        $file = public_path('uploads/device-code/LQF-2018-12--218.sqlite');

        if(!file_exists($file)){
            die("文件不存在");
        }

        $sqlite = new \SQLite3($file);
        $sql =  "SELECT * from DataFactory ";
        $result     = $sqlite->query($sql);
        $devices    = [];

        while($row = $result->fetchArray(SQLITE3_ASSOC) )
        {
            array_push($devices,$row);
        }

        return;
    }
}
