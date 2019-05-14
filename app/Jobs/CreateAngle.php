<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateAngle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $matchId;

    /**
     * Create a new job instance.
     * @param $matchId integer
     */
    public function __construct($matchId)
    {
        $this->matchId = $matchId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->compass_sensor_file();
        $this->translate_angle();
    }



    /**
     * @param $foots array
     *
     * */
    public function compass_sensor_file($foots = ["L",'R'])
    {
        $dataDir    = matchdir($this->matchId);

        foreach ($foots as $foot)
        {
            //将所有数据读取到数组中
            $sensorPath = $dataDir."sensor-{$foot}.txt.temp.txt";
            $compassPath= $dataDir."compass-{$foot}.txt.temp.txt";

            $compassList= file_to_array($compassPath);
            $sensorList = file_to_array($sensorPath);

            $sensorArr  = $this->classify_data_by_time($sensorList,4);  //按时间将数据分段
            $compassArr = $this->classify_data_by_time($compassList,4); //罗盘数据时间分段


            //结果文件
            $resultPath = $this->middlefile($foot);
            $fresult    = fopen($resultPath,'w+');

            foreach($compassArr as $stage   => $compass) //按同步周期遍历
            {
                $stageSensors   = $sensorArr[$stage];
                $sensorNum      = count($stageSensors);
                $begin          = 0;

                foreach($compass as $singleCompass)     //遍历每条数据
                {
                    $time           = $singleCompass[3];
                    $currentSensor  = null;

                    for($i=$begin;$i<$sensorNum;$i++){

                        $begin++;

                        $sensor     = $stageSensors[$i];

                        if($sensor[3] > $time){ //两条数据项匹配的原则是第一条时间比compass大的数据和自己匹配

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
        }
    }


    /**
     * 根据时间重新分类数据
     * @param $arr array
     * @param $keynum integer
     * @return array
     * */
    public function classify_data_by_time($arr,$keynum){

        $newArr = [];
        foreach($arr as $data){

            $timeStage  = $data[$keynum];

            if(!isset($newArr[$timeStage]))
            {
                $newArr[$timeStage] = [];
            }
            $newArr[$timeStage][] = $data;
        }
        return $newArr;
    }


    /**
     * @param $foots array
     * */
    public function translate_angle($foots = ["R","L"]){

        foreach($foots as $foot){
            $infile     = $this->middlefile($foot);
            $outfile    = $this->outfile($foot);

            file_put_contents($outfile,"");     //清空历史数据

            $command    = "/usr/bin/compass $infile $outfile > /dev/null && echo 'success' ";

            $res        = shell_exec($command);
            $res        = trim($res);
            if($res != "success"){

                logbug(['msg'=>"比赛".$this->matchId."转换角度失败"]);
            }
        }
    }


    /**
     * 获得中级文件路径
     * @param $foot string
     * @return string
     * */
    private function middlefile($foot)
    {
        return matchdir($this->matchId)."sensor-compass-{$foot}.txt";
    }

    /**
     * 获得结果文件路径
     * @param $foot string
     * @return string
     * */
    private function outfile($foot)
    {
        return  matchdir($this->matchId)."angle-{$foot}.txt";
    }
}
