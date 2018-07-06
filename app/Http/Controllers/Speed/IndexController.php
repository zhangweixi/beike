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

        return apiData()->set_data('file',__FILE__)->send();
        $list = $this->wx->department->list();

        return config('wechat.work');

        //$work = EasyWeChat::work(); // 企业微信

        //EasyWeChat1::work()->config();

        return $list;

    }




}
