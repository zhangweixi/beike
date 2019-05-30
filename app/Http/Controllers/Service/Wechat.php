<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/4
 * Time: 11:48
 */

namespace App\Http\Controllers\Service;
use App\Common\WechatTemplate;
use App\Http\Controllers\Controller;
use Dingo\Api\Http\Request;




class Wechat extends Controller
{
    /**
     * @var \EasyWeChat\OfficialAccount\Application
     */
    public $wechat ;
    public $templateData;

    public function __construct()
    {

        $this->wechat = app('wechat.official_account');
    }


    /**
     * 接受微信通知
     * */
    public function serve()
    {
        $this->wechat->server->push(function($message)
        {
            return "欢迎关注 澜启科技！";
        });

        return $this->wechat->server->serve();
    }


    /*
     * 发送模板消息
     * */
    public function template_message(\App\Common\WechatTemplate $template)
    {
        $template->create();
        $data = [
            'touser'        => $template->openId,
            'template_id'   => $template->templateId,
            'url'           => $template->url,
            'data'          => $template->data,
        ];
        $this->templateData = $data;

        return $this;
    }

    /**
     * 发送消息
     * */
    public function send()
    {
        if(config('app.wechatenv') == false)
        {
            return false;
        }

        $this->wechat->template_message->send($this->templateData);
    }

    /*
     * 登录
     * */
    public function login(\Illuminate\Http\Request $request)
    {
        $redirectUrl    = url('/service/wechat/login_callback');
        $url            = $request->input('url',null);

        if($url != null)
        {
            $request->session()->put("appDirectUrl",$url);
        }

        $response       = $this->wechat->oauth->setRedirectUrl($redirectUrl)->scopes(["snsapi_userinfo"])->setRequest($request)->redirect();

        return $response;
    }

    /**
     * 登录回调
     * */
    public function login_callback(\Illuminate\Http\Request $request)
    {

        $user = $this->wechat->oauth->setRequest($request)->user(); //获取微信用户信息

        $request->session()->put("wechatUser",$user);               //缓存微信用户信息

        return $this->back_user_path();
    }


    /**
     * 返回用户的路径
     * */
    private function back_user_path()
    {
        $url    = session()->get('appDirectUrl',null);

        if($url != null){

            $url    = urldecode($url);

            return response()->redirectTo($url);

        }else{

            dd(self::wechat_info());

        }
    }

    public static function wechat_info()
    {
        $wechatInfo     = session()->get('wechatUser',null);

        if($wechatInfo == null)
        {
            return null;
        }

        return $wechatInfo->original;
    }



    /**
     * 获取用户信息
     * */
    public function get_wechat_info()
    {
        $wechatInfo     = self::wechat_info();

        if($wechatInfo == null){

            return apiData()->send(2001,'没有微信信息');
        }

        return apiData()->add('wechat',$wechatInfo)->send();

    }


    public function test()
    {
        $template = (new WechatTemplate())->warningTemplate();

        $template->first = "数据异常";
        $template->remark="请尽快处理";
        $template->warnType= "左右脚数据不一致";
        $template->warnTime= date_time();
        $template->openId = "o1zLM0daxBjdzyYwFxQ9YxPs7O6Q";
        $this->template_message($template)->send();

        return 'ok';

        $serviceTemplate    = (new WechatTemplate())->serviceFinishTemplate();
        $serviceTemplate->first  = "比赛通知";
        $serviceTemplate->remark = "比赛结束";
        $serviceTemplate->openId = "o1zLM0daxBjdzyYwFxQ9YxPs7O6Q";
        $serviceTemplate->orderSn = "123";
        $serviceTemplate->deviceSn = "设备编号";
        $serviceTemplate->workAddress = "工作地址";
        $serviceTemplate->workStyle = "工作模式";
        $serviceTemplate->workTime = "工作时间";
        $serviceTemplate->url = "http://www.baidu.com";

        $this->template_message($serviceTemplate)->send();
    }

    /**
     * 向管理员报警
     * @param $msg string 提示信息
     * */
    public static function warning_to_admin($msg){

        $template = (new WechatTemplate())->warningTemplate();

        $template->first    = "系统警告";
        $template->remark   =  $msg;
        $template->warnType = "无";
        $template->warnTime = date_time();
        $template->openId   = config('app.adminOpenId');
        $wechat             = new Wechat();
        $wechat->template_message($template)->send();
    }
}