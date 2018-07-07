<?php
namespace  App\Http\Controllers\Speed;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Speed\Weixin;
use DB;


class IndexController extends Controller{


    public function __construct()
    {


    }


    public function index()
    {

        $info  = $this->wx->department->list();

        return apiData()->set_data('list',$info)->send();

    }






    public function user(Request $request)
    {
        $userId = $request->input('userId');
        $userInfo = DB::table('user')->where('user_sn',$userId)->first();
        return apiData()->set_data('userInfo',$userInfo)->send();

    }



}
