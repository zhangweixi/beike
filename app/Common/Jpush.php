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


    public function __construct(){
        $this->jpushAppKey = config('app.pushAppkey');
        $this->jpushSecret = config('app.pushSecret');
        $this->plushClient = new Client($this->jpushAppKey,$this->jpushSecret);
        $this->plushClient = $this->plushClient->push();
    }


    /**
     * 设置发送的消息
     * */
    public function set_message($msg){
        $this->message = $msg;
    }

    /**
     * 设置发送的标签
     * */
    public function set_tags($tags){
        $this->tags = $tags;
    }


    /*
     * 发送消息
     * */
    public function send(){
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
     * @param string $alias 推送的别名
     * @param int    $type  发送的类型 0:所有用户 1:按别名推送(表示单个) 2:按标签推送(多个)
     * @param array  $tags  推送标签
     * @param array data    发送给客户端的数据
     * @return boolean
     * */
    public function pushContent($title,$msg,$code,$type,$alias = "",$tags = [],$data = [])
    {

        $extras      = [
            'code'      => $code,
            'message'   => json_encode($data),
        ] ;
        $push   = $this->plushClient;
        if($type == 0)
        {
            $result = $push->setPlatform('all')
                ->options(['apns_production' => true])
                ->addAllAudience()
                ->iosNotification($msg, [
                    'title'  => $title,
                    'sound'  => 'sound',
                    'badge'  => '+1',
                    'extras' => $extras
                ])
                ->androidNotification($msg, [
                    'title'  => $title,
                    'sound'  => 'sound',
                    'badge'  => '+1',
                    'inbox'  => 2,
                    'extras' => $extras
                ])->send();
        }
        elseif ($type == 1)
        {
            $result = $push->setPlatform('all')
                ->addAlias($alias)
                ->options(['apns_production' => true])
                ->iosNotification($msg, [
                    'title'  => $title,
                    'sound'  => 'sound',
                    'badge'  => '+1',
                    'extras' => $extras
                ])
                ->androidNotification($msg, [
                    'title'  => $title,
                    'sound'  => 'sound',
                    'badge'  => '+1',
                    'inbox'  => 2,
                    'extras' => $extras
                ])->send();

        } elseif($type == 2) {

            $result = $push->setPlatform('all')
                ->addTag($tags)
                ->options(['apns_production' => true])
                ->iosNotification($msg, [
                    'title'  => $title,
                    'sound'  => 'sound',
                    'badge'  => '+1',
                    'extras' => $extras
                ])
                ->androidNotification($msg, [
                    'title'  => $title,
                    'sound'  => 'sound',
                    'badge'  => '+1',
                    'inbox'  => 2,
                    'extras' => $extras
                ])->send();
        }
        return $result;
    }
}