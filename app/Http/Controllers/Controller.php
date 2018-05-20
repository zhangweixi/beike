<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Common\Params;
use Illuminate\Http\Request;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    

    /*
    * 公共方法
    * param Request $request 请求变量
    */
    public function action(Request $request,$action)
    {
        $action = tofeng_to_line($action);

        if(method_exists($this,$action))
        {

            return $this->$action($request);

        }else{

            return apiData()->send('404','not fund');
        }
    }

    /**
     * 检查参数
     * $params 要检查的参数，程二维数组形式
     * return \stdClass
     * */
    public function check_params(array $params)
    {
        $result = new Params();
        $result->status = true;
        $result->code = "200";

        foreach($params as $v)
        {
            if(empty($v[0]) && $v !== 0)
            {
                $result->status = false;
                $result->message= $v[1];
                if(isset($v[2])){
                    $result->code = $v[2];
                }
                break;
            }
        }
        return $result;
    }

}
