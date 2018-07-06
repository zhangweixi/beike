<?php
namespace  App\Http\Controllers\Speed;
use App\Http\Controllers\Controller;
use EasyWeChat;



class IndexController extends Controller{

    private $wx = "";

    public function __construct()
    {
        $this->wx = EasyWeChat::work();

    }


    public function index()
    {

        return ['ok190000'];
        $list = $this->wx->department->list();


        return apiData()->set_data('list',$list)->send();

    }




}
