<?php

namespace App\Console\Commands;

use App\Common\MobileMassege;
use Illuminate\Console\Command;

class LinuxMinitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'linux:minitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->desk();
    }

    public function desk() {
        $shellData = shell_exec('df -h');
        $desks = explode("\n",$shellData);
        foreach($desks as $desk) {
            $desk = trim($desk);
            if(preg_match('/^\/dev\/vdb1/', $desk)) {
                $diskInfo = explode(" ",$desk);
                $size = substr($diskInfo[16],0,strlen($diskInfo[16])-1);
                $size = (int)$size;
                if($size > 60) {
                    $mobileMsg = new MobileMassege('15000606942');
                    // deskWarningId
                    $mobileMsg->send_msg('15000606942', config('aliyun.loginTempId'),['code'=>$diskInfo[14]]);
                }
            }
        }
    }
}
