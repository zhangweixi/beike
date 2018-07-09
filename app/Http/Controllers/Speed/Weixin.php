<?php
namespace  App\Http\Controllers\Speed;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use EasyWeChat;
use \EasyWeChat\Factory;
use DB;

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



    }

    public function get_department_list()
    {
        $info  = $this->wx->department->list();

        return $info;
    }

    public function login(Request $request)
    {

        $url    = $request->input('url');
        mylogger('登录');
        if(empty($url))
        {
            exit('缺少URL');
        }
        $url    = urldecode($url);

        $weixinInfo = $this->get_wx_info($request);
        $userId     = $weixinInfo->user_sn;
        mylogger('用户ID'.$userId);

        if(preg_match('/\?/',$url))
        {
            $url = str_replace("?","?userSn=".$userId."&",$url);
        }elseif(preg_match('/#/',$url)){

            $url  = str_replace("#","?userSn=".$userId."#",$url);

        }else{
            $url = $url."?userSn=".$userId;
        }
        mylogger('url'.$url);
        header('Location:'.$url);

    }

    public function clean(Request $request)
    {
        $info = $request->session()->all();
        $request->session()->flush();

        $request->session()->forget('wechat_user');
        $request->session()->save();
        dd($info);
    }

    public function get_wx_info(Request $request){

        $userInfo   = $request->session()->get('wechat_user');
        $code       = $request->input('code');

        if($userInfo)
        {
            $userInfo = DB::table('user')->where('user_sn',$userInfo->user_sn)->first();
            if($userInfo)
            {
                return $userInfo;

            }else{

                $userInfo = null;

            }
        }

        if(empty($userInfo) && empty($code))
        {
            $targetUrl = url($request->getRequestUri());

            $directUrl = url('/speed/weixin/get_wx_info?targetUrl='.urlencode($targetUrl));
            $directUrl = urlencode($directUrl);

            /*return $this->wx->oauth->scopes(['snsapi_userinfo'])
                ->setRedirectUrl($directUrl)
                ->setRequest($request)
                ->redirect()->send();*/

            mylogger('请求URL'.$directUrl);
            $config = config('wechat.work.default');
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$config['corp_id']."&redirect_uri=".$directUrl."&response_type=code&scope=snsapi_userinfo&agentid=".$config['agent_id']."&state=STATE#wechat_redirect";


            //        https://open.weixin.qq.com/connect/oauth2/authorize?appid=CORPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&agentid=AGENTID&state=STATE#wechat_redirect
            header("Location:".$url);
            exit;
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

            //从数据库获取用户信息
            $userInfo = DB::table('user')->where('user_sn',$info->UserId)->first();

            if($userInfo)
            {
                $request->session()->put('wechat_user',$userInfo);
                $request->session()->save();
                return $userInfo;
            }

            $userId     =  $info->UserId;
            $userInfo   = $this->getwxinfobyuserid($userId);


            if($userInfo)
            {

                //保存在数据库
                $data = [
                    'user_sn'   => $userInfo['userid'],
                    'nick_name'  => $userInfo['name'],
                    'real_name' => $userInfo['name'],
                    'head'      => $userInfo['avatar'],
                    'mobile'    => $userInfo['mobile'],
                    'created_at'=> date_time(),
                    'updated_at'=> date_time()
                ];


                DB::table('user')->insert($data);
                $userInfo = DB::table('user')->where('user_sn',$userInfo['userid'])->first();
                $request->session()->put('wechat_user',$userInfo);
                $request->session()->save();
                $targetUrl = urldecode($request->input('targetUrl'));

                mylogger('完成后:'.$targetUrl);
                //print_r($userInfo);
                header('Location:'.$targetUrl);
                exit();
            }
        }
        return false;
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
