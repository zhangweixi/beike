<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/31
 * Time: 16:32
 */

namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\Controller;
use App\Models\Base\BaseVersionModel;
use App\Models\V1\DeviceModel;
use App\Models\V1\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use QrCode;


class DeviceController extends Controller
{

    /**
     * 设备列表
     * */
    public function devices(Request $request)
    {
        $keywords   = $request->input('keywords');

        $devices    = DB::table('device as a')
            ->leftJoin('users as b','b.id','=','a.owner')
            ->select('a.*','b.nick_name','b.mobile')
            ->orderBy('device_id','desc')
            ->whereNull('deleted_at');

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


    /**
     * 设备信息
     * */
    public function get_device_info(Request $request){

        $deviceId   = $request->input('deviceId',0);

        $deviceInfo = DeviceModel::find($deviceId);

        return apiData()->add('deviceInfo',$deviceInfo)->send();
    }


    /*编辑设备信息*/
    public function edit_device(Request $request)
    {
        $deviceId   = $request->input('device_id',0);

        $deviceInfo = $request->all();

        if($deviceId > 0) {
            foreach ($deviceInfo as $key =>$v){
                if($v == null || $v == "null")
                {
                    unset($deviceInfo[$key]);
                }
            }
            DeviceModel::where('device_id',$deviceId)->update($deviceInfo);

        }else{


            $deviceinfo = DeviceModel::where('device_sn',$deviceInfo['device_sn'])->first();

            if($deviceinfo){

                return apiData()->send(2001,"设备编号已存在");
            }

            $deviceInfo['created_at']   = date_time();
            $deviceInfo['updated_at']   = date_time();

            DeviceModel::create($deviceInfo);

        }

        return apiData()->send();
    }


    /*
     * 删除设备
     * */
    public function delete_device(Request $request)
    {

        $deviceId   = $request->input('deviceId');

        DeviceModel::where('device_id',$deviceId)->update(['deleted_at'=>date_time()]);

        return apiData()->send();
    }


    public function unbind_device(Request $request){

        $deviceId   = $request->input('deviceId',0);

        $deviceInfo = DeviceModel::find($deviceId);

        DeviceModel::where('device_id',$deviceId)->update(["owner"=>0]);

        UserModel::where('id',$deviceInfo->owner)->update(['device_sn'=>'']);

        return apiData()->send();
    }

    /**
     * 获取设备二维码
     * */
    public function get_device_qrs(Request $request){

        $qrs    = DB::table('device_qr')->where('deleted_at')->paginate(40);

        return apiData()->add('qrs',$qrs)->send();
    }
    /**
     * 创建设备编号二维码
     *
     * */
    public function create_device_qr(Request $request)
    {
        set_time_limit(0);

        $length     = $request->input('length');
        $prefix     = $request->input('prefix');
        $qrnum      = $request->input('num');
        $prefix     = trim($prefix);
        $prefix     = str_replace("，",",",$prefix);
        $prefixArr  = explode(",",$prefix);
        $addNum     = $request->input('addnum',"false") == "true" ? true : false;


        $begin      = "1";
        $end        = "9";

        for($i=0;$i<$length-1;$i++){

                $begin  .= "0";
                $end    .= "9";
        }

        $begin  = intval($begin);
        $end    = intval($end);

        //创建二维码
        foreach($prefixArr as $prefix)
        {

            $id = DB::table('device_qr')->insertGetId(['prefix'=>$prefix,'length'=>$length,'num'=>$qrnum,'created_at'=>date_time()]);

            $dir        = public_path("qr/" . $id);
            is_dir($dir)?   '' : mk_dir($dir);

            $tempArr    = [];
            $total      = 0;
            $tempNum = $qrnum * 5;

            for ($i = 0; $i < $tempNum; $i++)
            {
                $sn = $prefix . rand($begin, $end);
                if (in_array($sn, $tempArr))
                {
                    continue;
                }

                array_push($tempArr, $sn);

                QrCode::format('png')->size(1000)->margin(1)->generate($sn, $dir . "/" . $sn . ".png");

                $total++;

                if ($total == $qrnum) {
                    break;
                }
            }

            if($addNum){

                $this->add_qr_num($dir);
            }

            DB::table('device_qr')->where("id",$id)->update(['status'=>1]);
        }

        return apiData()->add('data',$request->all())->send();
    }


    public function add_qr_num($dir){

        //合成文字
        $imgs = scandir($dir);
        $len  = count($imgs);

        for($i=2;$i<$len;$i++)
        {
            $bg     = imagecreatetruecolor(1000,1100);
            $path   = $dir."/".$imgs[$i];
            $source = imagecreatefrompng($path);

            $white  = imagecolorallocate($bg, 255, 255, 255);
            imagefilledrectangle($bg, 0, 0, 1000, 1200, $white);
            $black = imagecolorallocate($bg, 0, 0, 0);
            imagecopy($bg,$source,0,0,0,0,1000,1000);
            imagettftext($bg,85,0,50,1060,$black,public_path("fonts/PingFangRegular.ttf"),"匹配码:".substr($imgs[$i],0,8));
            imagepng($bg,$path);
            imagedestroy($bg);
            imagedestroy($source);
        }
    }

    public function download_qr(Request $request){

        $prefix     = $request->input('prefix');
        $id         = $request->input('id');

        $dir        = public_path("qr/".$id);

        $zipFile    = $dir."-".$prefix.".zip"; // 最终生成的文件名（含路径）

        if(!file_exists($zipFile)){

            // 生成文件
            $zip        = new \ZipArchive(); // 使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释

            if ($zip->open($zipFile,\ZipArchive::OVERWRITE) !== true) {  //OVERWRITE 参数会覆写压缩包的文件 文件必须已经存在


                if($zip->open($zipFile,\ZipArchive::CREATE) !== true){ // 文件不存在则生成一个新的文件 用CREATE打开文件会追加内容至zip

                    exit('无法打开文件，或者文件创建失败');
                }
            }
            $imgs = scandir($dir);
            $len  = count($imgs);

            for($i=2;$i<$len;$i++)
            {
                $zip->addFile($dir."/".$imgs[$i],$imgs[$i]);//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下 写上目录就会存放至目录
            }
            $zip->close(); // 关闭
        }

        response($zipFile)->header('Content-Type','application/zip');
        return response()->download($zipFile);
    }


    public function delete_qr(Request $request)
    {
        $prefix     = $request->input('id');
        DB::table('device_qr')->where('id',$prefix)->update(['deleted_at'=>date_time()]);

        return apiData()->send();
    }


    /**
     * 获得设备编码
     * */
    public function get_device_code_versions(Request $request){

        $deviceVersion  = BaseVersionModel::orderBy('id','desc')->paginate(50);

        return apiData()->set_data('deviceCodeVersions',$deviceVersion)->send();

    }

    public function add_device_code(Request $request){

        $file       = $request->file('file');
        $name       = $file->getClientOriginalName();
        $extension  = explode(".",$name)[1];

        $file       = $file->storeAs('device-code',create_member_number().".".$extension,'web');

        $data       = [
            'version'       => $request->input('version'),
            'publish'       => $request->input('publish'),
            'must_upgrade'  => $request->input('must_upgrade'),
            'type'          => $request->input('type'),
            'file'          => $file,
            'created_at'    => date_time()
        ];

        BaseVersionModel::insert($data);

        return apiData()->send();
    }

    /*
     * 删除设备版本
     * */
    public function delete_device_code(Request $request){

        $id             = $request->input('id',0);
        $versionInfo    = BaseVersionModel::find($id);
        $file           = public_path($versionInfo->file);
        if(file_exists($file)){

            unlink($file);
        }

        BaseVersionModel::where('id',$id)->delete();
        return apiData()->send();
    }
}