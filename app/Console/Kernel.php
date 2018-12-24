<?php

namespace App\Console;

use App\Models\Base\BaseFootballCourtModel;
use App\Models\Base\BaseMatchModel;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Base\BaseMatchUploadProcessModel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //1.监控比赛数据的上传状态
        $schedule->call(function(){
            BaseMatchUploadProcessModel::check_match_upload_status();
        })->everyMinute();




        //2.监控比赛数据的计算情况
        $schedule->call(function(){

            BaseMatchModel::minitor_match();

        })->everyMinute();


        //3.监控球场计算进度
        $schedule->call(function(){

            BaseFootballCourtModel::minitor_court();

        })->everyMinute();

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
