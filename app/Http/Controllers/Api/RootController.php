<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/3
 * Time: 15:12
 */

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Dingo\Api\Http\Request;

class RootController extends Controller
{

    public function door(Request $request,$v,$controller,$action){

        $controllers    = [
            'test'      => "TestController",
            "sqmatch"   => "ShequMatchController",
        ];

        $v          = strtoupper($v);
        $vcode      = substr($v,1,1);
        $action     = tofeng_to_line($action);
        $controller = isset($controllers[$controller]) ? $controllers[$controller] : ucfirst($controller)."Controller";

        while(true)
        {
            $class  = "App\Http\Controllers\Api\V".$vcode."\\".$controller;

            if(class_exists($class)) {

                $class  = new $class();

                if(method_exists($class,$action)){

                    return $class->$action($request);
                }
            }


            if($vcode > 1){

                $vcode--;
                continue;
            }

            return apiData()->send(4004,'not fund');
        }
    }
}