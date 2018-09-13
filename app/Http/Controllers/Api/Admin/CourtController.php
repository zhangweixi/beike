<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3
 * Time: 17:06
 */

namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Models\V1\CourtModel;
use Illuminate\Http\Request;
use DB;

class CourtController extends Controller
{

    public function court_types(Request $request)
    {



    }

    /**
     * 球场列表
     * */
    public function court_list(Request $request)
    {

        $courtList  = DB::table('football_court as a')
            ->leftJoin('users as b','b.id','=','a.user_id')
            ->select('a.court_id','a.user_id','a.gps_group_id','a.address','a.width','a.length','a.created_at','b.nick_name')
            ->orderBy('a.court_id','desc')
            ->paginate(20);

        return apiData()->add('courtList',$courtList)->send();


    }


    /**
     * 读取配置文件
     *
     * */
    public function read_config(Request $request)
    {
        $filePath   = public_path("court.xlsx");

        $courTypes  = [];


        Excel::load($filePath, function ($reader) use (&$courTypes) {

            $excel = $reader->all();

            foreach ($excel as $sheet)                  //每个sheet
            {
                $title      = $sheet->getTitle();
                $listBox    = [];
                $allList    = count($sheet)-1;

                foreach ($sheet as $listKey => $cell)   //一行
                {

                    $lineBoxs   = [];
                    $lineIndex  = 0;

                    foreach($cell as $lineKey => $angle)    //每一个格子
                    {
                        $type   = substr($angle,0,1);

                        if($type == 'D' || $type == 'X')
                        {
                            $angle      = substr($angle,1);

                        }else{

                            $type   = "N";
                            $angle  = sprintf("%0.2f", $angle);
                        }

                        $boxInfo    = ["y"=>$allList-$listKey,"x"=>$lineIndex,"angle"=>$angle,"type"=>$type];
                        array_push($lineBoxs,$boxInfo);
                        $lineIndex++;
                    }

                    if(count($lineBoxs) > 0)
                    {
                        array_push($listBox,$lineBoxs);
                    }
                }

                if(count($listBox) > 0)
                {
                    $courTypes[$title]  = $listBox;
                }
            }
        });


        foreach($courTypes as $type => $court)
        {
            DB::table('football_court_type')->where('people_num',$type)->update(['angles'=>\GuzzleHttp\json_encode($court)]);
        }

        return "ok";
    }

}