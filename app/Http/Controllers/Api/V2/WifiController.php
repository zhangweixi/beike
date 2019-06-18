<?php
namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Base\BaseWifiModel as WifiModel;
use PhpParser\Node\Expr\Cast\Object_;


class WifiController extends Controller{

    /**
     * 添加新的Wifi
     * @param $request Request
     * @return object
     * */
    public function add_wifi(Request $request){

        (int) $userId =  $request->input('userId');
        WifiModel::where('user_id',$userId)->update(['is_default'=>0]);

        $wifiInfo=WifiModel::create([
            "user_id"   => $userId,
            "name"      => $request->input('name'),
            "password"  => $request->input('password'),
            "tag"       => $request->input('tag'),
            "is_default"=> 1
        ]);

        return apiData()->add("wifi",$wifiInfo)->send();
    }

    /**
     * 编辑WIFI
     * @param $request Request
     * @return object
     * */
    public function edit_wifi(Request $request){

        $data   = $request->only(["name","password","is_default","tag"]);
        $wifi   = WifiModel::find($request->input('wfId'));

        if($data['is_default'] == 1){

            WifiModel::where('user_id',$wifi->user_id)->update(['is_default'=>0]);
        }

        $wifi->update($data);
        return apiData()->send();
    }

    /**
     * 删除Wifi
     * @param $request Request
     * @return object
     * */
    public function delete_wifi(Request $request){

        WifiModel::where('wf_id',$request->input('wfId'))->delete();

        return apiData()->send();
    }

    /**
     * 获得Wifi列表
     * @param $request Request
     * @return object
     * */
    public function wifi_list(Request $request){

        $userId     = $request->input('userId',0);

        $wifis      = WifiModel::where('user_id',$userId)
            ->select('wf_id','name','password','tag','is_default')
            ->orderBy('is_default','desc')
            ->orderBy('wf_id','asc')
            ->get();

        return apiData()->add('wifiList',$wifis)->send();
    }

    /**
     * WiFi详情
     * */
    public function wifi_detail(Request $request){


    }
}