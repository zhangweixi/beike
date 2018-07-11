<?php
namespace App\Http\Controllers\Speed;

use App\Http\Controllers\Controller;
use Cyberduck\LaravelExcel\Factory\ImporterFactory;
use Illuminate\Http\Request;
use DB;
use Cyberduck\LaravelExcel\Exporter;
use Cyberduck\LaravelExcel\Importer;





class AdminController extends Controller{


    public function login(Request $request)
    {

        $name       = $request->input('name');
        $password   = $request->input('password');

        $password   = sha1(md5($password));

        $info       = DB::table('admin')->where('name',$name)->where('password',$password)->first();

        if($info)
        {
            $token  = create_token($info->admin_id);
            DB::table('admin')->where('admin_id',$info->admin_id)->update(['token'=>$token]);
            return apiData()->set_data('adminToken',$token)->send();

        }

        return apiData()->send(4001,'账户不存在或密码错误');
    }



    /**
     * 创建管理员
     * */
    public function create_admin(Request $request)
    {

        $name = $request->input('name');
        $pass = $request->input('password');
        $type = $request->input('type',0);

        $data = [

            'name'      => $name,
            'password'  => sha1(md5($pass)),
            'type'      => $type,
            'created_at'=> date_time()
        ];

        $id = DB::table('admin')->insertGetId($data);

        return apiData()->send();
    }

    /**
     * 上传EXCEL
     *
     * */
    public function read_question(Request $request)
    {


        $file = "E:\phpstudy\PHPTutorial\WWW\launchever\api.launchever.cn\public\/uploads/speed/QG9sQv0LmkRDXRvfURmjfCJIyGwVQcwxlAb4PIGx.xlsx";


        $fileInfo = Excel::load($file);
        dd($fileInfo);



    }



}