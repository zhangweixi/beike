<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\V1\DeviceModel;
use App\Models\V1\FriendModel;
use App\Models\V1\MessageModel;
use App\Models\V1\ShequMatchModel;
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

        $userId     = $request->input('userId');
        $tempInfo   = $request->all();
        $userInfo   = [];
        $colums     = Schema::getColumnListing('users');

        foreach($tempInfo as $key => $v)
        {
            $key    = tofeng_to_line($key);
            if(!in_array($key,$colums) || $v == null)
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

        $grades = new \stdClass();
        $grades->attack = ['self'=>50,'avg'=>100];//攻击
        $grades->control = ['self'=>80,'avg'=>100];//控球
        $grades->dribble = ['self'=>30,'avg'=>100];//盘球
        $grades->passGround = ['self'=>30,'avg'=>100];//地面传球
        $grades->passAir = ['self'=>60,'avg'=>100];//空中传球
        $grades->shoot   = ['self'=>10,'avg'=>100];//射门
        $grades->location = ['self'=>30,'avg'=>100];//定位球
        $grades->strength = ['self'=>10,'avg'=>100];//强度
        $grades->head = ['self'=>30,'avg'=>100];//头球
        $grades->defence = ['self'=>60,'avg'=>100];//防守能力
        $grades->grab = ['self'=>30,'avg'=>100];//抢球

        $map            = create_round_array(2,3);
        return apiData()
            ->set_data('map',$map)
            ->set_data('userAbility',$userAbility)
            ->set_data('grades',$grades)
            ->send();
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


    /**
     * 用户反馈
     * */
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


    /**
     * 消息分类
     * */
    public function message_type(Request $request)
    {
        $userId     = $request->input('userId');
        $systemNum  = MessageModel::count_unread_msg($userId,'system');
        $inviteNum  = MessageModel::count_unread_msg($userId,'invite');
        $focusNum   = MessageModel::count_unread_msg($userId,'focus');


        $msgTypes   = [
            [
                "msgType"   => 'focus',
                "typeTitle" => "关注信息",
                "icon"      => url('beike/images/icon/msg-focus.png'),
                'msgNum'    => $focusNum,
                'newMsg'    => "一只小小龟请求关注你"
            ],
            [
                "msgType"   => 'invite',
                "typeTitle" => "邀请信息",
                "icon"      => url('beike/images/icon/msg-invite.png'),
                'msgNum'    => $inviteNum,
                'newMsg'    => "11-09  14:00-16:00  虹口足球场"
            ],
            [
                "msgType"   => 'system',
                "typeTitle" => "系统信息",
                "icon"      => url('beike/images/icon/msg-system.png'),
                'msgNum'    => $systemNum,
                'newMsg'    => "一只小小龟请求关注你"
            ]
        ];

        return apiData()->add('msgType',$msgTypes)->send();
    }


    /**
     * 阅读所有消息
     * @param $msgType string 消息类型
     * @param $userId integer 用户ID
     * */
    public function read_all_message($msgType,$userId)
    {
        $messages = DB::table('user_message')->where('type',$msgType)
            ->where(function($db)use($userId) {

                $db->where('user_id',0)->orWhere('user_id',$userId);
            })
            ->whereRaw("NOT FIND_IN_SET($userId,readed_users)")
            ->select('msg_id','readed_users')
            ->get();

        foreach ($messages as $msg)
        {
            $users  = $msg->readed_users ? $msg->readed_users.",".$userId : $userId;
            DB::table('user_message')->where('msg_id',$msg->msg_id)->update(['readed_users'=>$users]);
        }
    }

    /**
     * 系统信息列表
     * */
    public function system_message(Request $request)
    {

        $userId = $request->input('userId');
        $page   = $request->input('page',1);
        $page == 1? $this->read_all_message("system",$userId) : '';


        $isRead = DB::raw("IF(FIND_IN_SET('{$userId}',readed_users) > 0,1,0) AS is_readed");
        $message = DB::table('user_message')
            ->where('user_id',$userId)
            ->where('type','system')
            ->orWhere('user_id',0)
            ->select('msg_id','title','content','content_id','type','thumb_img','created_at',$isRead)
            ->paginate(20);

        foreach($message as $msg)
        {
            $timeInfo           = explode(' ',$msg->created_at);
            $msg->thumb_img     = url($msg->thumb_img);
            $msg->created_at    = str_replace('-','.',$timeInfo[0])." ".str_replace('-',":",substr($timeInfo[1],0,5));
        }
        return apiData()->add('message',$message)->send();
    }




    /**
     * 关注信息
     * */
    public function focus_message(Request $request)
    {
        $userId     = $request->input('userId');
        $page       = $request->input('page',1);
        $page == 1? $this->read_all_message("focus",$userId) : '';

        $msgList    = FriendModel::apply_list($userId);

        return apiData()->add('messages',$msgList)->send();
    }


    /**
     * 比赛邀请信息
     * */
    public function invite_message(Request $request)
    {
        $userId             = $request->input('userId');
        $page               = $request->input('page',1);
        $page == 1? $this->read_all_message("invite",$userId) : '';
        $shequMatchModel    = new ShequMatchModel();
        $invites            = $shequMatchModel->get_match_invite($userId);

        return apiData()->add('messages',$invites)->send();
    }



    /**
     * 阅读消息
     * */
    public function read_message(Request $request)
    {
        $userId = $request->input('userId');
        $msgId  = $request->input('msgId');

        $msgModel   = new MessageModel();
        $msgModel->read_message($msgId,$userId);

        return apiData()->send();
    }

    /**
     * 未读消息数量
     * */
    public function unread_message_num(Request $request)
    {
        $userId     = $request->input('userId');
        $unreadNum  = MessageModel::count_unread_msg($userId);

        return apiData()->add('unreadNum',$unreadNum)->send();
    }


    /**
     * 球队
     * */
    public function ball_teamp(Request $request)
    {
        $unions = DB::table('football_union')->select('union_id','union_name')->where('is_show',1)->orderBy('sort')->get();

        $teamUnions = [];
        foreach($unions as $union)
        {
            $union->teams                   = [];
            $teamUnions[$union->union_id]   = $union;
        }

        $teams = DB::table('football_team')->select('team_id','union_id','team_name','logo')->get();

        foreach($teams as $team)
        {
            $team->logo     = url($team->logo);
            array_push($teamUnions[$team->union_id]->teams,$team);
        }

        $unions = [];
        foreach($teamUnions as $union)
        {
            array_push($unions,$union);
        }

        return $unions;
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
