<?php
namespace App\Http\Controllers\Api\V1;

use App\Common\Geohash;
use App\Common\Jpush;
use App\Models\Base\BaseFriendModel;
use App\Models\Base\BaseUserModel;
use App\Models\V1\MessageModel;
use App\Models\V1\UserModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\V1\FriendModel;
use App\Models\Base\BaseFriendApplyModel;
use Illuminate\Support\Facades\DB;


class FriendController extends Controller
{

    /**
     * 申请添加为好友
     * */
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
        $messageModel->add_message("好友请求",$userInfo->nick_name."请求加您为好友",'focus',$friendUserId,$newFriend->apply_id);

        //极光提示
        jpush_content("申请好友提示",$userInfo->nick_name."请求加您为好友",5001,1,$friendUserId,["userId"=>$userId]);

        return apiData()->send(200,'已发送，请等待同意吧');
    }


    /* *
     * 处理好友申请
     * */
    public function handle_apply(Request $request)
    {
        $status     = $request->input('result',0);
        $applyId    = $request->input('applyId');

        $applyInfo  = BaseFriendApplyModel::where('apply_id',$applyId)->first();


        //检查信息是否处理
        if($applyInfo->status != 0)
        {
            return apiData()->send(2001,'您已经处理过该信息');
        }

        //同意：添加到好友列表
        if($status == 1)
        {
            //检查是否已经成为好友
            $isFriend = FriendModel::is_friend($applyInfo->user_id,$applyInfo->friend_user_id);
            if($isFriend == false){

                FriendModel::add_friend($applyInfo->user_id,$applyInfo->friend_user_id);
            }

            $result     = "同意";

        }elseif($status == 0){

            $result     = "拒绝";
            $status     = 2;

        }

        //修改申请记录
        BaseFriendApplyModel::where('apply_id',$applyId)->update(['status'=>$status]);

        $userInfo       = BaseUserModel::find($applyInfo->friend_user_id);
        //通知申请人处理情况
        $messageModel   = new MessageModel();
        $messageModel->add_message("系统通知",$userInfo->nick_name.$result."了您的好友请求",'system',$applyInfo->user_id);


        //极光提示
        jpush_content("好友关注提示",$userInfo->nick_name.$result."了您的关注请求",5002,1,$applyInfo->user_id);

        //修改消息为已读
        MessageModel::read_message_by_type($applyInfo->friend_user_id,'focus',$applyId);


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
        $geohash    = UserModel::where('id',$userId)->value('geohash');
        $geohash    = substr($geohash,0,5);

        $allFriend  = $this->friend_and_applyed_ids($userId);


        $colum      = ['u.id','u.head_img','u.nick_name','u.role1 as role','u.birthday as age','g.grade'];

        $recommendFriends    = [];

        //1.获取朋友的朋友
        $otherFriends = DB::table('friend as a')
            ->leftJoin('friend as b','b.friend_user_id','=',DB::raw('a.friend_user_id and b.user_id <>'.$userId))
            ->leftJoin('users as u','u.id','=','b.user_id')
            ->leftJoin('user_global_ability as g','g.user_id','=','u.id')
            ->select($colum)
            ->where('a.user_id',$userId)
            ->where('b.user_id',">",0)
            ->whereNotIn('b.friend_user_id',$allFriend)
            ->get();



        $userMatches   = DB::table('shequ_match_user')->where('user_id',$userId)->pluck('sq_match_id')->toArray();

        foreach($otherFriends as $friend)
        {
            array_push($recommendFriends,$friend);
            array_push($allFriend,$friend->id);
        }


        //2.参加过同一场比赛的
        $matchFriend = DB::table('shequ_match_user as a')
            ->leftJoin('users as u','u.id','=','a.user_id')
            ->leftJoin('user_global_ability as g','g.user_id','=','u.id')
            ->select($colum)
            ->whereIn('a.sq_match_id',$userMatches)
            ->whereNotIn('a.user_id',$allFriend)
            ->where('a.user_id',"<>",$userId)
            ->get();

        foreach($matchFriend as $friend)
        {
            array_push($recommendFriends,$friend);
            array_push($allFriend,$friend->id);
        }


        //地理位置
        $neabyFriend = DB::table('users as u')
            ->leftJoin('user_global_ability as g','g.user_id','=','u.id')
            ->select($colum)
            ->whereNotIn('u.id',$allFriend)
            ->where('u.id','<>',$userId)
            ->where('u.geohash','like',"'{$geohash}%'")
            ->get();

        foreach($neabyFriend as $friend)
        {
            array_push($recommendFriends,$friend);
        }


        foreach($recommendFriends as $friend)
        {
            $friend->head_img   = get_default_head($friend->head_img);
            $friend->age        = birthday_to_age($friend->age);
            $friend->role       = $friend->role ?? "";
            $friend->grade      = $friend->grade ?? 0;
        }

        return apiData()->add('friends',$recommendFriends)->send();
    }


    /**
     *
     * @title 查找通讯录好友
     * @descriptioni 根据用户通讯录获取好友
     *
     * */
    public function mobile_friends(Request $request)
    {
        $userId     = $request->input('userId');
        $users      = $request->input('users');

        $users      = \GuzzleHttp\json_decode($users);

        $userInfo   = UserModel::find($userId);

        $allFriend   = $this->friend_and_applyed_ids($userId);

        //查找已加入系统的用户
        $mobiles    = [];
        $names      = [];

        foreach($users as $user)
        {
            if($user->mobile == trim($userInfo->mobile))
            {
                continue;
            }
            array_push($mobiles,trim($user->mobile));
            $names["i".$user->mobile] = $user->name;
        }

        //已注册的用户
        $registeredUsers    = DB::table('users as a')
            ->leftJoin('user_global_ability as d','d.user_id','=','a.id')
            ->select('a.id','a.birthday as age','role1 as role','a.mobile','a.head_img','d.grade')
            ->whereNotIn("a.id",$allFriend)
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
            $mobile     = $user->mobile;

            if(!in_array($mobile,$registedMobiles))
            {
                $unregiFriend   = [
                    'id'        => 0,
                    'mobile'    => $mobile,
                    'nick_name' => $user->name,
                    'role'      => '',
                    'grade'     => 0,
                    'head_img'  => get_default_head(),
                    'age'       => 0,

                ];
                array_push($unregisteredUsers,$unregiFriend);
            }
        }

        return apiData()->add('registeredUsers',$registeredUsers)->add('unregisteredUsers',$unregisteredUsers)->send();
    }


    /**
     * 已是朋友或已申请为朋友的用户ID
     * */
    private function friend_and_applyed_ids($userId)
    {
        //已经是朋友了的
        $allFriend      = DB::table('friend')->where('user_id',$userId)->pluck('friend_user_id')->toArray();

        //已经申请为朋友了的
        $applyFriend    = DB::table('friend_apply')->where('user_id',$userId)->where('status',0)->pluck('friend_user_id')->toArray();

        $allFriend      = array_merge($allFriend,$applyFriend);
        $allFriend      = array_unique($allFriend);

        return $allFriend;
    }

    /**
     * 搜索朋友
     * */
    public function search_friends(Request $request)
    {
        $userId     = $request->input('userId');
        $keywords   = $request->input('keywords');

        $friends = DB::table('users as a')
            ->leftJoin('friend as b','b.user_id','=',DB::raw(" $userId AND b.friend_user_id = a.id"))
            ->leftJoin('user_global_ability as d','d.user_id','=','a.id')
            ->select('a.id','a.nick_name','a.role1 as role','a.birthday as age','a.head_img',DB::raw("IF(b.user_id,1,0) as isFriend,IFNULL(d.grade,0) as grade"))
            ->where('a.nick_name','like',"%$keywords%")
            ->orderBy('a.id','desc')
            ->paginate(20);

        foreach($friends as $friend)
        {
            $friend->head_img   = get_default_head($friend->head_img);
            $friend->age        = birthday_to_age($friend->age);
            $friend->role       = $friend->role ?? "";
        }

        return apiData()->add('friends',$friends)->send();
    }


    /**
     * 附近朋友
     * 根据用户的经纬度来推荐用户
     * */
    public function nearby_friends(Request $request)
    {
        $userId     = $request->input('userId');
        $lat        = $request->input('latitude',0);
        $lon        = $request->input('longitude',0);
        $keywords   = $request->input('keywords','');

        //如果没有传递经纬度，获取用户平时的经纬度
        if($lat == 0 || $lon == 0)
        {
            $userInfo   = DB::table('users')->where('id',$userId)->first();
            $lat        = $userInfo->lat;
            $lon        = $userInfo->lon;

            if($lat == 0 || $lon == 0){

                return apiData()->send(2001,'无法获知您的位置');
            }
        }

        //搜索附近20公里范围的人
        $geohash    = new Geohash();
        $gpsStr     = $geohash->encode($lat,$lon);
        $gpslength  = 4;

        begin:

        $nearPrifix = substr($gpsStr,0,$gpslength);
        $nearbyStrs = $geohash->neighbors($nearPrifix);

        //已注册的用户
        $nearbyFriends  = DB::table('users as a')
            ->leftJoin('friend as b','b.friend_user_id','=',DB::raw('a.id AND b.user_id='.$userId))
            ->leftJoin('user_global_ability as d','d.user_id','=','a.id')
            ->select('a.id','a.nick_name','a.birthday as age','role1 as role','a.mobile','a.head_img','d.grade')
            ->where('b.friend_id')
            ->where('a.id',"<>",$userId)
            ->where(function($db) use ($nearbyStrs,$nearPrifix)
            {
                $db->where('geohash','like',$nearPrifix."%");
                foreach($nearbyStrs as $str)
                {
                    $db->orWhere('geohash','like',$str."%");
                }
            });

        if($keywords)
        {
            $nearbyFriends = $nearbyFriends->where('a.nick_name','like',"%{$keywords}%");
        }
        $nearbyFriends = $nearbyFriends->paginate(20);

        if($request->input('page',0) <= 1 && count($nearbyFriends) == 0 && $gpslength > 1)
        {
            $gpslength--;
            goto begin;
        }

        foreach($nearbyFriends as $user)
        {
            $user->head_img     = get_default_head($user->head_img);
            $user->grade        = $user->grade ?? 0;
            $user->role         = $user->role ?? "";
            $user->age          = birthday_to_age($user->age);
        }
        return apiData()->add('friends',$nearbyFriends)->send();
    }

    public function rank(Request $i) {
        $userId     = $i->input('userId');
        $rankType   = $i->input('rankType','grade');
        $rankType   = $rankType ?: 'grade';
        $page       = (int)$i->input('page',1);
        $onlyFriend = $i->input('onlyFriend',0);
        $friends    = UserModel::rank($rankType, $onlyFriend ? $userId : 0);

        if($page === 1) { //获取自己的排名
            $myRank = UserModel::userRank($userId,$rankType,$onlyFriend);
        } else {
            $myRank = 0;
        }

        return apiData()->add('myRank', $myRank)->add('users', $friends)->send();
    }
}