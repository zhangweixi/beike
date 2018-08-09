<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use DB;
class CourtModel extends Model
{

    protected $table        = "football_court";
    protected $primaryKey   = "court_id";
    public  $timestamps = false;//关闭自动维护

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
