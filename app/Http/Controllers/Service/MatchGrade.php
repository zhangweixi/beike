<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/9
 * Time: 14:17
 */

namespace App\Http\Controllers\Service;
use App\Http\Controllers\Controller;
use App\Models\Base\BaseMatchResultModel;
use App\Models\Base\BaseUserAbilityModel;


class MatchGrade extends Controller
{


    /**
     * 获得用户新的能力值
     * @param $userId integer 用户ID
     * @return array
     * */
    public function get_global_new_grade($userId)
    {
        $gradeOptions   = [
            "shoot"     => [["type"=>"shoot_num_near",      "percent"=>70,"isAvg"=>true],
                            ["type"=>"shoot_num_far",       "percent"=>30,"isAvg"=>true]],
            "run"       => [["type"=>"run_distance_high",   "percent"=>45,"isAvg"=>true],
                            ["type"=>"run_distance_middle", "percent"=>25,"isAvg"=>true],
                            ["type"=>"run_distance_low",    "percent"=>10,"isAvg"=>true],
                            ["type"=>"run_speed_max",       "percent"=>20,"isAvg"=>false]],
            "pass"      => [["type"=>"pass_num_short",      "percent"=>70,"isAvg"=>true],
                            ["type"=>"pass_num_long",       "percent"=>30,"isAvg"=>true]],
            "strength"  => [["type"=>"shoot_speed_max",     "percent"=>55,"isAvg"=>false],
                            ["type"=>"pass_speed_max",      "percent"=>45,"isAvg"=>false]],
            "dribble"   => [["type"=>"dribble_dis_total",   "percent"=>50,"isAvg"=>true],
                            ["type"=>"change_direction_num","percent"=>30,"isAvg"=>true],
                            ["type"=>"abrupt_stop_num",     "percent"=>20,"isAvg"=>true]],
            "defense"   => [["type"=>"backrun_dis_total",   "percent"=>75,"isAvg"=>true],
                            ["type"=>"turn_around_num",     "percent"=>25,"isAvg"=>true]],

            "shoot_desire"      => [["type"=>"shoot_num_total",         "percent"=>100,"isAvg"=>true]],//射门欲望|射门总次数
            "shoot_strength"    => [["type"=>"shoot_speed",             "percent"=>100,"isAvg"=>false]],//射门力量|射门最大速度
            "shoot_chance"      => [["type"=>"shoot_distance_avg",      "percent"=>100,"isAvg"=>false]],//射门时机把控|射门距离
            "pass_num_short"    => [["type"=>"pass_num_short",          "percent"=>100,"isAvg"=>true]],//短传次数|短传数量
            "pass_num_long"     => [["type"=>"pass_num_long",           "percent"=>100,"isAvg"=>true]],//长传次数|长传数量
            "pass_power_short"  => [["type"=>"pass_speed_long",         "percent"=>100,"isAvg"=>false]],//短传力量|短传速度
            "pass_power_long"   => [["type"=>"pass_speed_short",        "percent"=>100,"isAvg"=>false]],//长传力量|长传速度
            "endurance"         => [["type"=>"run_distance_high",       "percent"=>100,"isAvg"=>true]],//耐力|总距离
            "speed"             => [["type"=>"run_speed_max",           "percent"=>100,"isAvg"=>false]],//速度|跑动平均速度
            "sprint"            => [["type"=>"run_high_speed",          "percent"=>100,"isAvg"=>false]],//冲刺|高速跑动速度
            "touchball_num"     => [["type"=>"touchball_num_total",     "percent"=>100,"isAvg"=>true]],//触球数量|触球数
            "flexible"          => [["type"=>"change_direction_num",    "percent"=>100,"isAvg"=>true]],//灵活度|转向次数
        ];

        $grades     = [];

        foreach($gradeOptions as $key => $option)
        {
            $grade  = $this->get_global_multy_option_grade($userId,$option);
            $grades["grade_".$key]  = $grade;
        }

        //计算用户的总分
        //个人分数=（射门评分*系数1+传球评分*系数2+力量评分*系数3+盘带评分*系数4+跑动评分*系数5+防守评分*系数6）/6
        //系数1、2、3、5的值都为1，系数4和系数6的值为0.5
        //个人分数=（射门评分*1+传球评分*1+力量评分*1+盘带评分*0.5+跑动评分*1+防守评分*0.5）/6

        $grade              = ($grades['grade_shoot'] + $grades['grade_pass'] + $grades['grade_strength'] + $grades['grade_dribble'] * 0.5 + $grades['grade_run'] + $grades['grade_defense']*0.5)/6;
        $grades['grade']    = $grade;

        return $grades;
    }



