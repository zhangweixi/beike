<?php
namespace App\Common;
use Illuminate\Validation\Validator;
/**
 * 执行curl
 */
class Http{

    public $type = "post";
    public $data = [];
    public $post = false;
    private $ch;

    public function __construct(){
        $this->ch = curl_init();
    }
    /*
     * @param data array 要传递的参数
     * */
    public function set_data($data){
        $this->data = $data;
    }


    /*
     * @param  data array
     * */
    public function set_header($header){
        foreach($header as $key => $v){
            $he = $key.":".$v;
            curl_setopt($this->ch,CURLOPT_HTTPHEADER,$he);
        }
    }



    public function send($url){

        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_TIMEOUT,30);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($this->ch, CURLOPT_HEADER,0);

        if($this->post){
            curl_setopt($this->ch,CURLOPT_POST,true);               //开启post
            curl_setopt($this->ch,CURLOPT_POSTFIELDS,$this->data);  //设置参数
            curl_setopt($this->ch,CURLOPT_HTTPHEADER,['Content-type:multipart/form-data']);     //设置头部
            curl_setopt($this->ch,CURLOPT_HTTPHEADER,['Accept:application/json']);              //设置头部
        }

        $res = curl_exec($this->ch);
        curl_close($this->ch);
        return $res;
    }

    public function sock($host,$url){

        $fp = fsockopen($host, 80, $errno, $errstr, 30);
        if (!$fp)
        {
            return false;
        }
        else
        {
            stream_set_blocking($fp,0);
            $http = "GET {$url} HTTP/1.1\r\n";
            $http .= "Host: {$host}\r\n";
            $http .= "Connection: Close\r\n\r\n";
            fwrite($fp,$http);
            fclose($fp);
            return true;
        }
    }
}
