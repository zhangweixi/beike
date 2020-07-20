<?php
namespace App\Http\Controllers\Service;

use App\Models\Base\BaseCountryModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Common\MobileMassege;



class Mobile extends Controller{


    /**
     * 获取验证码
     * */
    public function get_mobile_code(Request $request)
    {

        $mobile     = $request->input('mobile');
        $country    = $request->input('country','086');
        $checkRes   = $this->check_params(['mobile'=>[$mobile,'缺少手机号']]);
        if($checkRes->status == false)
        {
            return apiData()->send(3001,$checkRes->message);
        }

        $mobileMessage  = new MobileMassege($country.$mobile);
        $result         = $mobileMessage->send_valid_code();
        if($result == false)
        {
            return apiData()->send(3002,$mobileMessage->error);
        }else{

            return apiData()->send(200,'ok');
        }
    }


    /**
     * 检查验证码
     * */
    public function check_mobile_code(Request $request)
    {

        $mobile     = $request->input('mobile');
        $code       = $request->input('code');

        $data       = [
            'mobile'=>[$mobile,"手机号错误"],
            'code'  =>[$code,'缺少验证码']
        ];

        $checkRes   = $this->check_params($data);
        if($checkRes->status == false)
        {
            return apiData()->send(3004,$checkRes->message);
        }

        $mobileMessage  = new MobileMassege();
        $codeCheck      = $mobileMessage->check_valid_code($mobile,$code);
        if($codeCheck == false)
        {
            return apiData()->send(3004,$mobileMessage->error);

        }else{

            return apiData()->send(200,"验证通过");
        }
    }

    /**
     * 发送短信接口回调
     * */
    public function mobile_callback(Request $request)
    {
        $info   = $request->all();
        $info   = $info[0];
        $msgId  = $info['biz_id'];
        $info   = \GuzzleHttp\json_encode($info);
        $messageModel   = new MobileMassege();
        $messageModel->recored_send_status($msgId,$info);
        return response()->json(['code'=>0,'msg'=>"成功"]);
    }

    public function get_countries(Request $request) {
        $countries = BaseCountryModel::select('country_china_name','country_num_code')->where('is_show',1)->orderBy('sort')->get();
        return apiData()->add('data', $countries)->send_old();
    }
}