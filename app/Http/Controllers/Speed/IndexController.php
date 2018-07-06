<?php
namespace  App\Http\Controllers\Speed;
use App\Http\Controllers\Controller;
use Dingo\Api\Http\Request;
use EasyWeChat;
use \EasyWeChat\Factory;


/**
 * @property \EasyWeChat\Factory $wx
 *
 * */

class IndexController extends Controller{

    private $wx = "";

    public function __construct()
    {
        $this->wx = EasyWeChat::work();


    }


    public function index()
    {

        $info  = $this->wx->department->list();



        return apiData()->set_data('list',$info)->send();

    }




    public function user(Request $request)
    {

        $this->wx->oauth->scopes(['snsapi_userinfo'])
            ->setRequest($request)
            ->redirect();

        $users = $this->wx->oauth->setRequest($request)->user();

        return apiData()->set_data('user',$users)->send();

    }



}
