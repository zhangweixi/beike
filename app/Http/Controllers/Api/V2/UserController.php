<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Base\BaseStarTypeModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\UserController as V1Controller;

class UserController extends V1Controller
{
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
