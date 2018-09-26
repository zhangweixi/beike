<?php
namespace App\Common;
use JPush\Client;

class Jpush{

    private $message    = "";
    private $tags       = [];
    public  $alias      = "";
    private $platform   = [];
    private $jpushAppKey= "";
    private $jpushSecret= "";
    public  $plushClient;
    public  $data       = [];


    public function __construct()
    {
        $this->jpushAppKey = config('jpush.appKey');
        $this->jpushSecret = config('jpush.secret');
        $this->plushClient = new Client($this->jpushAppKey,$this->jpushSecret);
        $this->plushClient = $this->plushClient->push();
    }


    /**
     * 设置发送的消息
     * */
    public function set_message($msg)
    {
        $this->message = $msg;
    }

    /**
     * 设置发送的标签
     * */
    public function set_tags($tags)
    {
        $this->tags = $tags;
    }


    /*
     * 发送消息
     * */
    public function send()
    {
        $push = $this->plushClient;

        //设置平台
        if(count($this->platform) == 0){
            $push->setPlatform('all');
        }else{
            $push->setPlatform($this->plat);
        }

        //$push->setApnsProduction

        //设置发生对象
        if($this->alias){
            $push->addAlias($this->alias);
        }elseif(count($this->tags)> 0){
            $push->addTag($this->tags);
        }else{
            $push->addAllAudience();
        }

        $options  = ['badge'=>'+1','sound'=>'sound.caf','extras'=>$this->data];
        $res = $push->setNotificationAlert($this->message)
            ->options(['apns_production'=>true])
            ->iosNotification($this->message,$options)
            ->send();
        return $res;
    }


    /**
     * @param string $title 推送的标题
     * @param string $msg   推送的消息
     * @param int    $code  识别的code
     * @param int    $type  发送的类型 0:所有用户 1:按别名推送(表示单个) 2:按标签推送(多个)
     * @param mixed  $user  用户
     * @param array data    发送给客户端的数据
     * @return array
     * */
    public function pushContent($title,$msg,$code,$type,$user,$data = [])
    {
        $extras      = [
            'code'   => $code,
            'data'   => json_encode($data),
        ];

        $push   = $this->plushClient;
        $push->setPlatform('all')
            ->options(['apns_production' => false])
            ->iosNotification($msg, [
                'title'  => $title,
                //'badge'  => '+1',
                'inbox'  => 2,
                'extras' => $extras
            ])
            ->androidNotification($msg, [
                'title'  => $title,
                //'badge'  => '+1',
                'inbox'  => 2,
                'extras' => $extras
            ]);

        //$push->message($msg,[]); //自定义消息

        if($type == 0)
        {
            $push->addAllAudience();

        } elseif ($type == 1) {

            $push->addAlias($user);

        } elseif($type == 2) {
            if(!is_array())
            {
                $user = [$user];
            }
            $push->addTag($user);
        }

        return $push->send();
    }
}