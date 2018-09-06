<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/4
 * Time: 11:48
 */

namespace App\Http\Controllers\Service;
use App\Http\Controllers\Controller;
use Dingo\Api\Http\Request;
use Overtrue\LaravelWeChat\Facade;


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
    public function template_message()
    {
        $this->wechat->template_message->send([
                'touser' => 'user-openid',
                'template_id' => 'template-id',
                'url' => 'https://easywechat.org',
                'data' => [
                    'key1' => 'VALUE',
                    'key2' => 'VALUE2'
            ],
        ]);
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
}