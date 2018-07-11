<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\V1\DeviceModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\V1\UserModel;
use App\Common\MobileMassege;
use Illuminate\Support\Facades\Schema;
use DB;


class UserController extends Controller
{

    /**
     * 用户注册
     * 绑定QQ和绑定微信
     * */
    public function login(Request $request)
    {
        $mobile     = $request->input('mobile');
        $code       = $request->input('code');

        $type       = $request->input('type');

        $nickName   = $request->input('nickName');

        $wxname     = $request->input('wxName');
        $wxhead     = $request->input('wxHead');
        $wxUnionid  = $request->input('wxUnionid');

        $qqOpenid   = $request->input('qqOpenid');
        $qqname     = $request->input('qqName');
        $qqHead     = $request->input('qqHead');

        //1.检查参数
        $params     = [
            'mobile'    => [$mobile,"缺少手机号",4001],
            'code'      => [$code,'缺少验证码',4003]
        ];

        $checkRes   = $this->check_params($params);
        if($checkRes->status == false)
        {
            return apiData()->send($checkRes->code,$checkRes->message);
        }

        //2.检查验证妈
        $mobileMessage  = new MobileMassege();
        $result         = $mobileMessage->check_valid_code($mobile,$code);
        if($result == false)
        {
            return apiData()->send(4005,$mobileMessage->error);
        }

        //3.根据注册类型进行注册

        $nickname   = $nickName ? $nickName : $mobile;

        $userModel  = new UserModel();
        $isRegister = $userModel->check_exists_user_by_mobile($mobile);

        $wxinfo     = [
            'wx_name'   => $wxname,
            'wx_head'   => $wxhead,
            'wx_unionid'=> $wxUnionid
        ];

        $qqinfo     = [

            'qq_openid' => $qqOpenid,
            'qq_name'   => $qqname,
            'qq_head'   => $qqHead
        ];

        if(!$isRegister) //未注册，进行手机号注册 其他也没法绑定
        {
            $otherInfo  = array_merge($wxinfo,$qqinfo);
            $userModel->register($mobile,$nickname,$otherInfo);
            $userInfo   = $userModel->get_user_info_by_mobile($mobile);
            return $this->login_action($userInfo,true);
        }

        $userInfo   = $userModel->get_user_info_by_mobile($mobile);

        if($type == 'wx') //微信登陆，绑定微信信息
        {
            if(empty($wxinfo['wx_unionid']))
            {
                return apiData()->send(5001,'缺少参数');
            }
            $userModel->update_user_info($userInfo['id'],$wxinfo);

        } elseif($type == 'qq') {

            if(empty($wxinfo['qq_openid']))
            {
                return apiData()->send(5002,'缺少参数');
            }
            $userModel->update_user_info($userInfo['id'],$qqinfo);
        }

        $userInfo   = $userModel->get_user_info($userInfo['id']);

        return $this->login_action($userInfo);
    }

    public function login_out(Request $request){

        $userID     = $request->input('userId');
        $tokenInfo  = parse_token($request);

        if($userID == $tokenInfo->userId)
        {
            $userModel  = new UserModel();
            $userModel->update_user_info($userID,['token'=>'']);
            return apiData()->send();

        }else{

            return apiData()->send(3001,'无法退出，您没有权限');
        }
    }

    /**
     * 获得用户信息
     * */
    public function get_user_info(Request $request)
    {
        $userId     = $request->input('userId');

        $userModel  = new UserModel();

        $userInfo   = $userModel->get_user_info($userId);
        $userInfo['isNewUser']  = 0;
        $userInfo['birthday']   = $userInfo['birthday'] == "0000-00-00" ? "" : $userInfo['birthday'];
        $deviceInfo             = $this->get_user_current_device_info($userInfo['deviceSn']);
        return apiData()->set_data('userInfo',$userInfo)->set_data('deviceInfo',$deviceInfo)->send(200,'success');

    }


    private function get_user_current_device_info($deviceSn)
    {
        //如果用户有设备信息
        if($deviceSn)
        {
            $deviceModel    = new DeviceModel();
            $deviceInfo     = $deviceModel->get_device_info_by_sn($deviceSn);

        }else{

            $deviceInfo     = null;
        }

        return $deviceInfo;
    }


    /**
     * 更新用户信息
     * */
    public function update_user_info(Request $request)
    {
        $tokenInfo  = parse_token($request);
        if($tokenInfo == false)
        {
            return apiData()->send(4001,'用户不存在');
        }

        $userId     = $tokenInfo->userId;
        $tempInfo   = $request->all();
        $userInfo   = [];
        $colums     = Schema::getColumnListing('users');

        foreach($tempInfo as $key => $v)
        {
            $key    = tofeng_to_line($key);
            if(!in_array($key,$colums))
            {
                continue;
            }
            $userInfo[$key] = $v;
        }
        $userModel  = new UserModel();
        $userModel->update_user_info($userId,$userInfo);
        $userInfo   = $userModel->get_user_info($userId);
        return apiData()->set_data('userInfo',$userInfo)->send(200,'修改成功');
    }


