<?php

use Illuminate\Foundation\Inspiring;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');




/*
|---------------------------------------------------------------------------
| 调用matlab计算比赛
|---------------------------------------------------------------------------
|
| 从主服务器获取比赛文件，调用matlab结算比赛结果
|
*/
Artisan::command('matlab:match {matchId}',function($matchId){

    $engine = new \App\Jobs\AnalysisMatchData();
    $engine->run_matlab($matchId);

})->describe('调用matlab计算比赛');




/*
|---------------------------------------------------------------------------
| 调用matlab计算球场
|---------------------------------------------------------------------------
|
| 使用GPS文件获得球场的几个关键点
|
*/
Artisan::command('matlab:court {courtId}',function($courtId){

    $engine = new \App\Jobs\AnalysisMatchData();
    $engine->call_matlab_court_action($courtId);

})->describe('调用matlab计算球场');


/*
|---------------------------------------------------------------------------
| 一次性解析球场的所有数据
|---------------------------------------------------------------------------
|
| 从数据开始的状态一次性解析完所有的数据
|
*/
Artisan::command('match:parse {matchId}',function($matchId){

    $list   = [
        ["type" => "sensor","foot"=> 'R'],
        ["type" => "sensor","foot"=> 'L'],
        ["type" => "compass","foot"=> 'R'],
        ["type" => "compass","foot"=> 'L'],
        ["type" => "gps","foot"=> 'L'],
    ];

    foreach($list as $type){
        //echo "match:run " . $matchId . " " . $type['type'] ." ".$type['foot']."\n";
        artisan("match:run " . $matchId . " " . $type['type'] ." ".$type['foot']);
    }

    artisan("match:run ".$matchId);

})->describe('解析所有比赛数据');