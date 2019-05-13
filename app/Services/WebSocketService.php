<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/2
 * Time: 11:18
 */
namespace App\Services;

use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class WebSocketService implements WebSocketHandlerInterface{

    public function __construct()
    {

    }

    public function onClose(Server $server, $fd, $reactorId)
    {
        echo "\n ".$fd."bye bye ,i'm closed\n";
    }

    public function onOpen(Server $server, Request $request)
    {
        // 在触发onOpen事件之前Laravel的生命周期已经完结，所以Laravel的Request是可读的，Session是可读写的
        // \Log::info('New WebSocket connection', [$request->fd, request()->all(), session()->getId(), session('xxx'), session(['yyy' => time()])]);
        $data = ['name'=>"足浴店老板",'content'=>'欢迎加入漕河泾足浴群'];

        echo $request->fd."\n";

        $server->push($request->fd, \GuzzleHttp\json_encode($data));

        // throw new \Exception('an exception');// 此时抛出的异常上层会忽略，并记录到Swoole日志，需要开发者try/catch捕获处理
    }

    public function onMessage(Server $server, Frame $frame)
    {
        // \Log::info('Received message', [$frame->fd, $frame->data, $frame->opcode, $frame->finish]);
        //1.数据必须是JSON格式
        var_dump(gettype($frame->data));
        echo $frame->data."\n";

        $data   = \GuzzleHttp\json_decode($frame->data);
        echo "--0---\n";

        if(!isJson($data)){

            echo "不是json\n";
            $data   = ["code"=>5000,"数据不是json格式"];
            $server->push($frame->fd,\GuzzleHttp\json_encode($data));

           return false;
        }

        echo "---1---\n";

        //数据中必须含有action字段
        if(!isset($data->action)){

            echo "没有action字段\n";
            $data   = ["code"=>5000,"必须包含action字段"];
            $server->push($frame->fd,\GuzzleHttp\json_encode($data));
            return false;
        }

        echo "---3---\n";

        switch ($data->action){

            case "test":
                $this->test($server,$frame,$data);                      break;

            case "match/markUserId":
                $this->online_inform($server,$frame,$data);             break;
        }

        echo "结束\n";
    }


    /**
     * 设备联网上线通知
     * */
    public function online_inform($server,$frame,$data){

        mylogger($frame->data);

    }
    /**
     * socket测试
     * */
    public function test($server,$frame,$data){

        foreach($server->connections as $fd){

            $server->push($fd,\GuzzleHttp\json_encode($data));
        }
    }
}
