<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Base\BaseStarModel;
use App\Models\Base\BaseStarTypeModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StarController extends Controller
{
    /**
     * @desc 球星列表
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function index() {
        $stars =  BaseStarModel::select('name','age','team','grade','img')->get();
        return apiData()->set_data('data',$stars)->send_old();
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
}
