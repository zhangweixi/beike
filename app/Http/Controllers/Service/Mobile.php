<?php
namespace App\Http\Controllers\Service;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Aliyun\Core\Config as AliyunConfig;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;


class Mobile extends Controller{


    function get_code()
    {

        // 阿里云Access Key ID和Access Key Secret 从 https://ak-console.aliyun.com 获取
        $appKey         = config('aliyun.appKey');
        $appSecret      = config('aliyun.appSecret');

        $signName       = config('aliyun.signName');
        $template_code  = 'SMS_134115374';


        //短信中的替换变量json字符串
        $json_string_param = json_encode(['code'=>'112233']);

        //接收短信的手机号码
        $phone = '15000606942';

        // 初始化阿里云config
        AliyunConfig::load();
        DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", "Dysmsapi", "dysmsapi.aliyuncs.com");

        // 初始化用户Profile实例
        $profile    = DefaultProfile::getProfile("cn-hangzhou", $appKey, $appSecret);

        $acsClient  = new DefaultAcsClient($profile);
        $request    = new SendSmsRequest(); // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request->setPhoneNumbers($phone);  // 必填，设置短信接收号码
        $request->setSignName($signName);   // 必填，设置签名名称
        $request->setTemplateCode($template_code); // 必填，设置模板CODE
        empty($json_string_param) ? "" : $request->setTemplateParam($json_string_param);

        $acsResponse    =  $acsClient->getAcsResponse($request); // 发起请求

        // 默认返回stdClass，通过返回值的Code属性来判断发送成功与否
        if($acsResponse && strtolower($acsResponse->Code) == 'ok')
        {
            return 'ok';
        }

        return [$acsResponse];
    }


}