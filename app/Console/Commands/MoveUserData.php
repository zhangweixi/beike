<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MoveUserData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'move:user {uid}';

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
        $uid = $this->argument('uid',0);
        if(!$uid) {
            echo 'please input uid';
            exit;
        }
        //查找用户的所有比赛
        $matches = DB::table('match')->select('match_id')->where('user_id', $uid)->get();
        foreach($matches as $match) {
            //将结果数据移除
            $matchDir = public_path('uploads/match/'.$match->match_id);

            if(is_dir($matchDir)) {
                system('rm -Rf '. $matchDir);
            }
            echo 'delete dir '. $matchDir . "\n";
            //将原始数据移除
            $files = DB::table('match_source_data')->where('match_id', $match->match_id)->pluck('data');
            foreach($files as $file) {
                $file = storage_path('app/'.$file);
                if(file_exists($file)) {
                    unlink($file);
                    echo "delete file ". $file."\n";
                }
            }
        }
    }
}
