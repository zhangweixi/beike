<?php

namespace App\Jobs;

use App\Common\Jpush;
use App\Models\V1\ShequMatchModel;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CommonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $action;
    private $data;
    public $timeout = 300;
    public $tries = 1;


    /**
     * Create a new job instance.
     * @param $action string
     * @param $data array
     * @return void
     */
    public function __construct($action,$data=[])
    {
        $this->action   = $action;
        $this->data     = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->action){

            case 'new_match_notice':

                $matchId    = $this->data['matchId'];
                $users      = $this->data['users'];
                $this->new_match_notice($matchId,$users);

            break;

            default:
                mylogger($this->action."方法不存在");

        }
    }


    /**
     * 新比赛通知
     * @param $matchId integer 比赛ID
     * @param $users array 用户ID
     * */
    private function new_match_notice($matchId,$users = [])
    {
        $jpush      = new Jpush();
        $matchInfo  = ShequMatchModel::find($matchId);

        foreach($users as $userId)
        {
            $jpush->pushContent("新比赛提醒",$matchInfo->address."球场".$matchInfo->begin_time."有一场足球比赛，去瞧瞧吧！",3001,1,$userId,['matchId'=>$matchId]);
            sleep(1);
        }
    }
}
