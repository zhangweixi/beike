<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Base\BaseFootballCourtTypeModel;
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

        $name       = $request->input('name');
        $head       = $request->input('headImg');
        $openId     = $request->input('openId');

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

        $wxinfo     = $qqinfo   = [];

        if($type == "wx"){

            $wxinfo     = [
                'wx_name'   => $name,
                'wx_head'   => $head,
                'wx_unionid'=> $openId
            ];

            $nickname       = $name;

        }elseif($type == 'qq'){

            $qqinfo     = [
                'qq_openid' => $openId,
                'qq_name'   => $name,
                'qq_head'   => $head
            ];
            $nickname   = $name;
        }



        if(!$isRegister) //未注册，进行手机号注册 其他也没法绑定
        {
            $otherInfo  = array_merge($wxinfo,$qqinfo);
            $userModel->register($mobile,$nickname,$otherInfo);
            $userInfo   = $userModel->get_user_info_by_mobile($mobile);
            return $this->login_action($userInfo,true);
        }

        $userInfo   = $userModel->get_user_info_by_mobile($mobile);

        if($type == 'wx') {//微信登陆，绑定微信信息

            if(empty($wxinfo['wx_unionid']))
            {
                return apiData()->send(5001,'缺少参数');
            }
            $userModel->update_user_info($userInfo['id'],$wxinfo);

        } elseif($type == 'qq') {

            if(empty($qqinfo['qq_openid'])){

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

        //球队信息
        $userInfo['footballTeamName']   = "";
        if($userInfo['footballTeam'] > 0)
        {
            $teamInfo = DB::table("football_team")->where('team_id',$userInfo['footballTeam'])->first();
            $userInfo['footballTeamName']   = $teamInfo->team_name;
        }

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

        //球队信息
        $userInfo['footballTeamName']   = "";
        if($userInfo['footballTeam'] > 0)
        {
            $teamInfo = DB::table("football_team")->where('team_id',$userInfo['footballTeam'])->first();
            $userInfo['footballTeamName']   = $teamInfo->team_name;
        }
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
            return apiData()->send(200,'请绑定手机号');
        }

        return $this->login_action($userInfo['id']);
    }



    /**
     * 用户整体能力
     * */
    public function user_global_ability(Request $request)
    {

        $userId = $request->input('userId');

        $colums         = [
            "grade_shoot    as shoot",
            "grade_pass     as pass",
            "grade_strength as strength",
            "grade_dribble  as dribble",
            "grade_defense  as defense",
            "grade_run      as run"
        ];


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


        $grades = [
            ["name"=>"射门欲望","max"=>100,"self"=>86],
            ["name"=>"射门力量","max"=>100,"self"=>60],
            ["name"=>"射门时机","max"=>100,"self"=>90],
            ["name"=>"长传速度","max"=>100,"self"=>48],
            ["name"=>"短传速度","max"=>100,"self"=>36],
            ["name"=>"长传数量","max"=>100,"self"=>48],
            ["name"=>"短传数量","max"=>100,"self"=>20],
            ["name"=>"短传数量","max"=>100,"self"=>20],
            ["name"=>"耐力","max"=>100,"self"=>40],
            ["name"=>"触球数量","max"=>100,"self"=>20],
            ["name"=>"高速跑动","max"=>100,"self"=>20],
            ["name"=>"灵活性","max"=>100,"self"=>50],
        ];

        if(false)
        {
            $grades = new \stdClass();
            $grades->shootDesire    = ["self"=>86,"avg"=>100];//射门欲望
            $grades->shootStrength  = ["self"=>60,"avg"=>100];//射门力量
            $grades->shootTimeControl = ["self"=>56,"avg"=>100];//射门时机控制

            //$grades->attack         = ['self'=>42,'avg'=>100];//攻击
            //$grades->control        = ['self'=>65,'avg'=>100];//控球
            //$grades->dribble        = ['self'=>76,'avg'=>100];//盘球
            //$grades->passGround     = ['self'=>50,'avg'=>100];//地面传球
            //$grades->passAir        = ['self'=>73,'avg'=>100];//空中传球

            $grades->passSpeedShort     = ["self"=>48,"avg"=>100]; //短传距离
            $grades->passSpeedLong      = ["self"=>63,"avg"=>100];  //长传距离


            $grades->passNumShort       = ["self"=>47,"avg"=>100];   //短传数量
            $grades->passNumLong        = ["self"=>86,"avg"=>100];    //长传数量


            //$grades->location   = ['self'=>36,'avg'=>100];//定位球
            //$grades->strength   = ['self'=>58,'avg'=>100];//强度
            //$grades->head       = ['self'=>71,'avg'=>100];//头球
            //$grades->defence    = ['self'=>69,'avg'=>100];//防守能力
            //$grades->grab       = ['self'=>73,'avg'=>100];//抢球

            $grades->touchball  = ['self'=>52,'avg'=>100];//触球
            $grades->endurance  = ['self'=>44,'avg'=>100];//耐力
            $grades->speed      = ['self'=>86,'avg'=>100];//速度

            $grades->runDis      = ['self'=>80,'avg'=>100];//冲刺能力
            $grades->flexible   = ['self'=>90,'avg'=>100];//灵活
        }


        return apiData()
            ->set_data('userAbility',$userAbility)
            ->set_data('grades',$grades)
            ->send();
    }


    /**
     * 比赛比较
     * */
    public function user_global_ability_compare(Request $request)
    {
        $userId     = $request->input('userId');
        $friendId   = $request->input('friendUserId');

        $userModel  = new UserModel();

        $friendInfo = $userModel->get_user_info($friendId);

        $userInfo   = new \stdClass();
        $userInfo->id       = $friendInfo['id'];
        $userInfo->headImg  = $friendInfo['headImg'];
        $userInfo->nickName = $friendInfo['nickName'];
        $userInfo->age      = birthday_to_age($friendInfo['birthday']);
        $userInfo->foot     = $friendInfo['foot'];
        $userInfo->role1    = $friendInfo['role1'];
        $userInfo->role2    = $friendInfo['role2'];


        //基本数据
        $myAbility      = $userModel->user_global_ability($userId);
        $friendAbility  = $userModel->user_global_ability($friendId);
        $baseAbility    = ["self"=>$myAbility,"friend"=>$friendAbility];

        //详细数据
        $detailAbility     = [
            ["name"=>"射门","self"=>30,"friend"=>40,"max"=>100],
            ["name"=>"盘球","self"=>60,"friend"=>50,"max"=>100],
            ["name"=>"控球","self"=>20,"friend"=>60,"max"=>100],
            ["name"=>"攻击能力","self"=>50,"friend"=>80,"max"=>100],
        ];

        return apiData()->add('friendInfo',$userInfo)
            ->add('baseAbility',$baseAbility)
            ->add('detailAbility',$detailAbility)
            ->send();
    }

    /**
     * 用户单项数据图
     * */
    public function user_global_ability_maps(Request $request)
    {
        //历史比赛评分情况
        //跑动距离，传球次数，射门次数，触球次数，最大速度

        $userId = $request->input('userId');

        $maps   = [
            [
                'name'      => "综合评分",
                'key'       => 'grade',
                'y_title'   => "分",
                'x_title'   => '时间',
                'data'      => [],
            ],
            [
                'name'      => "射门",
                'key'       => "shoot_num_total",
                'y_title'   => "次",
                'x_title'   => "时间",
                'data'      => []
            ],
            [
                'name'      => "跑动距离",
                'key'       => 'runDis',
                'y_title'   => "KM",
                'x_title'   => "时间",
                'data'      => [],
            ],
            [
                'name'      => "冲刺速度",
                'key'       => 'run_speed_max',
                'y_title'   => "km/h",
                'x_title'   => "时间",
                'data'      => [],
            ],
            [
                'name'      => "触球",
                'key'       => 'touchball_num',
                'y_title'   => "次",
                'x_title'   => "时间",
                'data'      => [],
            ],
            [
                'name'      => "传球次数",
                'key'       => 'passNum',
                'y_title'   => "次",
                'x_title'   => "时间",
                'data'      => [],
            ],

        ];

        $matches = DB::table('match as a')
            ->leftJoin('match_result as b','b.match_id','=','a.match_id')
            ->select("b.grade","b.shoot_num_total","b.pass_s_num","b.pass_l_num","b.touchball_num","b.run_speed_max",'b.run_low_dis','b.run_mid_dis','b.run_high_dis','b.run_static_dis')
            ->where('a.user_id',$userId)
            ->where('b.match_id',">",0)
            ->get();


        foreach ($matches as $key => $match){

            $match->passNum         = $match->pass_s_num + $match->pass_l_num;
            $match->runDis          = ($match->run_low_dis + $match->run_mid_dis + $match->run_high_dis + $match->run_static_dis)/1000;
            $match->run_speed_max   = (int)($match->run_speed_max*60*60/1000);

            foreach($maps as &$map)
            {
                $option     = $map['key'];
                array_push($map['data'],['x'=>(string)($key + 1),'y'=>$match->$option]);
            }
        }

        foreach($maps as &$map)
        {
            unset($map['key']);
        }

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
            ->whereRaw("(NOT FIND_IN_SET($userId,readed_users) OR readed_users is null OR readed_users = '')")
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

        if($page == 1)
        {
            $this->read_all_message("focus",$userId);
        }

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
        $userId = $request->input('userId',0);

        if($userId > 0)
        {
            $userInfo   = UserModel::find($userId);
            $teamId     = $userInfo->football_team;

        }else{

            $teamId     = 0;
        }

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
            $team->isMyTeam = $teamId == $team->team_id ? 1 : 0; //判断是不是当前球队

            array_push($teamUnions[$team->union_id]->teams,$team);
        }

        $unions = [];
        foreach($teamUnions as $union)
        {
            array_push($unions,$union);
        }

        return apiData()->add('unions',$unions)->send();
    }
}