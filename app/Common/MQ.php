<?php
namespace App\Common;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


class MQ{

    private static $channel = null;
    private static $mq = null;
    public static $error = null;

    static function connection(){

        if(is_null(self::$channel)) {

            $rabbit     = config('rabbitmq');
            $mq = new AMQPStreamConnection($rabbit['host'],$rabbit['port'],$rabbit['user'],$rabbit['password']);
            $channel    = $mq->channel();
            self::$channel = $channel;
        }
        return self::$channel;
    }

    static function close(){
        self::$channel->close();
        self::$mq->close();
        self::$channel = null;
        self::$mq = null;
    }

    static function send($queue,$msg,$exchange=''){

        self::connection();
        self::$channel->queue_declare($queue,false, false, false, false);
        if(is_array($msg)){
            $msg = \GuzzleHttp\json_encode($msg);

        }elseif(!is_string($msg)){
            self::$error = "消息必须为字符和数组";
            return false;
        }

        $msg    = new AMQPMessage($msg);
        self::$channel->basic_publish($msg,$exchange,$queue);
    }
}