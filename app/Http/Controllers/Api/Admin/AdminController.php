<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\V1\AdminModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;


class AdminController extends Controller
{

    /**
     * 管理员登录
     * */
    public function login(Request $request)
    {
        $name   = $request->input('name');
        $passwd = $request->input('password');

        $passwd = sha1(md5($passwd));
        $adminInfo = AdminModel::where('name',$name)->where('password',$passwd)->first();

        if( ! $adminInfo){

            return apiData()->send(2001,"登录失败");

        }

        $token  = create_member_number($adminInfo->admin_id);

        AdminModel::where('admin_id',$adminInfo->admin_id)->update(['token'=>$token]);

        $adminInfo->token   = $token;

        return apiData()->add('adminInfo',$adminInfo)->send();
    }


    /**
     * 根据TOKEN获取管理员信息
     * */
    public function get_admin_info_by_token(Request $request)
    {
        $admin = AdminModel::where('token',$request->input('token'))->first();

        if($admin){

            return apiData()->add('admin',$admin)->send();

        }else{

            return apiData()->send(2001,"管理员不存在");
        }

    }

    /**
     *
     * */
    public function edit_admin(Request $request)
    {
        $name   = $request->input('name');
        $passwd = $request->input('password');
        $passwd = sha1(md5($passwd));
        $adminId= $request->input('admin_id');
        $real_name=$request->input('real_name');
        if($adminId == 0)
        {
            AdminModel::create(['name'=>$name,'real_name'=>$real_name,'password'=>$passwd,'created_at'=>date_time(),'updated_at'=>date_time()]);

        }


        return apiData()->send();
    }



    public function admin_list(Request $request)
    {
        $admin = AdminModel::get();

        return apiData()->add('admins',$admin)->send();

    }
}