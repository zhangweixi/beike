<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseSuggestionModel extends Model
{
    protected $table = "user_suggestion";
    protected $primaryKey = "id";


    public static function suggestions(){

        return DB::table('user_suggestion as a')
            ->leftJoin('users as b','b.id','=','a.user_id')
            ->select('a.*','b.mobile','b.nick_name')
            ->paginate(20);
    }
}