    /**
     * @param $matchId integer 比赛ID
     * @return array
     * */
    public function get_match_new_grade($matchId)
    {
        $gradeOptions   = [

            "shoot"     =>[ ["type"=>"shoot_num_far",   "percent"=>70],
                            ["type"=>"shoot_num_short", "percent"=>30]],//射门

            "pass"      =>[ ["type"=>"pass_s_num",      "percent"=>70],
                            ["type"=>"pass_l_num",      "percent"=>30]],//传球

            "strength"  =>[ ["type"=>"shoot_speed_max", "percent"=>55],
                            ["type"=>"pass_l_speed_max","percent"=>45]],//力量，根据射门速度来

            "run"       =>[ ["type"=>'run_high_dis',    "percent"=>45],
                            ["type"=>'run_mid_dis',     "percent"=>25],
                            ["type"=>'run_low_dis',     "percent"=>10],
                            ["type"=>'run_speed_max',   "percent"=>20]],//跑动

            "dribble"   =>[ ["type"=>"dribble_dis_total",   "percent"=>50],
                            ["type"=>"change_direction_num","percent"=>30],
                            ["type"=>"abrupt_stop_num",     "percent"=>20]],//盘带:盘带距离，转向，急停
            "defense"   =>[
                            ["type"=>"backrun_dis_total",   "percent"=>75],
                            ["type"=>"turn_around_num",     "percent"=>25]],//防守:回追距离，转身次数
            "shoot_desire"      => [["type"=>"shoot_num_total", "percent"=>100]],//射门欲望|射门总次数
            "shoot_strength"    => [["type"=>"shoot_speed_avg", "percent"=>100]],//射门力量|射门最大速度
            "shoot_chance"      => [["type"=>"shoot_dis_avg",   "percent"=>100]],//射门时机把控|射门距离
            "pass_num_short"    => [["type"=>"pass_s_num",      "percent"=>100]],//短传次数|短传数量
            "pass_num_long"     => [["type"=>"pass_l_num",      "percent"=>100]],//长传次数|长传数量
            "pass_power_short"  => [["type"=>"pass_s_speed_avg","percent"=>100]],//短传力量|短传速度
            "pass_power_long"   => [["type"=>"pass_l_speed_avg","percent"=>100]],//长传力量|长传速度
            "endurance"         => [["type"=>"run_high_dis",    "percent"=>100]],//耐力|总距离
            "speed"             => [["type"=>"run_speed_max",   "percent"=>100]],//速度|跑动最高速度
            "sprint"            => [["type"=>"run_high_speed_avg",  "percent"=>100]],//冲刺|高速平均速度
            "touchball_num"     => [["type"=>"touchball_num",       "percent"=>100]],//触球数量|触球数
            "flexible"          => [["type"=>"change_direction_num","percent"=>100]],//灵活度|转向次数
        ];

        $grades     = [];
        foreach($gradeOptions as $key => $option)
        {
            $grades["grade_".$key]  = $this->get_match_multy_option_grade($matchId,$option);
        }

        $grade              = ($grades['grade_shoot'] + $grades['grade_pass'] + $grades['grade_strength'] + $grades['grade_dribble'] * 0.5 + $grades['grade_run'] + $grades['grade_defense']*0.5)/6;
        $grades['grade']    = bcmul($grade,1,2);

        return $grades;
    }


    /*
     * @var 整体数据
     * */
    private $userAbility    = false;
    private $totalUserNum   = null;


    /**
     * 获得用户整体数据
     * @param $userId integer
     * @return object
     * */
    private function getUserAbility($userId)
    {
        if($this->userAbility == false)
        {
            $this->userAbility = BaseUserAbilityModel::find($userId);
        }

        return $this->userAbility;
    }


    /**
     * 获取总的人数
     * */
    private function getTotalUserNum()
    {
        if($this->totalUserNum == null)
        {
            $this->totalUserNum = BaseUserAbilityModel::count();
        }

        return $this->totalUserNum;
    }

    /**
     * 平衡分数，为了使数值最小的用户的分数不至于趋近于0，给一个基础的分数
     * @param $grade float
     * @return float
     * */
    private static function balance_grade($grade)
    {
        return (int) (sqrt($grade) * 60 + 40);
    }



