<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/9
 * Time: 14:17
 */

namespace App\Http\Controllers\Service;
use App\Http\Controllers\Controller;
use App\Models\Base\BaseUserAbilityModel;


class MatchGrade extends Controller
{
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
    public function getUserAbility($userId)
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
    public static function balance_grade($grade)
    {
        return (int) (sqrt($grade) * 60 + 40);
    }


    /*
     * 整体跑动分数
     * */
    public function get_global_run_grade($userId)
    {
        $speedHighGrade     = $this->get_global_single_grade($userId,"run_distance_high",45,true);
        $speedMiddleGrade   = $this->get_global_single_grade($userId,"run_distance_middle",25,true);
        $speedLowGrade      = $this->get_global_single_grade($userId,"run_distance_low",10,true);
        $speedMaxGrade      = $this->get_global_single_grade($userId,"run_speed_max",20,false);

        $runGrade           = $speedHighGrade + $speedMiddleGrade + $speedLowGrade + $speedMaxGrade;
        $runGrade               = self::balance_grade($runGrade);

        return $runGrade;
    }


    /**
     * 获得触球分数
     * @param $userId integer
     * @return float
     * */
    public function get_global_touchball_grade($userId)
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
    public function get_global_single_option_grade($userId,$type,$isAvg)
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
    public function get_global_multy_option_grade($userId,$options)
    {
        $grade  = 0;

        foreach($options as $option){

            $singleGrade    = $this->get_global_single_grade($userId,$option['type'],$option['percent'],$option['isAvg']);
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

        $grade                  = $downSelfNum/$totalUserNum * $percent / 100;
        $grade                  = sqrt($grade);
        return $grade;
    }


}