<?php
namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V1\MatchController as V1MatchController;
use App\Models\Base\BaseFootballCourtModel;
use App\Models\Base\BaseMatchResultModel;
use App\Models\Base\BaseStarTypeModel;
use App\Models\Base\BaseUserModel;
use App\Models\V1\CourtModel;
use App\Models\V1\MatchModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchController extends V1MatchController{
    /**
     * 数据比赛
     * */
    public function match_list(Request $request)
    {
        $matchModel = new MatchModel();
        $userId     = $request->input('userId');

        $matchs     = $matchModel->get_match_list($userId);
        foreach($matchs as $match) {
            $court = CourtModel::where('court_id', $match->court_id)->select('court_name','address')->first();
            if (!$court) {
                $court = new \stdClass();
                $court->court_name = '未知球场';
                $court->address = '';
            }
            $match->courtName = $court->court_name ?: $court->address;
            $match->foot = BaseUserModel::where('id',$userId)->value('foot');
            $match->foot = $match->foot ?: '';
            $match->grade = $match->grade ?: 0;
        }

        return apiData()->set_data('matchs',$matchs)->send(200,'success');
    }

    /**
     * 比赛详细基本信息
     * */
    public function match_detail_base(Request $request)
    {
        $matchId    = $request->input('matchId');
        $matchModel = new MatchModel();
        $matchInfo  = $matchModel->get_match_detail($matchId);

        $matchResult     = BaseMatchResultModel::find($matchId);

        /*全局跑动热点图*/
        if($matchResult) {

            if($matchResult->map_gps_run)
            {
                $map                = \GuzzleHttp\json_decode($matchResult->map_gps_run);
                $map                = data_scroll_to($map,100);

            }else{

                $map                = create_round_array(20,32,true,0);
            }

            $matchInfo->shoot   = $matchResult->grade_shoot ?? 0;
            $matchInfo->pass    = $matchResult->grade_pass ?? 0;
            $matchInfo->strength= $matchResult->grade_strength ?? 0;
            $matchInfo->dribble = $matchResult->grade_dribble ?? 0;
            $matchInfo->defense = $matchResult->grade_defense ?? 0;
            $matchInfo->run     = $matchResult->grade_run ?? 0;

        }else{//默认值

            $map                = create_round_array(20,32,true,0);
            $matchInfo->shoot   = 0;
            $matchInfo->pass    = 0;
            $matchInfo->strength= 0;
            $matchInfo->dribble = 0;
            $matchInfo->defense = 0;
            $matchInfo->run     = 0;
        }

        /*基本信息*/
        $matchInfo->foot = BaseUserModel::where('id', $matchInfo->user_id)->value('foot');
        $matchInfo->advice = '';
        return apiData()
            ->set_data('matchInfo',$matchInfo)
            ->set_data('map',$map)
            ->send(200,'success');
    }

}
