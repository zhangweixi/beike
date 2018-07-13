<?php
namespace  App\Http\Controllers\Speed;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Speed\Model\PaperModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Speed\Weixin;
use DB;


class IndexController extends Controller{


    public function __construct()
    {


    }


    public function index()
    {

        $info  = $this->wx->department->list();

        return apiData()->set_data('list',$info)->send();

    }






    public function user(Request $request)
    {
        $userId = $request->input('userId');
        $userInfo = DB::table('user')->where('user_sn',$userId)->first();
        return apiData()->set_data('userInfo',$userInfo)->send();

    }



    public function papers(Request $request)
    {
        //检查当天是否分配
        $today      = date('Y-m-d');
        $userSn     = $request->input('userSn');
        //DB::table('paper')->where('user_sn',$userId)->where('paper_sn',$today)->first();
        $papers     = DB::table('paper')->where('user_sn',$userSn)->orderBy('paper_id','desc')->paginate(10);

        $time = time();

        foreach($papers as $paper)
        {
            $beginTime  = strtotime($paper->begin_time);
            $endTime    = strtotime($paper->end_time);
            $status     = $paper->status;

            if($status == 2)
            {
                $text = "完成";
                $canAnswer  = 0;

            }elseif($time > $beginTime && $time < $endTime && $status == 0){

                $text       = "答题";
                $canAnswer  = 1;

            }elseif($time > $beginTime && $time < $endTime && $status == 1){

                $text = "续答";
                $canAnswer  = 1;

            }else{

                $text       = "缺考";
                $canAnswer  = 0;
            }

            $paper->statusText = $text;
            $paper->canAnswer = $canAnswer;
        }


        return apiData()->set_data('papers',$papers)->send();
    }



    /*
     * 试卷详情
     * */
    public function paper_detail(Request $request)
    {
        $paperId = $request->input('paperId');
        $paperModel = new PaperModel();
        $paperInfo  = $paperModel->get_paper_info($paperId);
        $questions  = $paperModel->get_paper_question($paperId,true);


        return apiData()->set_data('paperInfo',$paperInfo)->set_data('questions',$questions)->send();
    }

    /**
     * 刷新测试时间
     * */
    public function fresh_exam_time(Request $request)
    {
        //5秒记录一次
        $paperId    = $request->input('paperId');

        DB::table('paper')->where('paper_id',$paperId)->increment('used_time',5);

        return apiData()->send();
    }

    /*
     * 保存答案
     * */
    public function save_answer(Request $request)
    {

        $userAnswers        = $request->input('answers');
        $paperQuestionId    = $request->input('paperQuestionId');
        $paperId            = $request->input('paperId');

        //1.检查题目是否正确



        $answers = DB::table('paper_question as a')
            ->leftJoin('answers as b','b.question_id','=','a.question_id')
            ->select('b.*')
            ->where('a.paper_question_id',$paperQuestionId)
            ->get();

        $rightAnswers = [];

        foreach($answers as $ans)
        {
            $ans->is_right == 1 ? array_push($rightAnswers,$ans->sn):'';
        }


        //比较答案
        $userAnswers = explode(',',$userAnswers);
        $userAnswers = array_sort($userAnswers);
        $rightAnswers= array_sort($rightAnswers);

        $userAnswers    = implode(',',$userAnswers);
        $rightAnswers   = implode(',',$rightAnswers);



        if($userAnswers == $rightAnswers) //回答正确
        {
            //给总的记录加分
            $result = 1;
            DB::table('paper')->where('paper_id',$paperId)->increment('grade',10);

        }else{

            $result = 0;
        }
        DB::table('paper')->where('paper_id',$paperId)->update(['status'=>1]);
        //记录用户答案
        $data = ['answer'=>$userAnswers,'result'=>$result,'answer_at'=>date_time()];
        DB::table('paper_question')->where('paper_question_id',$paperQuestionId)->update($data);

        return apiData()->send();
    }


