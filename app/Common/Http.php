<?php
namespace App\Common;
use Illuminate\Validation\Validator;
use PhpParser\Node\Expr\Cast\Object_;

/**
 * 执行curl
 */
class Http{

    public $type = "post";
    public $data = [];
    public $post = false;
    private $ch;
    private $_url = "";
    private $_host = "";

    public function __construct(){
        $this->ch = curl_init();
    }

    /**
     * @param data array 要传递的参数
     * @return self
     * */
    public function set_data($data){
        $this->data = $data;
        return $this;
    }

    /**
     * 设置方法
     * @param $method strng 方法
     * @return self
     * */
    public function method($method){
        $method = strtolower($method);
        if($method == 'post')
        {
            $this->post = true;

        }elseif($method == 'get'){

            $this->post = false;
        }
        return $this;
    }

    /**
     *
     * @param  $header array 请求头
     * @return self
     * */
    public function set_header(array $header)
    {
        foreach($header as $key => $v){
            $he = $key.":".$v;
            curl_setopt($this->ch,CURLOPT_HTTPHEADER,$he);
        }
        return $this;
    }

    /*
     * 设置请求的url
     * @param $url string 请求的url
     * @return Object
     * */
    public function url($url){
        $this->_url = $url;
        return $this;
    }

    /**
     * 设置域名
     * @param $host string 域名
     * @return Object
     * */
    public function host($host)
    {
        $this->_host = $host;
        return $this;
    }

    public function send(){

        curl_setopt($this->ch, CURLOPT_URL, $this->_url);
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

    public function sock(){

        $fp = fsockopen($this->_host, 80, $errno, $errstr, 30);
        if (!$fp)
        {
            return false;

        } else {

            stream_set_blocking($fp,0);
            $http = "GET {$this->_url} HTTP/1.1\r\n";
            $http .= "Host: {$this->_host}\r\n";
            $http .= "Connection: Close\r\n\r\n";
            fwrite($fp,$http);
            fclose($fp);
            return true;
        }
    }

}
