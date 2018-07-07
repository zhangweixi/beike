<?php
namespace  App\Http\Controllers\Speed;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Speed\Weixin;


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

        $weixin = new Weixin();

        $userInfo = $weixin->get_wx_info($request);

        return apiData()->set_date('userinfo',$userInfo)->send();
    }



}
