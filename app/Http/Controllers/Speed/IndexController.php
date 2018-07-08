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
        $papers     = DB::table('paper')->where('user_sn',$userSn)->paginate(10);

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
     * 回答问题
     * */
    public function answer_question(Request $request)
    {

        $answer             = $request->input('answer');
        $paperQuestionId    = $request->input('paperQuestionId');
        $paperId            = $request->input('paperId');


        
























    }

}
