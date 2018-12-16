<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseFootballCourtModel extends Model
{
    protected $table = "football_court";
    protected $primaryKey = "court_id";


    public static function delete_court($courtId){

        //$this->where('court_id',$courtId)->update(['deleted_at',date_time()]);
        self::where('court_id',$courtId)->update(['deleted_at'=>date_time()]);

    }

}
