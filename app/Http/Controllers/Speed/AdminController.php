<?php
namespace App\Http\Controllers\Speed;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Speed\Model\PaperModel;
use Illuminate\Http\Request;
use DB;
use Excel;
use App;

class AdminController extends Controller{


    public function login(Request $request)
    {

        $name       = $request->input('name');
        $password   = $request->input('password');

        $password   = sha1(md5($password));

        $info       = DB::table('admin')->where('name',$name)->where('password',$password)->first();

        if($info)
        {
            $token  = create_token($info->admin_id);
            DB::table('admin')->where('admin_id',$info->admin_id)->update(['token'=>$token]);
            return apiData()->set_data('adminToken',$token)->send();

        }

        return apiData()->send(4001,'账户不存在或密码错误');
    }


    public function login_out(Request $request){

        $tokenAdmin     = $request->input('token');

        DB::table('admin')->where('token',$tokenAdmin)->update(['token'=>'']);

        return apiData()->send();
    }

    /**
     * 创建管理员
     * */
    public function create_admin(Request $request)
    {

        $name = $request->input('name');
        $pass = $request->input('password');
        $type = $request->input('type',0);

        $data = [

            'name'      => $name,
            'password'  => sha1(md5($pass)),
            'type'      => $type,
            'created_at'=> date_time()
        ];

        $id = DB::table('admin')->insertGetId($data);

        return apiData()->send();
    }

    /**
     * 上传EXCEL
     *
     * */
    public function read_question(Request $request)
    {

        $isSave   = $request->input('isSave',0);
        $isSave   = $isSave == 1 ? true : false;
        $filePath = $request->input('filepath');

        $data   = [];

        Excel::load($filePath, function($reader)use(&$data,$isSave) {

            $excel = $reader->all();

            foreach($excel as $sheet)
            {
                foreach($sheet as $key => $cell)
                {

                    $title      = $cell->question;
                    $type       = $cell->type;
                    $type       = trim($type);

                    $answer     = $cell->answer;
                    $id         = $key + 2;


                    $time   = date_time();
                    $sns    = ['A','B','C','D','E','F'];


                    switch($type)
                    {
                        case "多选":$type = 'checkbox';break;
                        case "单选":$type = 'radio';break;
                        case "判断":$type = 'radio';break;
                    }


                    $question = [
                        'title'         => $title,
                        'type'          => $type,
                        'created_at'    => $time,
                    ];

                    //$question['ans']    = $answer;

                    //array_push($data,$question);continue;


                    //添加到题库
                    $questionId = $isSave == true ? DB::table('question')->insertGetId($question) : $id;


                    $answer     = str_replace("\n","",$answer);
                    $answer     = str_replace("　","",$answer);
                    $answer     = str_replace(" ","",$answer);

                    $answers = explode("#",trim($answer,"#"));

                    foreach($answers as $key => $ans)
                    {
                        $temp           = explode('==',$ans);
                        if(count($temp) == 1)
                        {
                            exit("格式错误:第".$id."行,【".$ans."】");
                        }

                        //检查编码
                        //$bm = mb_detect_encoding($temp[0], array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));

                        $answers[$key]  = [
                            'question_id'=>$questionId,
                            'content'   => $temp[0],
                            'is_right'  => (int)$temp[1],
                            'sn'        => $sns[$key]
                        ];
                    }


                    try{

                        \GuzzleHttp\json_encode($answers);

                    }catch(Exception $e){

                        exit($id."条数据有异常");

                    }

                    $question['answer'] = $answers;
                    $question['id'] = $id;

                    if($isSave)
                    {
                        DB::table('answers')->insert($answers);

                    }else{

                        array_push($data,$question);

                    }
                }
            }
        });
        return apiData()->set_data('questions',$data)->send();
    }


    /**
     * 问题列表
     * */
    public function questions(Request $request)
    {

        $question = DB::table('question')->paginate(10);
        $paperModel = new PaperModel();

        foreach($question as $q)
        {
            $answer   = $paperModel->get_question_answer($q->question_id);
            $q->answer = $answer;
        }
        return apiData()->set_data('question',$question)->send();
    }


