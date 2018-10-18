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

}