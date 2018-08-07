<?php
namespace App\Http\Controllers\Api\V1;

use App\Models\Base\BaseFriendModel;
use App\Models\Base\BaseUserModel;
use App\Models\V1\MessageModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\V1\FriendModel;
use App\Models\Base\BaseFriendApplyModel;
use Illuminate\Support\Facades\DB;


class FriendController extends Controller
{

    public function add_friend(Request $request)
    {
        $userId         = $request->input('userId');
        $friendUserId   = $request->input('friendUserId');

        //检查是否彼此已经成为好友
        $isFriend       = FriendModel::is_friend($userId,$friendUserId);
        if($isFriend == true)
        {
            return apiData()->send(2001,'该用户已经是你的好友啦');
        }

        //检查是否已经申请过但是还没有处理
        $isApplying     = FriendModel::is_appling($userId,$friendUserId);
        if($isApplying == true)
        {
            return apiData()->send(2002,"您已经申请过，等待对方同意");
        }

        $newFriend                  = new BaseFriendApplyModel();
        $newFriend->user_id         = $userId;
        $newFriend->friend_user_id  = $friendUserId;
        $newFriend->save();

        $userInfo     = BaseUserModel::find($userId);

        //添加消息
        $messageModel   = new MessageModel();
        $messageModel->add_message("好友请求",$userInfo->nick_name."请求加您为好友",'apply_friend',$friendUserId,$newFriend->apply_id);

        return apiData()->send(200,'已发送，请等待同意吧');
    }


    /* *
     * 处理好友申请
     * */
    public function handle_apply(Request $request)
    {
        $result     = $request->input('result',0);
        $applyId    = $request->input('applyId');

        $applyInfo  = BaseFriendApplyModel::where('apply_id',$applyId)->first();


        //检查信息是否处理
        if($applyInfo->status != 0)
        {
            return apiData()->send(2001,'您已经处理过该信息');
        }

        //修改申请记录
        BaseFriendApplyModel::where('apply_id',$applyId)->update(['status'=>$result]);


        //同意：添加到好友列表
        if($result == 1)
        {
            FriendModel::add_friend($applyInfo->user_id,$applyInfo->friend_user_id);
            $result     = "同意";

        }else{

            $result     = "拒绝";

        }

        $userInfo       = BaseUserModel::find($applyInfo->friend_user_id);
        //通知申请人处理情况
        $messageModel   = new MessageModel();
        $messageModel->add_message("关注好友通知",$userInfo->nick_name.$result."了您的好友请求",'',$applyInfo->user_id);

        return apiData()->send(200,'操作成功');
    }

    /**
     * 我的好友
     * */
    public function my_friends(Request $request)
    {
        $userId     = $request->input('userId');
        $keywords   = $request->input('keywords','');
        $friends    = FriendModel::my_friend($userId,$keywords);
        return apiData()->add('friends',$friends)->send();
    }


    /**
     * 推荐朋友
     * */
    public function recommend_friends(Request $request)
    {

        $userId     = $request->input('userId');

        $sql = "SELECT 
                    a.id,
                    a.nick_name,
                    a.birthday as age,
                    IFNULL(a.role1,'') as role ,
                    a.head_img,
                    IFNULL(b.grade,0) as grade 
                FROM users a 
                LEFT JOIN user_global_ability b ON b.user_id = a.id 
                WHERE a.id NOT IN (SELECT friend_user_id FROM friend WHERE user_id = $userId) 
                LIMIT 20";

        $friends    = DB::select($sql);

        foreach($friends as $friend)
        {
            $friend->head_img   = get_default_head($friend->head_img);
            $friend->age        = birthday_to_age($friend->age);
        }

        return apiData()->add('friends',$friends)->send();
    }


    /**
     * 通讯录好友
     * */
    public function mobile_friends(Request $request)
    {
        $userId     = $request->input('userId');
        $users      = $request->input('users');
        $users      = \GuzzleHttp\json_decode($users);


        //查找已加入系统的用户
        $mobiles    = [];
        $names      = [];

        foreach($users as $user)
        {
            array_push($mobiles,$user[0]);
            $names["i".$user[0]] = $user[1];
        }

        //已注册的用户
        $registeredUsers    = DB::table('users as a')
            ->leftJoin('friend as b','b.friend_user_id','=',DB::raw('a.id AND b.user_id='.$userId))
            ->leftJoin('user_global_ability as d','d.user_id','=','a.id')
            ->select('a.id','a.birthday as age','role1 as role','a.mobile','a.head_img','d.grade')
            ->where('b.friend_id')
            ->whereIn('a.mobile',$mobiles)
            ->get();

        $registedMobiles    = [];
        foreach($registeredUsers as $user)
        {
            $user->nick_name    = $names["i".$user->mobile];
            $user->head_img     = get_default_head($user->head_img);
            $user->grade        = $user->grade ?? 0;
            $user->role         = $user->role ?? "";
            $user->age          = birthday_to_age($user->age);
            array_push($registedMobiles,$user->mobile);
        }


        //未加入系统的用户
        $unregisteredUsers    = [];
        foreach($users as $user)
        {
            $mobile     = $user[0];

            if(!in_array($mobile,$registedMobiles))
            {
                array_push($unregisteredUsers,['mobile'=>$mobile,'nick_name'=>$user[1]]);
            }
        }
        return apiData()->add('registeredUsers',$registeredUsers)->add('unregisteredUsers',$unregisteredUsers)->send();
    }


    /**
     * 邀请通讯录好友
     * */
    public function invite_mobile_friend(Request $request)
    {
        $mobile = $request->input('mobile');

        //发送邀请短信


        return apiData()->send(200,'已给您的好友发送了邀请信息');
    }

    /**
     * 附近朋友
     * */
    public function nearby_friends(Request $request)
    {
        $userId = $request->input('userId');
        $lat    = $request->input('latitude');
        $lon    = $request->input('longitude');
        $keywords = $request->input('keywords');


        //已注册的用户
        $nearbyFriends  = DB::table('users as a')
            ->leftJoin('friend as b','b.friend_user_id','=',DB::raw('a.id AND b.user_id='.$userId))
            ->leftJoin('user_global_ability as d','d.user_id','=','a.id')
            ->select('a.id','a.nick_name','a.birthday as age','role1 as role','a.mobile','a.head_img','d.grade')
            ->where('b.friend_id');
        if($keywords)
        {
            $nearbyFriends = $nearbyFriends->where('a.nick_name','like',"%{$keywords}%");
        }
        $nearbyFriends = $nearbyFriends->paginate(20);


        foreach($nearbyFriends as $user)
        {
            $user->head_img     = get_default_head($user->head_img);
            $user->grade        = $user->grade ?? 0;
            $user->role         = $user->role ?? "";
            $user->age          = birthday_to_age($user->age);
        }
        return apiData()->add('friends',$nearbyFriends)->send();
    }

}