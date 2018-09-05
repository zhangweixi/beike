<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/31
 * Time: 16:32
 */

namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\Controller;
use App\Models\V1\DeviceModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class DeviceController extends Controller
{

    public function devices(Request $request)
    {
        $keywords   = $request->input('keywords');

        $devices    = DB::table('device as a')
            ->leftJoin('users as b','b.id','=','a.owner')
            ->select('a.*','b.nick_name','b.mobile')
            ->orderBy('device_id','desc');

        if($keywords)
        {
            $devices->where(function($db) use ($keywords)
            {
                $keywords   = "%{$keywords}%";

                $db->where('b.nick_name',"like",$keywords)->orWhere('a.device_sn',"like",$keywords)->orWhere('b.mobile','like',$keywords);
            });
        }

        $devices    = $devices->paginate(20);

        return apiData()->add('devices',$devices)->send();
    }

}