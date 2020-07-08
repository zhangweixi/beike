<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Base\BaseStarModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StarController extends Controller
{
    public function index() {
        $stars =  BaseStarModel::select('name','age','team','grade','img')->get();
        return apiData()->set_data('data',$stars)->send_old();
    }
}
