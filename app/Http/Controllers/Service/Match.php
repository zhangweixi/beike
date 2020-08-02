<?php

namespace App\Http\Controllers\Service;
use App\Common\Http;
use App\Models\Base\BaseMatchModel;
use App\Models\Base\BaseMatchResultModel;
use App\Models\V1\MatchModel;

class Match
{
    public static function create_compass_angle($infile,$outfile,$compassVersion=0){
        $http   = new Http();
        $url    = config('app.matlabhost').'/compass';
        //$url    = "http://localhost:5000/compass";
        //$url    = "http://dev1.api.launchever.cn/api/matchCaculate/upload";
        $data   = file_get_contents($infile);
        $data   = trim($data);
        $md5    = md5($data);
        $res    = $http->url($url)
            ->method("post")
            ->set_data(["compassVersion"=>$compassVersion,"compassSensorData"=>$data,'md5'=>$md5])
            ->send();
        $res = \GuzzleHttp\json_decode($res);

        if($res->code == 200)
        {
            file_put_contents($outfile,$res->data);
        }

        return $res->code == 200 ? true : false;
    }

    /**
     * 计算比赛的经验值
     * @param $matchId 比赛ID
     * @return float
     */
    public static function calculate_empiric($matchId) {
        $matchInfo = BaseMatchResultModel::where('match_id', $matchId)->select('grade_run','grade_touchball_num','grade_shoot','grade_speed','grade_defense','grade_dribble')->first()->toArray();
        $match = BaseMatchModel::find($matchId);
        $time = $match->time_length / 3600;
        $grade = array_sum($matchInfo);
        //echo $time;exit;
        return  round(($grade / count($matchInfo)) / 100  * $time,2);
    }
}
