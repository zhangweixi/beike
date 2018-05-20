<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Common\Http;


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

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
            $url        = "http://matlab.launchever.cn/api/caculate?matchId=".$this->sourceId;
            $http       = new Http();
            $response   = $http->send($url);
            var_dump($response);
    }
}

