<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/30
 * Time: 17:57
 */

namespace App\Http\Controllers\Web;
use Illuminate\Http\Request;


class ShortLinkController
{

    public function index(Request $request,$method)
    {
        switch ($method)
        {
            case "0": return $this->match_invity_by_mobile($request);break;
        }


    }


    /**
     * 通过手机号邀请比赛
     * */
    public function match_invity_by_mobile(Request $request)
    {
        $matchId    = $request->input('I');

        return "比赛ID:".$matchId;
    }


}