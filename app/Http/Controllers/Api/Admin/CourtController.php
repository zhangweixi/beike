<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3
 * Time: 17:06
 */

namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Service\MatchCaculate;
use App\Models\V1\CourtModel;
use Illuminate\Http\Request;
use DB;
use Maatwebsite\Excel\Facades\Excel;

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
            ->select('a.court_id','a.user_id','a.gps_group_id','a.address','a.width','a.length','a.created_at','a.is_virtual','b.nick_name')
            ->orderBy('a.court_id','desc')
            ->paginate(20);

        return apiData()->add('courtList',$courtList)->send();


    }

    public function caculate_court(Request $request){

        $courtId    = $request->input('courtId');

        $courtInfo  = CourtModel::find($courtId);
        if($courtInfo->gps_group_id != 0){

            MatchCaculate::call_matlab_court_init($courtId);
        }

        return apiData()->send();
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
                $listNum    = count($sheet);

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

                        $boxInfo    = ["angle"=>$angle,"type"=>$type];
                        array_push($lineBoxs,$boxInfo);
                        $lineIndex++;
                    }

                    if(count($lineBoxs) > 0)
                    {
                        $listBox[$listNum-$listKey-1]   = $lineBoxs;
                        $listBox[$listNum+$listKey]     = $lineBoxs;
                    }

                }

                if(count($listBox) > 0)
                {
                    $courTypes[$title]  = $listBox;
                }
            }
        });



        //以对称性，将半个球场扩展为整个球场

        foreach($courTypes as $type => $court)
        {
            DB::table('football_court_type')->where('people_num',$type)->update(['angles'=>\GuzzleHttp\json_encode($court)]);
        }

        return "ok";
    }


    /*
    * 绘制球场
    * */
    public function draw_court(Request $request)
    {
        $gpsGroupId = $request->input('gpsGroupId');

        $gpsInfo    = DB::table('football_court')->where('gps_group_id',$gpsGroupId)->first();


        $gpsInfo    = [
            ["position"=>"A","gps"=>$gpsInfo->p_a],
            ["position"=>"B","gps"=>$gpsInfo->p_b],
            ["position"=>"C","gps"=>$gpsInfo->p_c],
            ["position"=>"D","gps"=>$gpsInfo->p_d],
            ["position"=>"A1","gps"=>$gpsInfo->p_a1],
            ["position"=>"B1","gps"=>$gpsInfo->p_b1],
            ["position"=>"C1","gps"=>$gpsInfo->p_c1],
            ["position"=>"D1","gps"=>$gpsInfo->p_d1],
        ];

        foreach ($gpsInfo as &$data){

            $gps    = explode(",",$data['gps']);
            $data['device_lat']  = $gps[0];
            $data['device_lon']  = $gps[1];
        }
        return $gpsInfo;
    }



    /**
     * 绘制球场
     * */
    public function draw_court_all(Request $request)
    {
        $gpsGroupId = $request->input('gpsGroupId');
        $sql = "SELECT * from football_court_point 
                WHERE gps_group_id = '{$gpsGroupId}' ";

        $gpsInfo    = DB::select($sql);
        $points     = [
            'device'=>[],
            'mobile'=>[]
        ];

        foreach($gpsInfo as $point)
        {
            array_push($points['mobile'],['lat'=>$point->mobile_lat,'lon'=>$point->mobile_lon]);
            array_push($points['device'],['lat'=>$point->device_lat,'lon'=>$point->device_lon]);
        }
        return $points;
    }

    /**
     * 球场配置详情
     * */
    public function type_detail(Request $request){

        $courtTypeId    = $request->input('courtTypeId');

        $courtTypeInfo  = DB::table('football_court_type')->where('court_type_id',$courtTypeId)->first();
        $courtTypeInfo->angles  = \GuzzleHttp\json_decode($courtTypeInfo->angles);

        return apiData()->add("configInfo",$courtTypeInfo)->send();
    }

    /**
     * 编辑球场信息
     * */
    public function edit_court_config(Request $request){

        $courtTypeId    = $request->input('courtTypeId');

        $angles         = $request->input('angles');
        $angles         = \GuzzleHttp\json_decode($angles);

        foreach($angles as $key => $line){

            $lineAngles = [];

            foreach($line as $p){

                array_push($lineAngles,['type'=>substr($p,0,1),'angle'=>substr($p,1)]);
            }

            $angles[$key] = $lineAngles;
        }

        $data           = ['angles'=>\GuzzleHttp\json_encode($angles)];

        DB::table('football_court_type')->where('court_type_id',$courtTypeId)->update($data);

        return apiData()->add('d',$data)->send();
    }

}