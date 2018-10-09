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

            case '':

            break;

            default:
                mylogger($this->action."方法不存在");

        }
    }


}
