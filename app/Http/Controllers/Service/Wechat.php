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
        if(config('app.wechatenv') == false)
        {
            return false;
        }
        $template->create();
        $data = [
            'touser'        => $template->openId,
            'template_id'   => $template->templateId,
            'url'           => $template->url,
            'data'          => $template->data,
        ];
        $this->wechat->template_message->send($data);
    }



    /*
     * 登录
     * */
    public function login(Request $request)
    {
        $redirectUrl    = url('/api/wechat/login_callback');

        $response       = $this->wechat->oauth->setRedirectUrl($redirectUrl)->scopes(["snsapi_userinfo"])->setRequest($request)->redirect();

        return $response;
    }

    /**
     * 登录回调
     * */
    public function login_callback(Request $request)
    {
        $user = $this->wechat->oauth->setRequest($request)->user();

        dd($user);
    }



    public function test()
    {
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

        $this->template_message($serviceTemplate);
    }
}