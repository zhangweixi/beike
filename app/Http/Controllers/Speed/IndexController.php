<?php
namespace  App\Http\Controllers\Speed;
use App\Http\Controllers\Controller;
use Dingo\Api\Http\Request;
use EasyWeChat;
use \EasyWeChat\Factory;


/**
 * @property \EasyWeChat\Factory $wx
 *
 * */

class IndexController extends Controller{

    private $wx = "";

    public function __construct()
    {
        $this->wx = EasyWeChat::work();


        //Factory::work()->oauth->setRedirectUrl()->redirect();
    }


    public function index()
    {

        $info  = $this->wx->department->list();

        return apiData()->set_data('list',$info)->send();

    }




    public function user(Request $request)
    {

        $userInfo = $this->get_wx_info($request);

        return [$userInfo];

    }


    public function get_wx_info(Request $request){

        $userInfo   = $request->session('wechat_user');
        $code       = $request->input('code');

        if($userInfo)
        {

            return $userInfo;
        }


        if(empty($userInfo) && empty($code))
        {
            $targetUrl = url($request->getRequestUri());

            $directUrl = url('/weixin/get_wx_info?getUrl='.urlencode($targetUrl));


            return $this->wx->oauth->scopes(['snsapi_userinfo'])
                ->setRedirectUrl($directUrl)
                ->setRequest($request)
                ->redirect();
        }


        if(empty($userInfo) && $code)
        {
            $userInfo = $this->wx->oauth->setRequest($request)->user();


            if($userInfo)
            {
                $userInfo = $userInfo->toArray();

                $request->session()->put('wechat_user',$userInfo);
                $request->session()->save();
                $targetUrl = urldecode($request->input('targetUrl'));
                header('Location:'.$targetUrl);
                exit;
            }
        }
        exit('错误');
    }


}
