<?php

namespace App\Models\Base;

use App\Models\V1\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseStarModel extends Model
{
    protected $table = 'star';
    protected $primaryKey = 'id';

    /**
     * @desc 类似球星
     * @param int $num 获取的数量
     * @param array $options  要匹配的选项
     * @param int $grade 用户发分数
     * @param string $position 位置
     * @return mixed
     */
    static function same_stars($num, $options, $grade, $position) {

        $columns = implode("+", $options);
        $db =  self::select('id','name','age','team','grade','img','height','position',DB::raw("$columns as total"),DB::raw("abs($columns - $grade) as scale" ));
        if($position) {
            $db->where('position', $position);
        }
        $stars = $db->orderBy("scale")
        ->limit($num)
        ->get();
        foreach($stars as $star) {
            $star->scale = round(min($grade,$star->total) / max($grade,$star->total),4);
            $star->defence = $star->defense;
        }
        return $stars;
    }


    static function global_ability_same_star($userId) {

        $userModel = new UserModel();
        $ability = $userModel->user_global_ability($userId);
        $userInfo = $userModel->find($userId);
        $abilityKey = ['shoot','pass','strength','dribble','defense','speed'];
        $abilityValue=[];
        foreach($abilityKey as $key) {
            $key1 = "grade_".$key;
            $abilityValue[$key] = $ability->$key1;
        }
        $abilityValue = array_sort($abilityValue);
        $abilityValue = array_splice($abilityValue,3,3);
        $totalGrade   = array_sum($abilityValue);
        $keys = array_keys($abilityValue);
        $position     = $userInfo->role?: $userInfo->role;
        return BaseStarModel::same_stars(7, $keys, $totalGrade, $position);
    }
}