    /*同步更新所有用户*/
    public function down_all_users()
    {
        $weixin = new Weixin();
        $token = $weixin->get_token();
        //$token      = "0-SLn9ppjyszkWW0pvd5CQeOOg8e6A81tNA7Zm0YEcII8EH_avXxhu2m5F6uTXkzSM-Qxj2TwSQFWUoVnxupDeN0KuvPI4iGsINiHFJkkZ8as4NkAzrEMzQP7T6q7EsrcR1WyAKonLER4g5nBeiGiO9TV9zfHX8UWuAJBOvj1nzIVu1AG7kT7xX6BS65yMFzByoS0nu6gcigpnND4NhBTg";

        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/list?access_token={$token}&department_id=1&fetch_child=1";

        $users = file_get_contents($url);

        $users = json_decode($users);

        $newUser = [];
        $time = date_time();

        foreach($users->userlist as $user)
        {
            //检查用户是否存在
            $info = DB::table('user')->where('user_sn',$user->userid)->first();
            if($info)
            {
                $updateInfo = [
                    'real_name' => $user->name,
                    'nick_name' => $user->name,
                    'head'      => $user->avatar,
                    'mobile'    => $user->mobile,
                    'updated_at'=> $time,
                ];
                DB::table('user')->where('user_sn',$user->userid)->update($updateInfo);

            }else{

                array_push($newUser,[
                    'user_sn'   => $user->userid,
                    'real_name' => $user->name,
                    'nick_name' => $user->name,
                    'head'      => $user->avatar,
                    'mobile'    => $user->mobile,
                    'created_at'=> $time,
                    'updated_at'=> $time,
                ]);
            }


            //检查用户所在的部门
            foreach($user->department as $did)
            {
                $has = DB::table('user_department')->where('user_sn',$user->userid)->where('department',$did)->first();

                if(!$has)
                {
                    DB::table('user_department')->insert(['user_sn'=>$user->userid,'department'=>$did]);
                }
            }
        }
        $num = count($newUser);
        DB::table('user')->insert($newUser);

        return apiData()->send(200,'添加'.$num."个成员");
    }

    /**
     * 用户列表
     * */
    public function users(Request $request)
    {
        $keywords   = $request->input('keywords','');
        $users      = DB::table('user')
            ->orWhere(function($db)use($keywords)
            {
                $db->where('user_sn','like',"%".$keywords."%")
                    ->orWhere('nick_name','like',"%".$keywords."%")
                    ->orWhere('mobile','like',"%".$keywords."%");
            })->paginate(10);


        foreach($users as $user)
        {
            $departs = DB::table('user_department as a')->leftJoin('department as b','b.id','=','a.department')
                ->select('b.*')
                ->where('a.user_sn',$user->user_sn)
                ->where('a.is_delete',0)
                ->get();
            $user->departs = $departs;
        }
        return apiData()->set_data('users',$users)->send();
    }



    /*移除部门*/
    public function quit_department(Request $request)
    {
        $userSn     = $request->input('userSn');
        $depId      = $request->input('depId');

        DB::table('user_department')->where('user_sn',$userSn)->where('department',$depId)->update(['is_delete'=>1]);
        return apiData()->send();
    }

    /*同步所有部门*/
    public function down_department(Request $request)
    {
        $weixin     = new Weixin();
        $token      = $weixin->get_token();
        $url        = "https://qyapi.weixin.qq.com/cgi-bin/department/list?access_token={$token}&id=0";
        $departments= file_get_contents($url);
        $departments= \GuzzleHttp\json_decode($departments,true);
        $departments= $departments['department'];
        foreach($departments as $part)
        {
             $info = DB::table('department')->where('id',$part['id'])->first();

             if($info)
             {
                 DB::table('department')->where('id',$part['id'])->update($part);
             }else{

                 DB::table('department')->insert($part);
             }
        }
        return apiData()->send();
    }


    public function departments()
    {
        $departments = DB::table('department')->get();
        return apiData()->set_data('departments',$departments)->send();
    }

    public function change_pk_status(Request $request)
    {
        $id     = $request->input('id');
        $status = $request->input('status');
        DB::table('department')->where('id',$id)->update(['is_show'=>$status]);
        return apiData()->send();
    }


