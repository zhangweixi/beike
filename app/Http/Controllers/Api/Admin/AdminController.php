<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/30
 * Time: 17:19
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class AdminController extends Controller
{

    /**
     * 管理员登录
     * */
    public function login(Request $request)
    {

        $name   = $request->input('name');
        $passwd = $request->input('passwd');



    }


}