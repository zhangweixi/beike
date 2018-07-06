<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;



class ShequMatchController extends Controller{


    /**
     * 创建比赛
     * */
    public function create_match(Request $request)
    {
        $column = ['userId','beginDate','beginTime','signFee','address','lat','lon','totalNum'];

        $input = $request->only($column);


        return apiData()->set_data('data',$input)->send();
    }





}