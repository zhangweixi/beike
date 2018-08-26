<?php
namespace App\Models\V1;
use Illuminate\Database\Eloquent\Model;
use App\Models\Base\BaseFriendModel;
use App\Models\Base\BaseFriendApplyModel;
use Illuminate\Support\Facades\DB;

class FriendModel extends Model
{

    /* *
     * 检查是否是朋友
     * */
    static function is_friend($userId,$friendUserId)
    {
        $result = BaseFriendModel::where('user_id',$userId)->where('friend_user_id',$friendUserId)->first();

        return $result ? true : false;
    }


    /**
     * 是否在申请中
     * */
    static function is_appling($userId,$friendUserId)
    {
        $isApplying = BaseFriendApplyModel::where('user_id',$userId)->where('friend_user_id',$friendUserId)->where('status',0)->first();

        return $isApplying ? true : false;
    }

    /**
     * 添加为好友
     * */
    static function add_friend($userId,$friendUserId)
    {
        $friend  = new BaseFriendModel();
        $friend->user_id        = $userId;
        $friend->friend_user_id = $friendUserId;
        $friend->save();

        return  $friend->friend_id;
    }


    static function my_friend($userId,$keywords='')
    {
        $db = DB::table('friend as a')
            ->leftJoin('users as b','b.id','=','a.friend_user_id')
            ->leftJoin('user_global_ability as d','d.user_id','=','a.friend_user_id')
            ->select('a.friend_id','a.friend_user_id','b.nick_name','b.birthday as age',DB::raw('IFNULL(d.grade,0) as grade'),'b.role1 as role','b.head_img')
            ->where('a.user_id',$userId);

        if($keywords != "")
        {
            $db->where('b.nick_name','like',"%{$keywords}%");
        }

        $friends    = $db->paginate(10);

        foreach($friends as $friend)
        {
            $friend->head_img   = get_default_head($friend->head_img);
            $friend->age        = birthday_to_age($friend->age);
        }
        return $friends;
    }

    /**
     * 申请朋友列表
     * @param $userId integer 用户ID
     * */
    static function apply_list($userId)
    {
        $applyList = DB::table('friend_apply as a')
            ->leftJoin('users as b','b.id','=','a.user_id')
            ->leftJoin('user_global_ability as d','d.user_id','=','a.user_id')
            ->select('b.head_img','b.nick_name','b.role1 as role','b.birthday as age','d.grade','a.created_at','a.status','a.msg')
            ->where('a.friend_user_id',$userId)
            ->orderBy('apply_id','desc')
            ->paginate(10);

        foreach($applyList as $apply)
        {
            $timeInfo           = explode(' ',$apply->created_at);
            $apply->head_img    = get_default_head($apply->head_img);
            $apply->age         = birthday_to_age($apply->age);
            $apply->role        = $apply->role ?? "";
            $apply->grade       = $apply->grade ?? 0;
            $apply->created_at  = str_replace('-','.',$timeInfo[0])." ".str_replace('-',":",substr($timeInfo[1],0,5));
            $apply->msg         = $apply->msg == "" ? $apply->nick_name."请求加您为好友" : $apply->msg;
        }
        return $applyList;
    }
}