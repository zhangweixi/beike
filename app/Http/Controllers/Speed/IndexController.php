<?php
namespace  App\Http\Controllers\Speed;
use App\Http\Controllers\Controller;
use EasyWeChat\Factory as EasyWeChat;
//use EasyWeChat;


class IndexController extends Controller{

    private $wx;

    public function __construct()
    {


        $this->wx = EasyWeChat::work(config('wechat.work'));

    }


    public function index()
    {

        $list = $this->wx->department->list();




        //$work = EasyWeChat::work(); // 企业微信

        //EasyWeChat1::work()->config();

        return apiData()->set_data('list',$list)->set_data('config',config('wechat.work'))->send();

    }




}