    /**
     * 获得触球分数
     * @param $userId integer
     * @return float
     * */
    private function get_global_touchball_grade($userId)
    {
        $grade  = $this->get_global_single_grade($userId,"touchball_num_total",100,true);
        $grade  = self::balance_grade($grade);

        return $grade;
    }


    /**
     * 获得单项值的分数
     * @param $userId integer
     * @param $type string
     * @param $isAvg boolean 是否需要平均
     * @return integer
     * */
    private function get_global_single_option_grade($userId,$type,$isAvg)
    {
        $grade  = $this->get_global_single_grade($userId,$type,100,$isAvg);
        $grade  = self::balance_grade($grade);
        return $grade;
    }


    /**
     * 获取多项数据
     * @param $userId integer 用户ID
     * @param $options array 要选择的多项
     * @return float
     * */
    private function get_global_multy_option_grade($userId,$options)
    {
        $grade  = 0;

        foreach($options as $option){

            $singleGrade    = $this->get_global_single_grade($userId,$option['type'],$option['percent'],$option['isAvg']);
            mylogger($option['type'].'------'.$singleGrade);
            $grade          = $grade + $singleGrade;
        }

        return self::balance_grade($grade);
    }

    /**
     * 获得总体单项数据
     * @param $userId   integer 用户ID
     * @param $type     string  要计算的字段  实际是数据库的一个字段
     * @param $percent  integer 该项参数所占的比重
     * @param $isAvg    boolean 是否需要计算平均值
     * @return float
     * */
    private function get_global_single_grade($userId,$type,$percent,$isAvg)
    {
        $userAbility        = $this->getUserAbility($userId);
        $totalUserNum       = $this->getTotalUserNum();

        if($isAvg == true){

            $selfValue        = $userAbility->$type/$userAbility->match_num;
            $downSelfNum      = BaseUserAbilityModel::where('match_num','>',0)->whereRaw("{$type}/match_num < {$selfValue} ")->count();    //小于我的数量

        }else{

            $selfValue        = $userAbility->$type;
            $downSelfNum      = BaseUserAbilityModel::where('match_num','>',0)->where($type,"<",$selfValue)->count();    //小于我的数量
        }

        if($totalUserNum == 0){

            return 1;
        }

        $grade                  = $downSelfNum/$totalUserNum;
        $grade                  = sqrt($grade);
        $grade                  = $grade * $percent / 100;
        return $grade;
    }


    /*===================================单场比赛的数据==================================*/

    /*
     * @var 总的比赛数量
     * */
    private $matchTotalNum  = -1;

    /*
     * 获得获取比赛的总量
     * */
    private function get_match_total_num()
    {
        if($this->matchTotalNum == -1)
        {
            $matchNum               = BaseMatchResultModel::count();
            $this->matchTotalNum    = $matchNum;
        }

        return $this->matchTotalNum;
    }



    private $matchInfo  = false;
    /**
     * 比赛信息
     * */
    private function get_match_info($matchId)
    {
        if($this->matchInfo == false)
        {
            $this->matchInfo = BaseMatchResultModel::find($matchId);
        }

        return $this->matchInfo;
    }


    private function get_match_single_grade($matchId,$type,$percent)
    {
        //1.总场数
        $matchNum   = $this->get_match_total_num();

        $matchInfo  = $this->get_match_info($matchId);


        //2.小于自己值的数量
        $lowSelfNum = BaseMatchResultModel::where($type,'<',$matchInfo->$type)->count();


        if($matchNum  == 0)
        {
            return 1;
        }

        $grade      = $lowSelfNum/$matchNum;
        $grade      = sqrt($grade);
        $grade      = $grade * $percent / 100;

        return $grade;
    }


    /**
     * 某些项目的分数是由多项来决定的，比如传球有长传和短传
     * @param $matchId integer 比赛ID
     * @param $options array 分数项 [['pass_s_num',70,false],['pass_l_num',30,false]]
     * @return double
     *
     * */
    private function get_match_multy_option_grade($matchId,$options)
    {
        $grade  = 0;

        foreach ($options as $option){

            $optionGrade    = $this->get_match_single_grade($matchId,$option['type'],$option['percent']);
            $grade          = $grade + $optionGrade;

        }
        return self::balance_grade($grade);
    }






}