<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Base\BaseStarModel;
use App\Models\Base\BaseStarTypeModel;
use App\Models\Base\BaseUserAbilityModel;
use App\Models\V1\UserModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class StarController extends Controller
{
    /**
     * @desc 球星列表
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function index(Request $i) {
        $option = $i->input('option','grade');
        $option = $option ?: 'grade';
        $stars =  BaseStarModel::orderBy($option,'desc')
            ->select('id','name','age','team',$option.' as grade','img','position')
            ->get();
        foreach($stars as $star) {
            $star->img = url($star->img);
        }
        return apiData()->set_data('data',$stars)->send_old();
    }

    /**
     * @description 类似球星
     * @param Request $i
     * @return \Illuminate\Http\JsonResponse
     */
    public function same_star(Request $i) {
        $userId = $i->input('userId');
        $stars = BaseStarModel::global_ability_same_star($userId);
        foreach($stars as $star) {
            $star->img = url($star->img);
            $star->scale = round($star->scale * 100,2);
            $star->age = $star->age ?: 0;
        }
        return apiData()->set_data('data', $stars)->send_old();
    }

    /**
     * 球星等级
     */
    public function star_grade() {
        $starTypes = BaseStarTypeModel::all();
        foreach ($starTypes as $type) {
            $type->img = url($type->img);
        }
        return apiData()->set_data('starTypes',$starTypes)->send();
    }

    public function detail(Request $i) {
        $starInfo = BaseStarModel::find($i->input('id'));
        $starInfo->img = url($starInfo->img);
        return apiData()->set_data('data',$starInfo)->send();
    }
}
