<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/2
 * Time: 11:18
 */
namespace App\Services;

use App\Models\Base\BaseMatchUploadProcessModel;
use App\Models\Base\BaseUserSocketModel;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use PhpParser\Node\Expr\Cast\Object_;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * @property \Swoole\WebSocket\Server $serve
 * @property \Swoole\WebSocket\Frame $frame
 *
 * */
class WebSocketService implements WebSocketHandlerInterface{

    private $errMsg     = "";
    private $serve      = null;
    private $frame      = null;

    public function __construct()
    {

    }

    public function onClose(Server $server, $fd, $reactorId)
    {
        $socket     = new BaseUserSocketModel();
        $sockInfo   = $socket->detail($fd);
        if($sockInfo){

            $socket->unconnect($fd);
            //jpush_content("网络状态通知","用户".$sockInfo->user_id."网络已断开:",6002,1,1);
        }
        echo "\n ".$fd."bye bye ,i'm closed\n";
    }

    public function onOpen(Server $server, Request $request)
    {
        // 在触发onOpen事件之前Laravel的生命周期已经完结，所以Laravel的Request是可读的，Session是可读写的
        // \Log::info('New WebSocket connection', [$request->fd, request()->all(), session()->getId(), session('xxx'), session(['yyy' => time()])]);
        echo "wellcome:".$request->fd;
    }

    public function onMessage(Server $server, Frame $frame)
    {
        // \Log::info('Received message', [$frame->fd, $frame->data, $frame->opcode, $frame->finish]);
        //1.数据必须是JSON格式
        $this->serve    = $server;
        $this->frame    = $frame;

        echo "\n".$frame->data."\n";

        $data   = $this->check($frame->data);

        if($data === false){

            $server->push($frame->fd,$this->errMsg);

        }else{

            switch ($data->action){

                case "test":
                    $this->test($server,$frame,$data);                      break;

                case "match/markUserId":        //设备确认身份
                    $this->online_inform($data);                            break;

                case "match/upload_progress":    //通知上传进度
                    $this->inform_upload_progress($data);                    break;
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
     * 联网上线通知
     * @param $data object
     * */
    public function online_inform($data){

        $socket     = new BaseUserSocketModel();

        if($data->client == "device"){

            $type   = $data->foot;

            //存储数据量
            BaseMatchUploadProcessModel::save_total_num($data->userId,$data->foot,$data->dataNum);

            //通知APP设备上线
            $this->inform_device_state($data->userId,$data->foot,1);

        }else{

            $type   = "app";

            $this->push($this->frame->fd,["code"=>200,"msg"=>"AUTH SUCCESS"]);
        }

        $socket->bind($this->frame->fd,$data->userId,$type);

        //通知用户，联网成功
        //jpush_content("联网通知","用户".$data->userId."联网成功".$type,6001,1,1);

        if($type == 'app'){
            $this->inform_upload_progress($data);
        }
    }

    /**
     * 通知APP设备在线状态
     * @param $userId integer
     * @param $foot string
     * @param $state
     * */
    public function inform_device_state($userId,$foot,$state){

        $data   = [
            "action"    => "deviceState",
            "foot"      => $foot,
            "state"     => $state
        ];

        $appfd  = BaseUserSocketModel::get_user_fd($userId,'app');

        if($appfd){
            $this->push($appfd,$data);
        }
    }


    /**
     * 通知APP上传数据的进度
     * @param $data Object
     * */
    public function inform_upload_progress($data){

        $process = BaseMatchUploadProcessModel::get_upload_state($data->userId);

        if(!$process){
            return;
        }

        $uploadData   = [
            "action"    => "uploadProgress",
            "total"     => $process->left_num + $process->right_num,
            "finished"  => $process->finished_num,
            "needTime"  => 0,
            "percent"   => intval(($process->right_finished_num + $process->left_finished_num) / ($process->left_num + $process->right_num) * 100),
        ];

        $fd     = BaseUserSocketModel::get_user_fd($data->userId,"app");

        if($fd){
            $res = $this->push($fd,$uploadData);
            if(!$res){
                mylogger("进度通知：用户".$data->userId."发送失败");
            }else{
                mylogger("进度通知：用户".$data->userId."发送成功，percent:".$uploadData['percent']);
            }
        }else{
            mylogger('进度通知，用户'.$data->userId."没有连接socket");
        }
    }


    /**
     * socket测试
     * */
    public function test($server,$frame,$data){

        foreach($server->connections as $fd){

            $server->push($fd,\GuzzleHttp\json_encode($data));
        }
    }



    public function push($fd,array $data){

        return $this->serve->push($fd,\GuzzleHttp\json_encode($data));
    }
}
