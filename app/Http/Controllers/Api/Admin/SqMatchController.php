<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\V1\ShequMatchModel;
use Dingo\Api\Http\Request;
use Illuminate\Support\Facades\DB;


class SqMatchController extends Controller{

    /**
     * 获得社区列表
     * */
    public function matches(Request $request)
    {
        $matches    = DB::table('shequ_match as a')
            ->leftJoin('users as b','b.id','=','a.user_id')
            ->select('a.*','b.nick_name')
            ->orderBy('a.sq_match_id','desc')
            ->paginate(20);

        return apiData()->add('matches',$matches)->send();
    }

    /**
     * 获得社区比赛的用户
     * */
    public function match_users(Request $request)
    {
        $matchId    = $request->input('matchId');
        $matchUsers = DB::table('shequ_match_user as a')
            ->leftJoin('users as b','b.id','=','a.user_id')
            ->where('a.sq_match_id',$matchId)
            ->select('a.*','b.nick_name')->get();
        return apiData()->add('matchUsers',$matchUsers)->send();
    }
}