    /**
     * 手机登录
     * */
    public function mobile_login1(Request $request)
    {

        $mobile = $request->input('mobile');
        $code   = $request->input('code');

        $mobileService  = new MobileMassege();
        $result         = $mobileService->check_valid_code($mobile,$code);

        if($result == false)
        {
            return apiData()->send(4001,$mobileService->error);
        }


        //执行登录操作
        $userModel  = new UserModel();
        $userInfo   = $userModel->get_user_info_by_mobile($mobile);

        if(!$userInfo) //用户不存在
        {
            return $this->register($request);
        }

        return $this->login_action($userInfo);
    }


    /**
     * 登录的实体操作
     * */
    private function login_action($userInfo,$isNewUser=false)
    {
        $userModel  = new UserModel();
        $token      = $userModel->fresh_token($userInfo['id']);
        $userInfo['token'] = $token;
        $userInfo['isNewUser']  = $isNewUser == true ? 1 : 0;
        $userInfo['birthday']   = $userInfo['birthday'] == "0000-00-00" ? "" : $userInfo['birthday'];
        header('Token:'.$token);
        $deviceInfo = $this->get_user_current_device_info($userInfo['deviceSn']);

        return apiData()->set_data('userInfo',$userInfo)->set_data('deviceInfo',$deviceInfo)->send(200,'登录成功');
    }

    /*
     * 微信或QQ登陆的要求
     * 客户端使用第三方获取用户信息后，要求用户绑定手机号，只有在用户手机号验证成功的前提下，服务端才会保存这个用户的信息
     *
     * */
    public function wx_qq_login(Request $request)
    {
        $type       = $request->input('type');
        $wxunionid  = $request->input('wxUnionid');
        $qqopenid   = $request->input('qqOpenid');
        $userModel  = new UserModel();

        if($type == 'wx')
        {
            $userInfo   = $userModel->get_user_info_by_openid($wxunionid,'wx');

        }elseif ($type == 'qq'){

            $userInfo   = $userModel->get_user_info_by_openid($qqopenid,'qq');

        }

        if($userInfo == false) //用户第一次登陆
        {
            return apiData()->send(2001,'改号码尚未绑定手机号');
        }

        return $this->login_action($userInfo['id']);
    }







    /**
     * 用户整体能力
     * */
    public function user_global_ability(Request $request)
    {

        $userId = $request->input('userId');

        $colums         = ["shoot","pass","strength","dribble","defense","run"];


        $userAbility    = DB::table('user_global_ability')->select($colums)->where('user_id',$userId)->first();
        if(!$userAbility)
        {
            $userAbility    = new \stdClass();
            $userAbility->shoot     = 0;
            $userAbility->pass      = 0;
            $userAbility->strength  = 0;
            $userAbility->dribble   = 0;
            $userAbility->defense   = 0;
            $userAbility->run       = 0;
        }

        $map            = create_round_array(2,3);
        return apiData()
            ->set_data('map',$map)
            ->set_data('userAbility',$userAbility)
            ->send(200,'success');
    }


    /**
     * 用户单项数据图
     * */
    public function user_global_ability_maps()
    {
        $maps   = [
            [
                'name'      => "跑动最好情况",
                'y_title'   => "次数",
                'x_title'   => "时间",
                'data'      => create_xy_map()
            ],
            [
                'name'      => "传球最好情况",
                'y_title'   => "次数",
                'x_title'   => "时间",
                'data'      => create_xy_map()
            ]
        ];

        return apiData()->set_data('abilityMaps',$maps)->send(200,'success');
    }


    public function suggestion(Request $request)
    {
        $data   = [
            'user_id'   => $request->input('userId',0),
            'name'      => $request->input('name',''),
            'type'      => $request->input('type',''),
            'mobile'    => $request->input('mobile',''),
            'content'   => emoji_text_encode($request->input('content','')),
            'created_at'=> date_time()
        ];

        if(empty($data['content']))
        {
            return apiData()->send(2001,'反馈意见不能为空');
        }

        DB::table('user_suggestion')->insert($data);

        return apiData()->send();
    }
}


/**
 * 创建水平图谱
 * */
function create_xy_map()
{
    $data   = [];
    for($i=0;$i<10;$i++)
    {
        array_push($data,['x'=>(string)$i,'y'=>rand(0,10)]);
    }
    return $data;
}
