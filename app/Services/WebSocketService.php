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
    	file_put_contents('/data/www/dev.api.launchever.cn/public/logs/my.txt',time().':init socket\n');
    }

    public function onOpen(Server $server, Request $request)
    {
        // 在触发onOpen事件之前Laravel的生命周期已经完结，所以Laravel的Request是可读的，Session是可读写的
        // \Log::info('New WebSocket connection', [$request->fd, request()->all(), session()->getId(), session('xxx'), session(['yyy' => time()])]);

        $server->push($request->fd, '欢迎加入漕河泾足浴群');

        // throw new \Exception('an exception');// 此时抛出的异常上层会忽略，并记录到Swoole日志，需要开发者try/catch捕获处理
    }

    public function onMessage(Server $server, Frame $frame)
    {
        // \Log::info('Received message', [$frame->fd, $frame->data, $frame->opcode, $frame->finish]);
        echo "收到:".$frame->data."\n";

        $server->push($frame->fd, date('Y-m-d H:i:s'));
        foreach($server->connections as $fd){
            $server->push($fd,$frame->data);
        }


    }


    public function onClose(Server $server, $fd, $reactorId)
    {
    	echo "\n ".$fd."bye bye ,i'm closed\n";
    }
}
