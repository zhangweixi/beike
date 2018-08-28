<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Base\BaseUserAbilityModel;
use App\Models\V1\MatchModel;
use App\Models\V1\UserModel;
use Illuminate\Http\Request;
use App\Models\V1\ShequMatchModel;
use App\Models\Base\BaseShequMatchModel;
use App\Models\Base\BaseShequMatchUserModel;
use Illuminate\Support\Facades\DB;


class ShequMatchController extends Controller
{

    /**
     * 比赛条件
     * */
    public function match_condition()
    {
        $condition = [
            "playerNumber" => 12,
            "address" => "上海虹口足球场",
            "money" => 12,
            "credit" => 0,
            "grade" => "不限",
            "creditList" => ["不限", "优秀", "良好", "中等"],
            "gradeList" => [60, 80, 90]
        ];
        return apiData()->set_data('condition', $condition)->send();
    }


    /**
     * 创建比赛
     * */
    public function create_match(Request $request)
    {
        $userId     = $request->input('userId');
        $matchDate  = $request->input("date");
        $matchTime  = $request->input('time');
        $number     = $request->input('number');
        $address    = $request->input('address');
        $grade      = $request->input('grade');
        $credit     = $request->input('credit');
        $signFee    = $request->input('signFee');

        $userBility = BaseUserAbilityModel::find($userId);

        $credit = text_to_credit($credit);

        $shequModel = new BaseShequMatchModel();
        $shequModel->user_id = $userId;
        $shequModel->begin_time = $matchDate . " " . $matchTime;
        $shequModel->total_num = $number;
        $shequModel->address = $address;
        $shequModel->grade = $grade;
        $shequModel->credit = $credit;
        $shequModel->sign_fee = $signFee;
        $shequModel->joined_num = 0;
        $shequModel->save();

        $userModel = new UserModel();
        $userInfo = $userModel->get_user_info($userId);



        $shareInfo = [
            "url" => "http://www.baidu.com",
            "title" => $userInfo['nickName'] . "邀您你参加足球比赛",
            "desc" => "西瓜电视空调，不如球场上爽一把",
            "img" => url('beike/images/default/foot.png')
        ];

        //自己加入比赛
        $matchUserModel = new BaseShequMatchUserModel();
        $matchUserModel->sq_match_id   = $shequModel->sq_match_id;
        $matchUserModel->user_id    = $userId;
        $matchUserModel->grade      = $userBility ? $userBility->grade : 0;
        $matchUserModel->save();


        return apiData()
            ->add("shareInfo", $shareInfo)
            ->add('matchId', $shequModel->sq_match_id)
            ->send(200, '比赛创建成功');
    }

    /**
     * 邀请加入比赛
     * */
    public function invite_match(Request $request)
    {
        $matchId    = $request->input('matchId');
        $userId     = $request->input('userId');

        $matchModel = new ShequMatchModel();

        $isFull     = ShequMatchModel::check_user_is_full($matchId);
        if($isFull == true){

            return apiData()->send(2001,'人数已满,不能再邀请');
        }

        //检查是否已经参加
        $isJoin     = ShequMatchModel::check_is_join_match($matchId,$userId);

        if($isJoin){

            return apiData()->send(2001,'已经参加了比赛，不能再邀请了');
        }


        $matchModel->invite_user($matchId,$userId);

        return apiData()->send();
    }

    /**
     * 处理邀请
     * */
    public function hand_invite(Request $request)
    {
        $inviteId   = $request->input('inviteId');
        $result     = $request->input('result');
        $matchModel = new ShequMatchModel();


        $code   = 200;

        if($result == 0){

            $matchModel->refuse_invite($inviteId);
            $msg    = "SUCCESS";

        }elseif($result == 1){

            //检查用户是否已满

            $msg    = $matchModel->accept_invite($inviteId);

            if($msg == "SUCCESS"){

                $code   = 200;
            }
        }

        return apiData()->send($code,$msg);
    }


    /**
     * 参加比赛
     * */
    public function join_match(Request $request)
    {
        $userId     = $request->input('userId');
        $matchId    = $request->input('matchId');

        //检查用户是否加入
        $matchUser = BaseShequMatchUserModel::where('user_id', $userId)->where('sq_match_id', $matchId)->first();

        if ($matchUser != null)
        {
            return apiData()->send(2001, '您已经加入了本比赛');
        }

        //检查人数是否已满
        //xxxx

        $shequMatch = new ShequMatchModel();
        $shequMatch->add_match_user($matchId,$userId);


        return apiData()->send(200, '成功加入比赛');
    }

