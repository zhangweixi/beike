<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/31
 * Time: 16:51
 */

namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function users(Request $request)
    {
        $keywords   = $request->input('keywords');

        $users = DB::table('users as a')
            ->leftJoin('user_global_ability as b','b.user_id','=','a.id')
            ->orderBy('a.id');

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
}

