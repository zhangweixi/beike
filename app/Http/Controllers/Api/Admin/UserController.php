<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/31
 * Time: 16:51
 */

namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\Controller;
use App\Models\Base\BaseSuggestionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function users(Request $request)
    {
        $keywords   = $request->input('keywords');

        $users = DB::table('users as a')
            ->leftJoin('user_global_ability as b','b.user_id','=','a.id')
            ->orderBy('a.id','desc');

        if($keywords)
        {
            $users->where(function($db) use ($keywords)
            {
                $keywords   = "%{$keywords}%";
                $db->where('a.nick_name','like',$keywords)->orWhere('a.mobile','like',$keywords);
            });
        }

        $users  = $users->paginate(20);

        return apiData()->add('users',$users)->send();
    }


    /**
     * 用户反馈列表
     * */
    public function suggestions(Request $request){

        $suggestions = BaseSuggestionModel::suggestions();

        return apiData()->add("suggestions",$suggestions)->send();
    }
}

