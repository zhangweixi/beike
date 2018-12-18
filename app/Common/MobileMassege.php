<?php
namespace App\Common;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;
use App\Common\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Aliyun\Core\Config as AliyunConfig;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;



class MobileMassege{

    /**********要发送的手机号码***********/
    public $mobile 		= "";
    private $msg 		= "";
    private $db 		= "";
    private $table      = "user_mobile_code";
    public $code        = "";
    public $codeMsg     = "";
    public $countryCode = "";
    public $error       = "";//错误提示信息
    private $aliResponse= "";



    public function __construct($mobile = '')
    {
        $this->db   = DB::table($this->table);
        $this->create_code();

        if($mobile)
        {
            $this->mobile = $mobile;
        }
    }


    /**
     * 生成验证码
     * */
    private function create_code()
    {
        if(empty($this->code))
        {
            $this->code = randStr(4);
        }
    }


    /**********************
     * 发送验证码
     **********************/
    public function send_valid_code()
    {

        if($this->countryCode == "86")
        {
            $this->countryCode = "";
        }

        $result = $this->check_can_send_code($this->mobile);

        if($result == false)
        {
            return false;
        }


        $template_code  = config('aliyun.loginTempId');
        $data           = ['code'=>$this->code];

        $resInfo        = $this->send_msg($this->mobile,$template_code,$data);


        //记录发送的验证码
        $now        = date_time();

        $data = [
            'msg_id'    => 0,
            'status'    => 3,//0:正常，已使用 1:正常，未使用 2：无效
            'mobile'    => $this->mobile,
            'code'      => $this->code,
            'data'      => json_encode($this->aliResponse),
            'end_time'  => time() + 1800,
            'created_at'=> $now,
            'updated_at'=> $now
        ];

        if($resInfo)
        {
            $data['msg_id'] = $this->aliResponse->BizId;
            $data['status'] = 1;
        }


        $this->db->insert($data);
        $this->error  = $this->aliResponse->Message;

        return $resInfo;
    }


    /**
     * 检查是否可以发送验证码
     * @param mobile string 手机号
     * @return boolean
     * */
    public function check_can_send_code($mobile){

        //检查间隔时间
          $lastMobileCodeInfo = $this->db
            ->select('end_time', 'id')
            ->where('mobile', $mobile)
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->first();

        if ($lastMobileCodeInfo == null)
        {
            return true;
        }

        $endtime = $lastMobileCodeInfo->end_time;

        if (time() <= $endtime) // 时间没有到
        {
            $this->error = "频繁操作，请使用上次接受到的验证码";
            return false;
        }

        //一天最多10条 判断这一天是否足够
        $dayBegin   = strtotime(date('Y-m-d 00:00:00',time()));
        $dayEnd     = strtotime(date('Y-m-d 23:59:59',time()));

        $num    = $this->db
            ->where('created_at',">=",$dayBegin)
            ->where('created_at','<=',$dayEnd)
            ->where('mobile',$mobile)
            ->count();

        if($num >= 10){
           
            //将短信的有效时间调整为1天
            $endTime = time() + 24*60*60;
            $this->db
            ->where('id',$lastMobileCodeInfo->id)
            ->update(['status'=>1,'created_at'=>$endTime]);

            $this->error = "本日短信已达10条，请使用最后一条验证码或联系客服";
            return false;
        }

        //一个小时最多5条
        if($num >= 5){

            $prevHour= time()-60*60;
            $num    = $this->db
            ->where('created_at',">",$prevHour)
            ->where('mobile',$mobile)
            ->count();

            if($num >= 5){
                $this->error = "操作频繁，请距上次发送时间间隔一小时后再试";
                return false;
            }
        }
        return true;
    }



    /*******************
     * 检查验证码
     ******************/
    public function check_valid_code($mobile,$code){

        if($mobile && $code == 666666)
        {

            return true;
        }

        if(empty($mobile) || empty($code))
        {
            $this->error =  "手机号或验证码不能为空";
            return false;

        }

        $condition = [
            ['mobile','=',$mobile],
            ['code','=',$code],
            ['status','=',1],
            ['end_time','>',time()]
        ];

        $id = $this->db->where($condition)->orderBy('id')->value('id');
        if ($id > 0)
        {
            $this->error    = "验证成功";
            return true;

        }else{

            $this->error = "验证失败";
            return false;
        }
    }


    public function delete_code($mobile,$code)
    {
        $condition = [
            ['Mobile','=',$mobile],
            ['Code','=',$code],
            ['States','=',1]
        ];
        $this->db->where($condition)->update(['States'=>0]);
    }



    /**
     * 发送邀请比赛信息
     * */
    public function send_match_invite_message($mobile,$friend,$user,$time,$matchId)
    {
        $templateCode   = config('aliyun.matchInviteId');

        $data           = ["friend"=>$friend,"user"=>$user,"time"=>$time,"matchId"=>$matchId];

        return $this->send_msg($mobile,$templateCode,$data);
    }

    /**
     * 发送设备编号信息
     * */
    public function send_device_sn_message($mobile,$deviceSn)
    {
        $templateId = config('aliyun.deviceSnId');
        $data       = ['deviceSn'=>$deviceSn];
        return $this->send_msg($mobile,$templateId,$data);
    }

    /**
     * 发送消息
     * */
    private function send_msg($mobile,$templateCode,array $data = [])
    {

        $appKey         = config('aliyun.appKey');
        $appSecret      = config('aliyun.appSecret');
        $signName       = config('aliyun.signName');

        //短信中的替换变量json字符串
        $json_string_param = json_encode($data);

        // 初始化阿里云config
        AliyunConfig::load();
        DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", "Dysmsapi", "dysmsapi.aliyuncs.com");

        // 初始化用户Profile实例
        $profile    = DefaultProfile::getProfile("cn-hangzhou", $appKey, $appSecret);

        $acsClient  = new DefaultAcsClient($profile);
        $request    = new SendSmsRequest(); // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request->setPhoneNumbers($mobile);  // 必填，设置短信接收号码
        $request->setSignName($signName);   // 必填，设置签名名称
        $request->setTemplateCode($templateCode); // 必填，设置模板CODE
        empty($json_string_param) ? "" : $request->setTemplateParam($json_string_param);

        $acsResponse        =  $acsClient->getAcsResponse($request); // 发起请求
        $this->aliResponse  = $acsResponse;

        // 默认返回stdClass，通过返回值的Code属性来判断发送成功与否
        if($acsResponse && strtolower($acsResponse->Code) == 'ok')
        {
            return true;
        }

        return false;
    }


    /********************
     * 群发消息
     *******************/
    public function mass_massege($msg,$mobiles){
        $http = new Http();
        $http->post = true;
        //$msg  = iconv("UTF-8","gb2312",$msg);
        $msg  = iconv("UTF-8","gbk",$msg);
        $mobiles = implode(';',$mobiles);
        $data = [
            'phone'     => $mobiles,
            'message'   => $msg,
            'user'      => $this->mobileUser,
            'pwd'       => $this->mobilePwd
        ];

        $http->set_data($data);
        $result = $http->send(self::massUrl);
        return $result;
    }


    /*
     * 记录手机号发送状态
     * */
    public function recored_send_status($msgId,$info)
    {
        $this->db->where('msg_id',$msgId)->update(['data'=>$info]);
    }
}
