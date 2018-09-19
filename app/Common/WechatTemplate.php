<?php
namespace App\Common;


class  WechatTemplate{

    public $templateId;
    public $url = "";
    public $openId;

    public $data    = [];

    public $first;

    public $remark;

    public function create(){}


    public function serviceFinishTemplate()
    {
        return new ServiceFinishTemplate();
    }


    public function warningTemplate()
    {
        return new WarningTemplate();
    }
}


/**
 * 服务完成通知模板
 * */
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


/**
 * 异常报警模板
 * */
class WarningTemplate extends WechatTemplate{

    public $warnType;
    public $warnTime;
    public $templateId = "yflcUkErnS9i0pq9q4_hh4ifCkTVc1OVK9TsEAB_H_k";


    public function create()
    {
        $this->data = [
            "first"     => $this->first,
            "remark"    => $this->remark,
            "keyword1"  => $this->warnType,
            "keyword2"  => $this->warnTime
        ];
    }

}


