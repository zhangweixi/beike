<?php
namespace App\Models\V1;
use Dingo\Api\Http\Request;
use function GuzzleHttp\Psr7\str;
use Illuminate\Database\Eloquent\Model;
use DB;


class ShequMatchModel extends Model{

    public function add_match($matchData)
    {
        $matchData['created_at']    = date_time();

        $matchId = DB::table('shequ_match')->insertGetId($matchData);

        return $matchId;

    }



    public function count_month_match($userId,$year,$month,$fullMonth=false)
    {


        $month      = full_str_length($month,2,0);
        $month      = $year."-".$month;

        $sql = "SELECT LEFT(begin_time,10) as `day` ,count(*) as matchNum 
                FROM shequ_match 
                WHERE user_id = $userId 
                AND LEFT(begin_time,7) = '{$month}'
                GROUP BY `day` ";
        $matches = DB::select($sql);

        return $matches;

        $monthDays  = daysInmonth($year,$month);
        if($fullMonth == true)
        {
            $hasDays    = [];
            //收集已有的日期
            foreach ($matches as $key => $day)
            {
                array_push($hasDays,$day->day);
                $matches[$key]  = object_to_array($day);
            }

            //填补空缺的日期
            for($i=1;$i<= $monthDays;$i++)
            {
                $d  = full_str_length($i,2,0);
                if (!in_array($d,$hasDays)) {


                    array_push($matches,['day'=>$d,'matchNum'=>0]);
                }
            }
        }

        //按时间排序
        $matches = arraySequence($matches,'day','SORT_ASC');

        foreach($matches as $key => $day)
        {
            $matches[$key]['day']  = $month . "-" . $day['day'];
        }

        return $matches;
    }



    private $matchColum = ['sq_match_id','user_id','grade','credit','begin_time','total_num','joined_num','address','sign_fee','created_at'];

    /**
     * 用户比赛列表
     * @param $userId int 用户ID
     * @param $beginTime string
     * @param $endTime string
     * @return array
     * */
    public function user_match_list($userId,$beginTime="",$endTime="")
    {

        $db = DB::table('shequ_match')->where('user_id',$userId);

        if($beginTime)
        {
            $db->where('begin_time',">=",$beginTime);
        }

        if($endTime)
        {
            $db->where('begin_time',"<=",$endTime);
        }

        $matches    = $db->select($this->matchColum)->get();

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
            ->orderBy('begin_time')
            ->paginate(2);

        $shequModel = new ShequMatchModel();

        foreach($matches as $match)
        {
            $timeInfo           = explode(" ",$match->begin_time);
            $match->begin_date  = $timeInfo[0];
            $match->begin_time  = $timeInfo[1];
            $match->distance    = 0.3;

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
            $invite->credit     = credit_to_text($invite->credit);
            unset($invite->begin_time);
            $invite->members    = $this->get_match_user($invite->match_id,$invite->user_id);
            $invite->joinedNum  = count($invite->members);

        }
        return $invites;
    }


    /**
     * 接受邀请
     * @param $inviteId int 邀请ID
     * */
    public function accept_invite($inviteId)
    {
        DB::table('shequ_match_invite')->where('invite_id',$inviteId)->update(['status'=>1]);

    }


    /**
     * 拒绝邀请
     * @param $inviteId integer 邀请ID
     * */
    public function refuse_invite($inviteId)
    {
        DB::table('shequ_match_invite')->where('invite_id',$inviteId)->update(['status'=>2]);
    }

}