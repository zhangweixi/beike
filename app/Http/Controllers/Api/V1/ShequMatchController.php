<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\V1\UserModel;
use Illuminate\Http\Request;
use App\Models\V1\ShequMatchModel;
use App\Models\Base\BaseShequMatchModel;
use App\Models\Base\BaseShequUserMatchModel;



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
        $userId = $request->input('userId');
        $matchDate = $request->input("date");
        $matchTime = $request->input('time');
        $number = $request->input('number');
        $address = $request->input('address');
        $grade = $request->input('grade');
        $credit = $request->input('credit');
        $signFee = $request->input('signFee');


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

        return apiData()
            ->add("shareInfo", $shareInfo)
            ->add('matchId', $shequModel->sq_match_id)
            ->send(200, '比赛创建成功');
    }


    /**
     * 参加比赛
     * */
    public function join_match(Request $request)
    {
        $userId = $request->input('userId');
        $matchId = $request->input('matchId');
        $matchUserModel = new BaseShequUserMatchModel();

        //检查用户是否加入
        $matchUser = $matchUserModel->where('user_id', $userId)->where('sq_match_id', $matchId)->first();
        if ($matchUser != null) {
            return apiData()->send(2001, '您已经加入了本比赛');
        }

        //检查人数是否已满
        //xxxx

        $matchUserModel->user_id = $userId;
        $matchUserModel->sq_match_id = $matchId;
        $matchUserModel->save();

        return apiData()->send(200, '成功加入比赛');
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
        $userId     = $request->input('userId',0);
        $shequModel = new ShequMatchModel();
        $matches    = $shequModel->get_match_list(true);

        foreach($matches as $match)
        {
            $match->isCreater   = $userId == $match->user_id ? 1 : 0;

            $match->credit      = $this->get_credit_text();
        }
        return apiData()->add('matches',$matches)->send();
    }


    public function get_credit_text($credit)
    {
        if($credit == 0) {
            return "不限";
        }elseif($credit == 60) {
            return "一般";
        }elseif($credit == ""){

        }
    }


    /**
     * 每日比赛
     * */
    public function user_day_match(Request $request)
    {

        $date   = $request->input('date');
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
     * 通过手机号查找好友
     * */
    public function find_friend_by_mobile(Request $request)
    {



    }





}