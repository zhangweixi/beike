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
    public function index(Request $i) {
        $option = $i->input('option','grade');
        $option = $option ?: 'grade';
        $stars =  BaseStarModel::orderBy($option,'desc')
            ->select('id','name','age','team',$option.' as grade','img','position')
            ->get();
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

    public function detail(Request $i) {
        $starInfo = BaseStarModel::find($i->input('id'));
        return apiData()->set_data('data',$starInfo)->send();
    }
}
