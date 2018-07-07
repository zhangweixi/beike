<?php
namespace  App\Http\Controllers\Speed;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use EasyWeChat;
use \EasyWeChat\Factory;


/**
 * @property \EasyWeChat\Factory $wx
 *
 * */

class Weixin extends Controller{

    private $wx = "";
    private $token = "";


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

        return apiData()->set_date('userinfo',$userInfo)->send();
    }



    public function get_wx_info(Request $request){

        $userInfo   = $request->session()->get('wechat_user');

        $code       = $request->input('code');


        if($userInfo)
        {
            return $userInfo;
        }

        if(empty($userInfo) && empty($code))
        {
            $targetUrl = url($request->getRequestUri());

            $directUrl = url('/speed/weixin/get_wx_info?getUrl='.urlencode($targetUrl));


            return $this->wx->oauth->scopes(['snsapi_userinfo'])
                ->setRedirectUrl($directUrl)
                ->setRequest($request)
                ->redirect()->send();


            //$config = config('wechat.work.default');
            //$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$config['corp_id']."&redirect_uri=".$directUrl."&response_type=code&scope=snsapi_userinfo&agentid=".$config['agent_id']."&state=STATE#wechat_redirect";

            //        https://open.weixin.qq.com/connect/oauth2/authorize?appid=CORPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&agentid=AGENTID&state=STATE#wechat_redirect
            //header("Location:".$url);
            //exit;
        }




        if(empty($userInfo) && $code)
        {
            //$userInfo = $this->wx->oauth->setRequest($request)->user();

            //获取微信基本信息
            $token = $this->get_token();
            $url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$token}&code={$code}";
            $info = file_get_contents($url);
            $info = json_decode($info);


            if(!isset($info->UserId))
            {
                $url = url('www/speed/html/focus-weipingtai.html');
                header('Location:'.$url);
                exit;
            }


            $userId     =  $info->UserId;
            $userInfo   = $this->getwxinfobyuserid($userId);


            if($userInfo)
            {
                //$userInfo = $userInfo->toArray();
                $request->session()->put('wechat_user',$userInfo);
                $request->session()->save();
                $targetUrl = urldecode($request->input('targetUrl'));


                //print_r($userInfo);
                header('Location:'.$targetUrl);
                exit();
            }
        }
        return false;
    }


    public function clean(Request $request){

        $request->session()->flush();
    }


    public function getwxinfobyuserid($userId)
    {

        $token = $this->get_token();

        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token={$token}&userid={$userId}";

        $info = file_get_contents($url);

        mylogger('获取信息结果');
        mylogger($info);
        $info = json_decode($info,true);

        return $info;

    }


    public function get_token(){

        // 获取 access token 实例
        if(empty($this->token))
        {
            $accessToken = $this->wx->access_token;
            $token = $accessToken->getToken(); // token 数组  token['access_token'] 字符串
            $token = $token['access_token'];
            $this->token = $token;
        }
        return $this->token;
    }
}
