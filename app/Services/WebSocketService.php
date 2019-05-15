<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/2
 * Time: 11:18
 */
namespace App\Services;

use App\Models\Base\BaseUserSocketModel;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class WebSocketService implements WebSocketHandlerInterface{

    private $errMsg     = "";

    public function __construct()
    {

    }

    public function onClose(Server $server, $fd, $reactorId)
    {
        $socket     = new BaseUserSocketModel();
        $sockInfo   = $socket->detail($fd);
        $socket->unconnect($fd);
        jpush_content("网络状态通知","用户".$sockInfo->user_id."网络已断开:",6002,1,1);
        echo "\n ".$fd."bye bye ,i'm closed\n";
    }

    public function onOpen(Server $server, Request $request)
    {
        // 在触发onOpen事件之前Laravel的生命周期已经完结，所以Laravel的Request是可读的，Session是可读写的
        // \Log::info('New WebSocket connection', [$request->fd, request()->all(), session()->getId(), session('xxx'), session(['yyy' => time()])]);
        echo "wellcome:".$request->fd;
        $socket = new BaseUserSocketModel();
        $socket->connect($request->fd);
    }

    public function onMessage(Server $server, Frame $frame)
    {
        // \Log::info('Received message', [$frame->fd, $frame->data, $frame->opcode, $frame->finish]);
        //1.数据必须是JSON格式
        $data   = $frame->data;
        echo $data."\n";

        $data   = $this->check($data);

        if($data === false){

            $server->push($frame->fd,$this->errMsg);

        }else{

            switch ($data->action){

                case "test":
                    $this->test($server,$frame,$data);                      break;

                case "match/markUserId":    //设备确认身份
                    $this->online_inform($server,$frame,$data);             break;
            }
        }
    }

    public function check($data){

        if(!isJson($data)){

            $data   = ["code"=>5000,"msg"=>"数据不是json格式"];
            $this->errMsg = \GuzzleHttp\json_encode($data);
            return false;
        }

        $data   = \GuzzleHttp\json_decode($data);

        //数据中必须含有action字段
        if(!isset($data->action)){

            $data   = ["code"=>5000,"msg"=>"必须包含action字段"];
            $this->errMsg   = \GuzzleHttp\json_encode($data);
            return false;
        }

        return $data;
    }

    /**
     * 设备联网上线通知
     * */
    public function online_inform($server,$frame,$data){

        $socket     = new BaseUserSocketModel();
        $socket->bind($frame->fd,$data->userId,"device");

        //通知用户，联网成功
        jpush_content("联网通知","用户".$data->userId."联网成功".$data->foot,6001,1,1);

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
