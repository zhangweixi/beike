<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TranslateGps implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $matchId ;
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
     * 转换GPS坐标
     * @return void
     */
    public function handle()
    {
        $dataDir    = matchdir($this->matchId);
        $inputGps   = $dataDir."gps-L.txt";
        $outGps     = $dataDir."gps-L.txt";
        $cmd        = "node ". app_path('node/gps.js') . " --outtype=file --input={$inputGps} --output={$outGps} ";
        $cmd        = str_replace("\\","/",$cmd);
        $result     = shell_exec($cmd);
    }
}
