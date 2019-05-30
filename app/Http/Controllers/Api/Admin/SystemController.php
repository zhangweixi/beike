<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Dingo\Api\Http\Request;


class SystemController extends Controller{

    public function service_logs(Request $request)
    {
        $logs           = scandir(storage_path("logs"));
        $resultFiles    = [];
        foreach($logs as $file)
        {
            if(preg_match("/^\w/",$file))
            {
                array_push($resultFiles,['name'=>$file,'url'=>url("storage/logs/".$file)]);
            }
        }

        return apiData()->add('logs',$resultFiles)->send();
    }


    /**
     * 清除日志
     * */
    public function clear_log(Request $request){

        $logFile    = $request->input('logFile');

        $logFile    = storage_path("logs/".$logFile);

        file_put_contents($logFile,"");

        return apiData()->send();
    }

}