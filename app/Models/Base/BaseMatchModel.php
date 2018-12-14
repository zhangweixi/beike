<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseMatchModel extends Model
{
    public static function match_process($matchId,$action){

        DB::table('match_process')->insert(['match_id'=>$matchId,'action'=>$action,'created_at'=>date_time()]);
    }
}
