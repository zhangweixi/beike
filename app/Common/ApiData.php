<?php
namespace App\Common;

class ApiData{
    public $code    = "200";
    public $data    = [];
    private $outData= [];
    public $message = "";

    public function set_data($key,$v){
        $this->data[$key] = $v;
        return $this;
    }

    public function send($code = 0 , $msg = ''){
        $code   = $code == 0 ? $this->code : $code;
        $message= $msg  == ""? $this->message : $msg;
        $data = [
                "code"      => $code,
                "message"   => $message,
                "data"      => $this->data
        ];

        if(count($this->outData) > 0){
            foreach($this->outData as $k=> $v){
                $data[$k] = $v;
            }
        }
        return response()->json($data);
    }



    public function set_out_data($k,$v){
        $this->outData[$k] = $v;
        return $this;
    }

    public function send_old($code =0,$msg = ""){
        $code   = $code == 0 ? $this->code : $code;
        $message= $msg  == ""? $this->message : $msg;
        $data = [
            "code"      => $code,
            "message"   => $message,
        ];
        foreach($this->data as $k => $v){
            $data[$k] = $v;
        }
        return response()->json($data);
    }

    /**
     * 操作结果提示
     * */
    public function notice($msg)
    {
        return view('admin_system/public/error',['msg'=>$msg]);
    }
}