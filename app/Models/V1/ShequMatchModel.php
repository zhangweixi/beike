<?php
namespace App\Models\V1;
use Illuminate\Database\Eloquent\Model;
use DB;

class ShequMatchModel extends Model{

    public function add_match($matchData)
    {
        $matchData['created_at']    = date_time();

        $matchId = DB::table('shequ_match')->insertGetId($matchData);

        return $matchId;

    }


}