<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class MoveMatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'match:del {matchId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'delete the match data';

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
        $matchId    = $this->argument("matchId");

        //移除结果文件
        $resDir    = public_path("uploads/match/".$matchId);
        if(file_exists($resDir)){

            $files      = scandir($resDir);
            array_splice($files,0,2);

            foreach($files as $key =>  $file){
                unlink($resDir."/".$file);
            }
            rmdir($resDir);
        }



        //移除原始文件
        $files      = DB::table('match_source_data')->where('match_id',$matchId)->get();
        $dir        = "";
        foreach($files as $file)
        {
            $file   = base_path("storage/app/".$file->data);
            if(file_exists($file))
            {
                unlink($file);
                $dir = dirname($file);
            }
        }

        if($dir && file_exists($dir)){
            rmdir($dir);
        }
        DB::table('match_source_data')->where('match_id',$matchId)->delete();

        DB::table('match_data_process')
            ->where('match_id',$matchId)
            ->update(["gps_L"=>0,"sensor_L"=>0,"sensor_R"=>0,"compass_R"=>0,"compass_L"=>0]);

        echo "success!!!\n";
    }
}
