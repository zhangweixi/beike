<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use DB;
class CourtModel extends Model
{

    protected $table = "football_court";


    /*
     * 添加足球场
     * */
    public function add_court($courtData)
    {
        $courtData['created_at']    = date_time();
        $courtId    = DB::table('football_court')->insertGetId($courtData);

        return $courtId;
    }


}
