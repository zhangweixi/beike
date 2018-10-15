<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MessageModel extends Model
{
    protected $table        = 'user_message';
    protected $primaryKey   = 'msg_id';

    /**
     * 添加通知信息
     * @param $title string 标题
     * @param $content string 内容
     * @param $type     string 类型
     * @param $contentId integer 对应的某个事件ID
     * @param $userId   integer 用户ID
     * @return integer
     * */
    public function add_message($title,$content,$type,$userId,$contentId=0)
    {
        $this->user_id  = $userId;
        $this->title    = $title;
        $this->content  = $content;
        $this->content_id=$contentId;
        $this->type     = $type;
        $this->thumb_img= "beike/images/default/msg-system.png";
        $this->save();
        return $this->msg_id;
    }


    /**
     * 阅读消息
     * @param $msgId integer 消息ID
     * @param $userId integer 用户ID
     * */
    public function read_message($msgId,$userId)
    {
        $msgInfo    = $this->find($msgId);
        if(strlen($msgInfo->readed_users) == 0) {

            $msgInfo->readed_users  = $userId;

        }else{

            $msgInfo->readed_users  = $msgInfo->readed_users . "," . $userId;
        }

        return $msgInfo->save();
    }

    /**
     * 按类型阅读消息
     * @param $userId integer 用户ID
     * @param $type string 类型
     * @param $contentId integer 内容ID
     *
     * */
    static function read_message_by_type($userId,$type,$contentId)
    {
        $msgInfo = DB::table('user_message')
            ->where('content_id',$contentId)
            ->where('type',$type)
            ->first();

        if(strlen($msgInfo->readed_users) != 0) {

            $userId  = $msgInfo->readed_users . "," . $userId;
        }

        DB::table('user_message')->where('msg_id',$msgInfo->msg_id)->update(['readed_users'=>$userId,'updated_at'=>date_time()]);
    }

        /**
     * 统计未读消息数量
     * @param $userId integer 用户ID
     * @param $type string 消息类型
     * */
    static function count_unread_msg($userId,$type='')
    {

        $sql    = "SELECT COUNT(*) AS total 
                    FROM  user_message 
                    WHERE (user_id = $userId OR user_id = 0) 
                    AND   (FIND_IN_SET('{$userId}',readed_users) = 0 OR FIND_IN_SET('{$userId}',readed_users) IS NULL )";

        if($type){
            $sql .= " AND type = '{$type}'";
        }

        $countInfo = DB::select($sql);

        return $countInfo[0]->total;
    }


    /**
     * 最新的一条未读消息
     * @param $userId integer 用户ID
     * @param $msgType string 消息类型
     * @return object
     * */
    static function last_unread_msg($userId,$msgType)
    {

        $sql    = "SELECT *  
                    FROM  user_message 
                    WHERE (user_id = $userId OR user_id = 0) 
                    AND   (FIND_IN_SET('{$userId}',readed_users) = 0 OR FIND_IN_SET('{$userId}',readed_users) IS NULL )
                    AND  type = '{$msgType}' 
                    ORDER BY msg_id 
                    LIMIT 1";

        $msgInfo = DB::select($sql);

        return count($msgInfo) > 0 ? $msgInfo[0] : false;
    }

}
