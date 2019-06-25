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
use App\Models\Base\BaseFootballCourtModel;
use App\Models\V1\CourtModel;
use App\Models\V1\MatchModel;
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
            ->select('a.court_id','a.user_id','a.gps_group_id','a.address','a.width','a.length','a.created_at','a.is_virtual','a.court_name','b.nick_name')
            ->whereNull('a.deleted_at')
            ->orderBy('a.court_id','desc')
            ->paginate(20);
        foreach($courtList as $court){
            $court->nick_name = $court->user_id == 0 ? "管理员":$court->nick_name;
        }

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


    /**
     * 绘制球场
     * */
    public function draw_court_top_point(Request $request)
    {
        $courtId = $request->input('courtId');

        $gpsInfo    = DB::table('football_court')->where('court_id',$courtId)->first();

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

    /**
     * 删除球场
     *
     * */
    public function delete_court(Request $request){

        $courtId    = $request->input('courtId');

        BaseFootballCourtModel::delete_court($courtId);

        return apiData()->send();
    }

    /**
     * 显示足球场地图
     * */
    public function court_border(Request $request)
    {
        $matchId        = $request->input('matchId');
        $courtFile      = "match/court-".$matchId.".json";
        $has            = Storage::disk('web')->has($courtFile);

        if(!$has)
        {
            $matchInfo      = MatchModel::find($matchId);
            $info           = CourtModel::find($matchInfo->court_id);
            $points         = \GuzzleHttp\json_decode($info->boxs);

            $points->A_D    = gps_to_bdgps($points->A_D);
            $points->AF_DE  = gps_to_bdgps($points->AF_DE);
            $points->F_E    = gps_to_bdgps($points->F_E);

            $arr            = [];
            foreach($points->center as $center)
            {
                foreach($center as $p)
                {
                    array_push($arr,$p);
                }
            }
            $points->center  = gps_to_bdgps($arr);

            Storage::disk('web')->put($courtFile,\GuzzleHttp\json_encode($points));

        }else{

            $points         = Storage::disk('web')->get($courtFile);
            $points         = \GuzzleHttp\json_decode($points);

        }
        return apiData()->add('points',$points)->send();
    }

    /**
     * 创建新球场
     * */
    public function add_new_court(Request $request){

        $name   = $request->input('name');
        $gps    = $request->input('gps');
        $gps    = explode(";",$gps);
        $gpsArr = array_splice($gps,0,6);

        $pos    = ["p_a","p_b","p_c","p_d","p_d1","p_a1"];

        $points = array_combine($pos,$gpsArr);

        //计算b,c的对立点b1,c1
        foreach($points as $key => $v)
        {
            $points[$key]   = explode(",",$v);
        }
        $points['p_b1'] = $points['p_c1'] = [];

        $k1         = self::p2k($points['p_a'],$points['p_b'],$points['p_d'],0);
        $k2         = self::p2k($points['p_a'],$points['p_b'],$points['p_d'],1);
        $lat        = self::k2p($points['p_a1'],$points['p_d1'],$k1,0);
        $lon        = self::k2p($points['p_a1'],$points['p_d1'],$k2,1);
        $points['p_b1'] = [$lat,$lon];
        
        $k1         = self::p2k($points['p_a'],$points['p_c'],$points['p_d'],0);
        $k2         = self::p2k($points['p_a'],$points['p_c'],$points['p_d'],1);
        $lat        = self::k2p($points['p_a1'],$points['p_d1'],$k1,0);
        $lon        = self::k2p($points['p_a1'],$points['p_d1'],$k2,1);
        $points['p_c1'] = [$lat,$lon];

        foreach($points as $key => $v){
            $points[$key] = implode(",",$v);
        }
        //2.添加新的球场
        $courtData  = [
            'user_id'       => 0,
            'court_name'    => $name
        ];
        $courtId        = (new CourtModel())->add_court($courtData);
        
        CourtModel::init_new_court($courtId,$points);

        return apiData()->send();
    }


    public static function p2k($p1,$p2,$p3,$key){

        return  ($p2[$key] - $p1[$key]) / ($p3[$key] - $p1[$key]);
    }


    public static function k2p($p1,$p2,$k,$key){

        return $p1[$key] + ($p2[$key] - $p1[$key]) * $k; 
    }

}