    /**
     * 部门统计
     * */
    public function count_department(Request $request)
    {

        $today      = current_date();
        $beginDate  = $request->input('beginDate',$today);
        $endDate    = $request->input('endDate',$today);
        $beginDate  = $beginDate." 00:00:00";
        $endDate    = $endDate." 23:59:59";


        //1.获取PK的部门
        $departments = DB::table('department')->where('is_show',1)->get()->toArray();

        foreach($departments as $depart)
        {
            //2.获得平均分
            $sql = "SELECT 
                      IFNULL(AVG (a.grade),0) as avgGrade
                    FROM paper as a
                    LEFT JOIN user_department as b ON b.user_sn = a.user_sn 
                    WHERE b.department = {$depart->id}
                    AND a.created_at >= '{$beginDate}'
                    AND a.created_at <= '{$endDate}' ";

            $avgInfo = DB::select($sql);
            $avgInfo = $avgInfo[0];
            $depart->avgGrade = number_format($avgInfo->avgGrade,2);

            //3.答题率


            //获得未答题的
            $finished = DB::table('paper as a')
                ->leftJoin('user_department as b','b.user_sn','=','a.user_sn')
                ->where('a.created_at',">=",$beginDate)
                ->where('a.created_at','<=',$endDate)
                ->where('b.department',$depart->id)
                ->where('a.status',2)
                ->count();

            //3.1总是试卷
            $total = DB::table('paper as a')
                ->leftJoin('user_department as b','b.user_sn','=','a.user_sn')
                ->where('a.created_at',">=",$beginDate)
                ->where('a.created_at','<=',$endDate)
                ->where('b.department',$depart->id)
                ->count();

            $percent = $total > 0 ? number_format($finished / $total * 100,2) : 0;
            $depart->percent        = $percent;
            $depart->totalNum       = $total;
            $depart->finishedNum    = $finished;
        }

        array_multisort(array_column($departments,'avgGrade'),SORT_ASC,$departments);

        return apiData()->set_data('departments',$departments)->send();
    }


    public function count_user(Request $request){
        
        $today      = current_date();
        $beginDate  = $request->input('beginDate',$today);
        $endDate    = $request->input('endDate',$today);
        $beginDate  = $beginDate." 00:00:00";
        $endDate    = $endDate." 23:59:59";

        //用户的总分
        $users = DB::table('user as a')
            ->leftJoin('paper as b','b.user_sn','=',DB::raw("a.user_sn and b.created_at >= '{$beginDate}' and b.created_at <= '{$endDate}'"))
            ->select("a.*",DB::raw('IFNULL(sum(b.grade),0) as totalGrade,IFNULL(sum(b.used_time),0) as usedTime'))
            ->groupBy('a.user_sn')
            ->orderBy('totalGrade','desc')
            ->orderBy('usedTime','asc')
            ->paginate(10);

        foreach($users as $user)
        {
            $finished = DB::table('paper')
                ->where('user_sn',$user->user_sn)
                ->where('status',2)
                ->where('created_at',">=",$beginDate)
                ->where('created_at',"<=",$endDate)
                ->count();

            $total    = DB::table('paper')
                ->where('user_sn',$user->user_sn)
                ->where('created_at',">=",$beginDate)
                ->where('created_at',"<=",$endDate)
                ->count();


            if($total > 0)
            {
                $percent  = number_format($finished / $total * 100);

            }else{

                $percent = 0;
            }

            $user->finished = $finished;
            $user->total    = $total;
            $user->percent  = $percent;
        }


        return apiData()->set_data('users',$users)->send();
    }


    /*管理员列表*/
    public function admin_list(Request $request)
    {
        $admins = DB::table('admin')->get();

        return apiData()->set_data('admins',$admins)->send();
    }


    public function get_admin_info(Request $request)
    {

        $adminId = $request->input('adminId');
        $adminInfo= DB::table('admin')->where('admin_id',$adminId)->first();

        return apiData()->set_data('adminInfo',$adminInfo)->send();
    }

    public function get_admin_info_by_token(Request $request)
    {
        $token  = $request->input('token');
        $adminInfo = DB::table('admin')->where('token',$token)->first();

        return apiData()->set_data('adminInfo',$adminInfo)->send();
    }

    public function edit_admin(Request $request)
    {

        $adminId    = $request->input('admin_id',0);
        $name       = $request->input('name');
        $password   = $request->input('password','');
        $data       = ['name'=>$name];

        if(!empty($password))
        {
            $data['password'] = sha1(md5($password));
        }

        if($adminId > 0)
        {
            DB::table('admin')->where('admin_id',$adminId)->update($data);

        }else{
            $data['created_at'] = date_time();
            DB::table('admin')->insert($data);
        }

        return apiData()->send();
    }

    public function delete_admin(Request $request)
    {
        $adminId = $request->input('adminId');
        DB::table('admin')->where('admin_id',$adminId)->delete();
        return apiData()->send();
    }

}
