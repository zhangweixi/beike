<?php
namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V1\MatchController as V1MatchController;
use App\Models\Base\BaseStarTypeModel;
use Illuminate\Support\Facades\DB;

class MatchController extends V1MatchController{

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
