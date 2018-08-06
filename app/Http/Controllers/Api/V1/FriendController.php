<?php
namespace App\Http\Controllers\Api\V1;

use App\Models\Base\BaseFriendModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\V1\FriendModel;
use App\Models\Base\BaseFriendApplyModel;


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

            $friendId   = FriendModel::add_friend($applyInfo->user_id,$applyInfo->friend_user_id);
        }

        //通知申请人处理情况


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


}