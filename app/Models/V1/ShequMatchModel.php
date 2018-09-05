<?php
namespace App\Models\V1;
use App\Models\Base\BaseShequMatchModel;
use App\Models\Base\BaseShequMatchUserModel;
use Dingo\Api\Http\Request;
use function GuzzleHttp\Psr7\str;
use Illuminate\Database\Eloquent\Model;
use DB;


class ShequMatchModel extends Model{

    protected $table = "shequ_match";
    protected $primaryKey = "sq_match_id";

    public function add_match($matchData)
    {
        $matchData['created_at']    = date_time();

        $matchId = DB::table('shequ_match')->insertGetId($matchData);

        return $matchId;

    }


    /*
     * 统计某月的每天比赛数量
     * */
    public function count_month_match($userId,$year,$month)
    {
        $month      = full_str_length($month,2,0);
        $month      = $year."-".$month;

        $sql = "SELECT LEFT(b.begin_time,10) as `day` ,count(a.sq_match_id) as matchNum 
                FROM shequ_match_user as a 
                LEFT JOIN shequ_match as b ON b.sq_match_id = a.sq_match_id
                WHERE a.user_id = $userId 
                AND LEFT(b.begin_time,7) = '{$month}'
                GROUP BY `day` ";
        $matches = DB::select($sql);

        return $matches;
    }



    private $matchColum = ['sq_match_id','user_id','grade','credit','begin_time','total_num','joined_num','address','sign_fee','created_at','lat','lon'];

    /**
     * 用户比赛列表
     * @param $userId int 用户ID
     * @param $beginTime string
     * @param $endTime string
     * @return array
     * */
    public function user_match_list($userId,$beginTime="",$endTime="")
    {

        $db = DB::table('shequ_match_user as a')
            ->leftJoin('shequ_match as b','b.sq_match_id','=','a.sq_match_id')
            ->select("b.*")
            ->where('a.user_id',$userId);

        if($beginTime)
        {
            $db->where('b.begin_time',">=",$beginTime);
        }

        if($endTime)
        {
            $db->where('b.begin_time',"<=",$endTime);
        }

        $matches    = $db->get();

        foreach($matches as $match)
        {
            $timeInfo = explode(" ",$match->begin_time);
            $match->begin_date  = $timeInfo[0];
            $match->begin_time  = $timeInfo[1];
        }
        return $matches;
    }


    /* *
     * 获得比赛列表
     * */
    public function get_match_list($needMember = false)
    {
        $matches    = DB::table('shequ_match')
            ->select($this->matchColum)
            ->orderBy('begin_time','desc')
            ->paginate(20);

        $shequModel = new ShequMatchModel();

        foreach($matches as $match)
        {
            $timeInfo           = explode(" ",$match->begin_time);
            $match->begin_date  = $timeInfo[0];
            $match->begin_time  = $timeInfo[1];
            $match->distance    = 0;

            if($needMember)
            {
                $match->members     = $shequModel->get_match_user($match->sq_match_id,$match->user_id);
            }
        }
        return $matches;
    }


    /* *
     * 获得参加比赛的用户
     * @param $matchId int 比赛ID
     * */
    public function get_match_user($matchId,$userId=0)
    {
        $matchUsers = DB::table('shequ_match_user as a')
            ->leftJoin('users as b','b.id','=','a.user_id')
            ->select('a.user_id','b.nick_name',DB::raw('LEFT(b.birthday,4) as age'),'a.grade','b.role1 as role','b.head_img')
            ->where('a.sq_match_id',$matchId)
            ->get();

        $year   = date('Y');
        foreach($matchUsers as $user)
        {
            $user->head_img = get_default_head($user->head_img);
            $age        = $year - $user->age;
            $age        = $age > 200 ? "未知":(string)$age;
            $user->age  = $age;
            $user->isCreater    = $userId == $user->user_id ? 1 : 0;
        }

        return $matchUsers;
    }



