<?php
namespace App\Http\Controllers\Speed;

use App\Http\Controllers\Controller;
use Cyberduck\LaravelExcel\Factory\ImporterFactory;
use Illuminate\Http\Request;
use DB;
//use Maatwebsite\Excel;
use Excel;





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

        $filePath = "D:/www/tiku.xlsx";
        $data   = [];

        Excel::load($filePath, function($reader)use(&$data) {

            $excel = $reader->all();

            foreach($excel as $sheet)
            {
                foreach($sheet as $cell)
                {

                    $title      = $cell->question;
                    $type       = $cell->type;
                    $type       = trim($type);

                    $answer     = $cell->answer;
                    $id         = $cell->id;


                    $time   = date_time();
                    $sns    = ['A','B','C','D','E','F'];


                    switch($type)
                    {
                        case "多选":$type = 'checkbox';break;
                        case "单选":$type = 'radio';break;
                        case "判断":$type = 'radio';break;
                    }

                    mylogger($id);

                    $question = [
                        'title'         => $title,
                        'type'          => $type,
                        'created_at'    => $time,
                        //'id'            => (int)$id,
                    ];
                    //$question['ans']    = $answer;

                    //array_push($data,$question);continue;


                    //添加到题库
                    $questionId = DB::table('question')->insertGetId($question);
                    //$questionId = $id;

                    $answer     = str_replace("\n","",$answer);
                    $answer     = str_replace("　","",$answer);
                    $answer     = str_replace(" ","",$answer);

                    $answers = explode("#",trim($answer,"#"));

                    foreach($answers as $key => $ans)
                    {

                        $temp           = explode('==',$ans);

                        //$bm = mb_detect_encoding($temp[0], array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
//                        if($bm == 'ASCII')
//                        {
//                            $temp[0] = cp1251_utf8($temp[0]);
//                        }

                        $answers[$key]  = [
                            'question_id'=>$questionId,
                            'content'   => $temp[0],
                            'is_right'  => (int)$temp[1],
                            'sn'        => $sns[$key]
                        ];


                        //return $answers;
                        //if($key == 3) return $answers;
                    }
                    //$question['answers']   = $answers;
                    //dd($question);
                    //array_push($data,$question);
                    //return $data;
                    //return $answers;
                     DB::table('answers')->insert($answers);
                }


            }

        });


        return apiData()->send();

    }


    public function gettoken()
    {
        $weixin = new Weixin();
        $token = $weixin->get_token();

        return $token;
    }


    public function get_all_users()
    {
        //$weixin = new Weixin();
        //$token = $weixin->get_token();
        $token = "C4xHeo_-q1VA8MTxHUgM6JXJqRYNkXnEXCPis0adutZMlXAdv1TPLxSuHeBvkJW0uboZIIVoEcFzZKRaTZRzCsUdMzKAOx9zW5gavbmG4frnYZr0sd4KNVBPClBe375ngP-coMtWD7PMEHgF4vCwK9Gj5rcLqXhix6e70Br_w5cBYUr7_s6hAduhqswpwFP0qiCQ2RRaCMj6qW8Mh6b0EA";


        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/list?access_token={$token}&department_id=1&fetch_child=1";

        $users = file_get_contents($url);

        $users = json_decode($users);

        $arrUser = [];
        $time = date_time();

        foreach($users->userlist as $user)
        {
            array_push($arrUser,[
                'user_sn'   => $user->userid,
                'real_name' => $user->name,
                'nick_name' => $user->name,
                'head'      => $user->avatar,
                'mobile'    => $user->mobile,
                'created_at'=> $time,
                'updated_at'=> $time,
            ]);
        }

        DB::table('user')->delete();
        $num = DB::table('user')->insert($arrUser);


        return apiData()->send(200,'添加'.$num."个成员");


    }



}

function cp1251_utf8( $sInput )
{
    $sOutput = "";

    for ( $i = 0; $i < strlen( $sInput ); $i++ )
    {
        $iAscii = ord( $sInput[$i] );

        if ( $iAscii >= 192 && $iAscii <= 255 )
            $sOutput .=  "&#".( 1040 + ( $iAscii - 192 ) ).";";
        else if ( $iAscii == 168 )
            $sOutput .= "&#".( 1025 ).";";
        else if ( $iAscii == 184 )
            $sOutput .= "&#".( 1105 ).";";
        else
            $sOutput .= $sInput[$i];
    }

    return $sOutput;
}