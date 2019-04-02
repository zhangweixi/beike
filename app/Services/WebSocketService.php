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

    public function onOpen(Server $server, Request $request)
    {
        // 在触发onOpen事件之前Laravel的生命周期已经完结，所以Laravel的Request是可读的，Session是可读写的
        // \Log::info('New WebSocket connection', [$request->fd, request()->all(), session()->getId(), session('xxx'), session(['yyy' => time()])]);

        mylogger("新加入一个用户".$request->fd);

        $server->push($request->fd, 'Welcome to LaravelS');

        // throw new \Exception('an exception');// 此时抛出的异常上层会忽略，并记录到Swoole日志，需要开发者try/catch捕获处理
    }

    public function onMessage(Server $server, Frame $frame)
    {
        // \Log::info('Received message', [$frame->fd, $frame->data, $frame->opcode, $frame->finish]);
        $server->push($frame->fd, date('Y-m-d H:i:s'));
        // throw new \Exception('an exception');// 此时抛出的异常上层会忽略，并记录到Swoole日志，需要开发者try/catch捕获处理

    }


    public function onClose(Server $server, $fd, $reactorId)
    {
        // TODO: Implement onClose() method.
    }
}