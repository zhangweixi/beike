<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Base\BaseShequMatchUserModel;
use App\Models\Base\BaseUserAbilityModel;
use App\Models\Base\BaseUserModel;
use App\Models\V1\ShequMatchModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Service\Wechat;


class MatchController extends Controller{



    public function get_match_info(Request $request)
    {

        $matchId        = $request->input('matchId');
        $shequModel     = new ShequMatchModel();
        $matchInfo      = $shequModel->find($matchId);

        $users          = $shequModel->get_match_user($matchId);

        $matchInfo->creditText  = "信用".mb_substr(credit_to_text($matchInfo->credit),0,2);
        $matchInfo->users = $users;

        return apiData()->add('matchInfo',$matchInfo)->send();


    }



    public function join_match(Request $request)
    {
        $wexinInfo  = Wechat::wechat_info();

        //$wexinInfo  = new \stdClass(); $wexinInfo->unionid = "o5nq00z3taGvcaYAUvKGpfxshcc8";

        if($wexinInfo == null)
        {
            return apiData()->send(2002,'需微信授权');
        }

        $unionId    = $wexinInfo->unionid;

        $matchId    = $request->input('matchId');


        //检查系统是否存在用户
        $userInfo   = BaseUserModel::where('wx_unionid',$unionId)->first();

        if($userInfo == null)
        {
            return apiData()->send(2001,"请下载APP");
        }

        $userId     = $userInfo->id;
        //检查是否还有名额
        $matchInfo  = ShequMatchModel::find($matchId);

        if($matchInfo->joined_num >= $matchInfo->total_num)
        {
            return apiData()->send(2003,"名额已满");
        }

        //检查是否已参加
        $hasJoin    = BaseShequMatchUserModel::where('sq_match_id',$matchId)->where('user_id',$userInfo->id)->first();
        if($hasJoin != null){

            return apiData()->send(2004,"您已经参加");
        }

        //检查信用是否足够
        if($userInfo->credit < $matchInfo->credit){

            return apiData()->send(2005,"信用分不足");
        }

        //检查能力分是否足够
        $userAbility    = BaseUserAbilityModel::where('user_id',$userId)->select('grade')->first();

        if($matchInfo->grade > 0 && ($userAbility == null || $userAbility->grade < $matchInfo->grade))
        {
            return apiData()->send(2006,"能力分不足");
        }

        $shequMatch = new ShequMatchModel();
        $shequMatch->add_match_user($matchId,$userId);

        return apiData()->send();

    }


}