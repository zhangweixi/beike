<?php
namespace App\Common;


class WechatTemplate{

    public $templateId;
    public $url;
    public $openId;

    public $data    = [];

    public $first;

    public $remark;

    public function create(){}


    public function serviceFinishTemplate()
    {
        return new ServiceFinishTemplate();
    }
}



class ServiceFinishTemplate extends WechatTemplate {

    public $orderSn     = "";
    public $deviceSn    = "";
    public $workStyle   = "";
    public $workTime    = "";
    public $workAddress = "";
    public $templateId  = "cEsCFNH5rvJDMEA-v8kMNuvq_nAH_tx2jh8lTHdoPyw";


    public function create()
    {
        $this->data = [
            "first"     => $this->first,
            "remark"    => $this->remark,
            "keyword1"  => $this->orderSn,
            "keyword2"  => $this->deviceSn,
            "keyword3"  => $this->workStyle,
            "keyword4"  => $this->workTime,
            "keyword5"  => $this->workAddress,
        ];
    }
}



