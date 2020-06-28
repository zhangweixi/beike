<?php
namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V1\MatchController as V1MatchController;
use App\Models\Base\BaseFootballCourtModel;
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

}