    /**
     * 退出比赛
     * */
    public function quit_match(Request $request)
    {
        $userId    = $request->input('userId');
        $matchId   = $request->input('matchId');

        //减去申请列表中的用户
        BaseShequMatchUserModel::where('sq_match_id',$matchId)->where('user_id',$userId)->delete();

        BaseShequMatchModel::where('sq_match_id',$matchId)->decrement('joined_num');

        return apiData()->send(200,'已退出该场比赛');
    }


    /**
     * 社区首页
     * */
    public function shequ_index(Request $request)
    {
        $time   = time();
        $year   = date('Y',$time);
        $month  = date('m',$time);
        $day    = date('d',$time);


        $userId = $request->input('userId');


        $sqMatchModel   = new ShequMatchModel();

        //当前月份的数据
        $monthMatch     = $sqMatchModel->count_month_match($userId,$year,$month,true);

        //当日的比赛数据
        $date           = $year . "-" . $month . "-" . $day;
        $beginTime      = $date . " 00:00:01";
        $endtime        = $date . " 23:59:59";

        $dayMatch       = $sqMatchModel->user_match_list($userId,$beginTime,$endtime);

        foreach($dayMatch as $match)
        {

            $match->members = $sqMatchModel->get_match_user($match->sq_match_id);
        }

        $msgNum = 20;

        return apiData()
            ->add('year',(int)$year)
            ->add('month',(int)$month)
            ->add('msgNum',$msgNum)
            ->add('monthMatch',$monthMatch)
            ->add('dayMatch',$dayMatch)
            ->send();

    }

    /**
     * 月度比赛
     * */
    public function user_month_match(Request $request)
    {
        $time   = time();
        $year   = $request->input('year',date('Y',$time));
        $month  = $request->input('month',date('m',$time));
        $userId = $request->input('userId');

        $sqMatchModel   = new ShequMatchModel();

        $monthMatch     = $sqMatchModel->count_month_match($userId,$year,$month,true);

        return apiData()
            ->add('year',(int)$year)
            ->add('month',(int)$month)
            ->add('monthMatch',$monthMatch)
            ->send();
    }

    /* *
     * 社区比赛列表
     * */
    public function shequ_match_list(Request $request)
    {
        $userId     = $request->input('userId');
        $lon        = $request->input('longitude',0);
        $lat        = $request->input('latitude',0);


        $shequModel = new ShequMatchModel();
        $matches    = $shequModel->get_match_list(true);



        $allFriend  = DB::table('friend')->where('user_id',$userId)->pluck('friend_user_id')->toArray();
        if($lon == 0 || $lat == 0)
        {
            $userInfo   = UserModel::find($userId);
            $lat        = $userInfo->lat;
            $lon        = $userInfo->lon;
        }


        //获得所有的朋友

        foreach($matches as $match)
        {
            $match->isCreater   = $userId == $match->user_id ? 1 : 0;   //是否是创建者

            $match->credit      = credit_to_text($match->credit);

            $match->isJoined    = 0;    //是否参加比赛

            $match->friendNum   = 0;    //朋友的数量

            //球距离
            if($lat != 0 && $lon != 0 && $match->lat && $match->lon)
            {
                $match->distance    = gps_distance($lat,$lon,$match->lat,$match->lon);
            }


            //判断自己是否参加
            foreach($match->members as $member)
            {
                if($member->user_id == $userId)
                {
                    $match->isJoined    = 1;
                }

                if(in_array($member->user_id,$allFriend))
                {
                    $match->friendNum++;
                }
            }
        }
        return apiData()->add('matches',$matches)->send();
    }




    /**
     * 每日比赛
     * */
    public function user_day_match(Request $request)
    {

        $date   = $request->input('date');
        $userId = $request->input('userId');

        //当日的比赛数据

        $beginTime      = $date . " 00:00:01";
        $endtime        = $date . " 23:59:59";

        $sqMatchModel   = new ShequMatchModel();
        $dayMatch       = $sqMatchModel->user_match_list($userId,$beginTime,$endtime);

        foreach($dayMatch as $match)
        {
            $match->members = $sqMatchModel->get_match_user($match->sq_match_id);
        }

        return apiData()
            ->add('matches',$dayMatch)
            ->send();
    }

}