    /**
     * 获得比赛邀请列表
     * @param $userId integer 用户ID
     * */
    public function get_match_invite($userId)
    {

        $invites = DB::table('shequ_match_invite as a')
            ->leftJoin('shequ_match as b','b.sq_match_id','=','a.match_id')
            ->select('a.match_id','a.invite_id','a.created_at','a.status','b.credit','b.address','b.total_num','b.grade','b.sign_fee','b.begin_time','b.user_id')
            ->where('a.user_id',$userId)
            ->paginate(5);


        foreach ($invites as $invite)
        {
            $timeInfo           = explode(' ',$invite->created_at);
            $beginTime          = explode(" ",$invite->begin_time);

            $invite->title      = "邀请信息";
            $invite->created_at = str_replace('-','.',$timeInfo[0])." ".str_replace('-',":",substr($timeInfo[1],0,5));
            $invite->beginDate  = $beginTime[0];
            $invite->beginTime  = $beginTime[1];
            //$invite->credit     = credit_to_text($invite->credit);
            unset($invite->begin_time);
            $invite->members    = $this->get_match_user($invite->match_id,$invite->user_id);
            $invite->joinedNum  = count($invite->members);

        }
        return $invites;
    }

    /**
     * 添加比赛的用户
     * @param $matchId integer 比赛ID
     * @param $userId integer 用户ID
     * */
    public function add_match_user($matchId,$userId)
    {
        $matchUser                  = new BaseShequMatchUserModel;
        $matchUser->user_id         = $userId;
        $matchUser->sq_match_id     = $matchId;
        $matchUser->save();


        //修改参与人员数量
        BaseShequMatchModel::where('sq_match_id',$matchId)->increment('joined_num');

    }

    /**
     * 邀请用户
     * @param $matchId integer 比赛ID
     * @param $userId integer 用户ID
     * @param $mobile string 手机号
     *
     * */
    public function invite_user($matchId,$userId,$mobile="")
    {
        $time   = date_time();
        $data   = [
            'match_id'  => $matchId,
            'user_id'   => $userId,
            "mobile"    => $mobile,
            'status'    => 0,
            'created_at'=> $time,
            'updated_at'=> $time
        ];

        $inviteId   = DB::table('shequ_match_invite')->insertGetId($data);
        if($userId == 0)
        {
            return true;
        }

        $messageModel   = new MessageModel();
        $messageModel->add_message("邀请信息","邀请你参加比赛","invite",$userId,$inviteId);

        return true;
    }

    /**
     * 接受邀请
     * @param $inviteId int 邀请ID
     * @return string
     * */
    public function accept_invite($inviteId)
    {
        $inviteInfo = DB::table('shequ_match_invite')->where('invite_id',$inviteId)->first();

        //检查用户是否已满
        $isFull = ShequMatchModel::check_user_is_full($inviteInfo->match_id);

        if($isFull == true){

            $this->refuse_invite($inviteId);

            return "本场比赛人数已满，自动拒绝了本邀请";

        }else{

            DB::table('shequ_match_invite')->where('invite_id',$inviteId)->update(['status'=>1]);

            $this->add_match_user($inviteInfo->match_id,$inviteInfo->user_id);

            return "SUCCESS";
        }
    }


    /**
     * 拒绝邀请
     * @param $inviteId integer 邀请ID
     * */
    public function refuse_invite($inviteId)
    {
        DB::table('shequ_match_invite')->where('invite_id',$inviteId)->update(['status'=>2]);
    }


    /**
     * 检查用户是否已满
     * @param $matchId integer 比赛ID
     * @return boolean
     * */
    public static function check_user_is_full($matchId)
    {
        $matchInfo = BaseShequMatchModel::find($matchId);
        if($matchInfo->total_num <= $matchInfo->joined_num) {

            return true;

        }else{

            return false;
        }
    }

    /**
     * 检查是否参加比赛
     * @param $matchId integer 比赛ID
     * @param $userId
     * @return boolean
     * */
    public static function check_is_join_match($matchId,$userId)
    {
        $isJoin     = BaseShequMatchUserModel::where('sq_match_id',$matchId)->where('user_id',$userId)->first();

        return $isJoin ? true : false;
    }


    /**
     * 检查手机好友是否参加比赛
     *
     * @param $matchId int 比赛ID
     * @param $mobile string 手机号
     * @return boolean
     * */
    public static function check_mobile_friend_is_invite($matchId,$mobile)
    {
        $isInvite = DB::table('shequ_match_invite')->where('match_id',$matchId)->where('mobile',$mobile)->first();

        return $isInvite ? true : false;
    }



}