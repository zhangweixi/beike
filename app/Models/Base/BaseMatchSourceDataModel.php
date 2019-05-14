<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseMatchSourceDataModel extends Model
{
    protected $primaryKey   = "match_source_id";
    protected $table        = "match_source_data";

    static function check_has_save_data($userId,$checkCode)
    {
        $hasFile    = self::where('user_id',$userId)->where('check_code',$checkCode)->first();

        return $hasFile ? $hasFile : false;
    }


    static function has_same_match_same_data($matchId,$checkCode){

        return  self::where('match_id',$matchId)->where('check_code',$checkCode)->has();
    }

}
