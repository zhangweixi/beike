<?php

namespace App\Http\Controllers\Api\V2;

use App\Common\MobileMassege;
use App\Models\Base\BaseStarModel;
use App\Models\Base\BaseStarTypeModel;
use App\Models\V1\UserModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\UserController as V1Controller;

class UserController extends V1Controller
{


    const LOGIN_TYPE_MOBILE = "mobile";
    const LOGIN_TYPE_PASSWORD ="password";
    const LOGIN_TYPE_WX = 'wx';
    const LOGIN_TYPE_IOS = 'ios';

    /**
     * 用户注册
     * 绑定QQ和绑定微信
     * */
    public function login(Request $request)
    {
        $country    = $request->input('country','86');
        $mobile     = delete_str($request->input('mobile',""));
        $code       = delete_str($request->input('code',""));
        $password   = delete_str($request->input('password',''));
        $type       = $request->input("type",self::LOGIN_TYPE_MOBILE);  //password mobile wx apple
        $nickName   = trim($request->input("nickName",''));
        $name       = trim($request->input('name',''));
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
        $result         = $mobileMessage->check_valid_code($country.$mobile,$code);
        if($result == false)
        {
            return apiData()->send(4005,$mobileMessage->error);
        }


        //3.根据注册类型进行注册
        $userModel  = new UserModel();
        $userInfo = $userModel->get_user_info_by_mobile($mobile);
        $nickname   = $nickName ?: $mobile;
        $userNewInfo   = [
            'head_img'      => $head,
            'name'          => $name,
            'nickname'      => $nickName,
        ];

        if($type == self::LOGIN_TYPE_IOS)    $userNewInfo['apple_id']   = $openId;
        if($type == self::LOGIN_TYPE_WX)     $userNewInfo['wx_union_id'] = $openId;
        if($password) $userNewInfo['password'] = self::password($password); //如果携带了密码，那么就是修改密码
        $userNewInfo = array_filter($userNewInfo);

        /*
         * 这里有两种情况
         * 1.微信绑定
         * 2.ios绑定
         * 3.第一次手机注册
         * */
        if(!$userInfo) {

            $userModel->register($mobile,$nickname,$userNewInfo);
            $userInfo   = $userModel->get_user_info_by_mobile($mobile);
            return $this->login_action($userInfo,true);

        } else {
            if(count($userNewInfo) > 0) {
                $userModel->update_user_info($userInfo['id'],$userNewInfo);
            }
            $userInfo   = $userModel->get_user_info($userInfo['id']);
            return $this->login_action($userInfo);
        }
    }

    /**
     * 比赛比较
     * */
    public function user_global_ability_compare(Request $request)
    {
        $userId     = $request->input('userId');
        $friendId   = $request->input('friendUserId');
        $type       = $request->input('type','friend'); //friend star
        $userModel  = new UserModel();

        if($type === 'friend') {
            $friendInfo = $userModel->get_user_info($friendId);
            $userInfo   = new \stdClass();
            $userInfo->id       = $friendInfo['id'];
            $userInfo->headImg  = $friendInfo['headImg'];
            $userInfo->nickName = $friendInfo['nickName'];
            $userInfo->age      = birthday_to_age($friendInfo['birthday']);
            $userInfo->foot     = $friendInfo['foot'];
            $userInfo->role1    = $friendInfo['role1'];
            $userInfo->team     = '自由球队';
            $friendAbility  = $userModel->user_global_ability($friendId);
            $shootDesire    = self::def_grade($friendAbility,"grade_shoot_desire",30);
            $shootStrength  = self::def_grade($friendAbility,"grade_shoot_strength",30);
            $shootChance    = self::def_grade($friendAbility,"grade_shoot_chance",30);
            $passNumLong    = self::def_grade($friendAbility,"grade_pass_num_long",30);
            $passNumShort   = self::def_grade($friendAbility,"grade_pass_num_short",30);
            $endurance      = self::def_grade($friendAbility,"grade_endurance",30);
            $sprint         = self::def_grade($friendAbility,"grade_sprint",30);
            $touchNum       = self::def_grade($friendAbility,"grade_touchball_num",30);
            $flexible       = self::def_grade($friendAbility,"grade_flexible",30);
        } else {
            $friendInfo = BaseStarModel::find($friendId);
            $userInfo           = new \stdClass();
            $userInfo->id       = $friendInfo->id;
            $userInfo->headImg  = '';
            $userInfo->nickName = $friendInfo->name;
            $userInfo->age      = $friendInfo->age;
            $userInfo->foot     = '';
            $userInfo->role1    = $friendInfo->position;
            $userInfo->team     = $friendInfo->team;

            $friendAbility          = new \stdClass();
            $friendAbility->grade   = $friendInfo->grade;
            $friendAbility->grade_shoot   = $friendInfo->shoot;
            $friendAbility->grade_pass    = $friendInfo->pass;
            $friendAbility->grade_strength= $friendInfo->strength;
            $friendAbility->grade_defense = $friendInfo->defense;
            $friendAbility->grade_dribble = $friendInfo->dribble;
            $friendInfo->grade_run        = $friendInfo->speed;

            $shootDesire    = 0;
            $shootStrength  = 0;
            $shootChance    = 0;
            $passNumLong    = 0;
            $passNumShort   = 0;
            $endurance      = 0;
            $sprint         = 0;
            $touchNum       = 0;
            $flexible       = 0;
        }

        // 基本数据
        $myAbility      = $userModel->user_global_ability($userId);

        $baseAbility    = [
            "self"  =>  self::get_base_map($myAbility),
            "friend"=>  self::get_base_map($friendAbility)
        ];

        //详细数据
        $detailAbility     = [
            ["name"=>"射门欲望",    "self"=>self::def_grade($myAbility,"grade_shoot_desire",30),     "friend"=> $shootDesire,   "max"=>100],
            ["name"=>"射门力量",    "self"=>self::def_grade($myAbility,"grade_shoot_strength",30),   "friend"=> $shootStrength, "max"=>100],
            ["name"=>"射门时机",    "self"=>self::def_grade($myAbility,"grade_shoot_chance",30),     "friend"=> $shootChance,   "max"=>100],
            ["name"=>"长传数量",    "self"=>self::def_grade($myAbility,"grade_pass_num_long",30),    "friend"=> $passNumLong,   "max"=>100],
            ["name"=>"短传数量",    "self"=>self::def_grade($myAbility,"grade_pass_num_short",30),   "friend"=> $passNumShort,  "max"=>100],
            ["name"=>"耐力",       "self"=>self::def_grade($myAbility,"grade_endurance",30),        "friend"=> $endurance,     "max"=>100],
            ["name"=>"冲刺能力",    "self"=>self::def_grade($myAbility,"grade_sprint",30),           "friend"=> $sprint,        "max"=>100],
            ["name"=>"触球数量",    "self"=>self::def_grade($myAbility,"grade_touchball_num",30),    "friend"=> $touchNum,      "max"=>100],
            ["name"=>"灵活性",      "self"=>self::def_grade($myAbility,"grade_flexible",30),         "friend"=> $flexible,      "max"=>100],
        ];

        return apiData()->add('friendInfo',$userInfo)
            ->add('baseAbility',$baseAbility)
            ->add('detailAbility',$detailAbility)
            ->send();
    }
}
