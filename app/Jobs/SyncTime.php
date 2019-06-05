<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use swoole\Coroutine as co;

class SyncTime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $matchId ;

    /**
     * Create a new job instance.
     * @param $matchId integer
     */
    public function __construct($matchId=0)
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
        $dir    = matchdir($this->matchId);
        $files  = ['compass-L','compass-R','sensor-L','sensor-R','gps-L'];

        foreach($files as $file){

            $resultFile = $dir.$file.".txt.temp";
            $sourceFile = $dir.$file.".txt";
            $backFile   = $dir."back.".$file.".txt";
            $this->resetTime($sourceFile,$resultFile);

            //将历史数据设置为备份
            //copy($sourceFile,$backFile);
            copy($resultFile,$sourceFile);
            unlink($resultFile);
        }
    }


    /**
     * 根据同步时间给每条数据赋值时间
     * @param $file string
     * @param $resultFile string
     * */
    public function resetTime($file,$resultFile){

        //按行读取文件
        $f          = fopen($file,'r');
        $nf         = fopen($resultFile,'w');
        $cache      = [];
        $timeEnd    = 0;
        $minuts     = 0;
        $isFirst    = true;
        bcscale(0);

        while(!feof($f)){

            //找到两个同步时间
            $line   = trim(fgets($f));
            if($line == ""){
                continue;
            }

            $info   = explode(" ",$line);
            $type   = $info[0];
            $time   = $info[1];

            if($type == "-"){   //如果没有遇到同步时间，将数据放入缓存中

                array_splice($info,0,2);
                array_push($cache,$info);

                continue;
            }

            //赋值时间
            $timeBegin  = $timeEnd;
            $timeEnd    = $time;
            $dataNum    = count($cache);

            if($timeBegin > 0 && $dataNum > 0){

                $timedis    = $timeEnd - $timeBegin;

                foreach($cache as $key => $line){

                    $time    = bcadd($timeBegin,bcmul($key, bcdiv($timedis,$dataNum)));

                    $line = implode(" ",$line)." {$time} {$minuts}";
                    if($isFirst){
                        $isFirst = false;
                    }else{
                        $line = "\n".$line;
                    }

                    fwrite($nf,$line);
                }

                $cache  = [];
                $minuts++;
            }
        }
        fclose($f);
        fclose($nf);
    }
}