    /**
     * 结束比赛
     * */
    public function finish_exam(Request $request)
    {

        $paperId = $request->input('paperId');
        $userSn  = $request->input('userSn');

        DB::table('paper')->where('paper_id',$paperId)->where('user_sn',$userSn)->update(['status'=>2,'updated_at'=>date_time()]);

        $paperModel = new PaperModel();
        $paperInfo  = $paperModel->get_paper_info($paperId);

        return apiData()->set_data('paperInfo',$paperInfo)->send();
    }


    /*
     * 同样的比赛排序
     * */
    public function same_paper_sort(Request $request){

        $paperSn    = $request->input('paperSn');
        $papers     = DB::table('paper as a')
            ->leftJoin('user as b','b.user_sn','=','a.user_sn')
            ->select('a.*','b.nick_name','b.head')
            ->where('a.paper_sn',$paperSn)
            ->orderBy('a.grade','desc')
            ->orderBy('a.used_time','asc')
            ->get();


        return apiData()->set_data('papers',$papers)->send();
    }

    /**
     * 生成测试问卷
     * */
    public function create_paper()
    {

        $allQuestions = DB::table('question')->pluck('question_id')->toArray();

        DB::table('user')->orderBy('id')->chunk(200,function($users)use($allQuestions){

            $today  = current_date();
            $current = date_time();
            $time   = 600;//总的时间
            $begin  = $today." 07:00:00";
            $end    = $today." 22:00:00";


            foreach($users as $user)
            {
                $userSn     = $user->user_sn;

                //检查用户有没有本题库
                $hasPaper = DB::table('paper')->where('user_sn',$userSn)->where('paper_sn',$today)->first();
                if($hasPaper)
                {
                    continue;
                }

                $paperInfo = [
                    'paper_sn'  => $today,
                    'user_sn'   => $userSn,
                    'total_time'=> $time,
                    'used_time' => 0,
                    'begin_time'=> $begin,
                    'end_time'  => $end,
                    'created_at'=> $current,
                    'title'     => $today."的试题"
                ];

                $paperId = DB::table('paper')->insertGetId($paperInfo);
                //再获取用户所有的
                $userQuestions = DB::table('paper_question')->where('user_sn',$userSn)->pluck('question_id')->toArray();


                //去除已经分配过的题型
                $newQuestion = array_diff($allQuestions,$userQuestions);


                //随机获取10条 注意获得的是下标
                $quests     = array_rand($newQuestion,10);

                $paperQuest = [];
                foreach($quests as $key)
                {
                    $quest = [
                        'question_id'   => $newQuestion[$key],
                        'paper_id'      => $paperId,
                        'user_sn'       => $userSn
                    ];
                    array_push($paperQuest,$quest);
                }
                DB::table('paper_question')->insert($paperQuest);
            }

            return apiData()->send();
        });




























    }



    public function implot_quest(){

        $quests = DB::table('quest_template')
            ->where('id',"<",54)
            ->get();

        $time   = date_time();
        $sns    = ['A','B','C','D','E','F'];
        foreach($quests as $q)
        {
            $type = trim($q->type);
            switch($type)
            {
                case "多选":$type = 'checkbox';break;
                case "单选":$type = 'radio';break;
                case "判断":$type = 'radio';break;
            }

            mylogger($q->id);

            $question = [
                'title'         => $q->question,
                'type'          => $type,
                'created_at'    => $time
            ];

            //添加到题库
            $questionId = DB::table('question')->insertGetId($question);
            //$questionId = 1;


            $answers = explode("#",trim(trim(trim($q->answer,"　")),"#"));



            foreach($answers as $key => $ans)
            {
                $ans            = trim($ans);
                $ans            = trim($ans,"　");
                $ans            = trim($ans,"\n");
                $ans            = trim($ans);
                $ans            = trim($ans,"　");

                $temp           = explode('==',$ans);

                $answers[$key]  = [
                    'question_id'=>$questionId,
                    'content'   => $temp[0],
                    'is_right'  => (int)$temp[1],
                    'sn'        => $sns[$key]
                ];

                //return $answers;
                //if($key == 3) return $answers;
            }
            //return $answers;
            DB::table('answers')->insert($answers);
        }

        return apiData()->send();
    }


}
