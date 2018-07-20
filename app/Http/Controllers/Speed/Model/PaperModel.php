<?php
namespace App\Http\Controllers\Speed\Model;
use Illuminate\Database\Eloquent\Model;
use DB;

class PaperModel extends Model{


    public function get_paper_info($paperId)
    {
        $paperInfo = DB::table('paper')->where('paper_id',$paperId)->first();

        return $paperInfo;
    }


    /*
     * 获得试卷问题
     * */
    public function get_paper_question($paperId,$needAnswer = false)
    {
        $questions = DB::table('paper_question as a')
            ->leftJoin('question as b','b.question_id','=','a.question_id')
            ->select('b.*','a.paper_question_id','a.answer','a.result','a.answer_at')
            ->where('paper_id',$paperId)
            ->get();

        if($needAnswer == true)
        {
            foreach($questions as $key=> $question)
            {

                $answers = $this->get_question_answer($question->question_id);
                $question->answers = $answers;
                $question->nth = $key;

            }
        }
        return $questions;
    }


    /*
     * 获得问题答案
     * */
    public function get_question_answer($questionId)
    {
        $answers = DB::table('answers')->where('question_id',$questionId)->get();

        return $answers;
    }


    public function get_paper_sn_info($paperSn)
    {
        return DB::table('papersn')->where('paper_sn',$paperSn)->first();

    }
}