<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\V1\UserModel;
use App\Common\MobileMassege;



class UserController extends Controller
{

    /**
     * 用户注册
     * */
    public function mobile_register(Request $request)
    {
        $mobile     = $request->input('mobile');
        $code       = $request->input('code');
        $nickName   = $request->input('nickName');

        //检查参数
        $params     = [
            'mobile'    => [$mobile,"缺少手机号",4001],
            'name'      => [$nickName,"缺少昵称",4002],
            'code'      => [$code,'缺少验证码',4003]
        ];

        $checkRes   = $this->check_params($params);

        if($checkRes->status == false)
        {
            return apiData()->send($checkRes->code,$checkRes->message);
        }


        //检查验证妈
        $mobileMessage  = new MobileMassege();
        $result         = $mobileMessage->check_valid_code($mobile,$code);
        if($result == false)
        {
            return apiData()->send(4005,$mobileMessage->error);
        }

        //检查用户是会否注册过
        $userModel  = new UserModel();
        if($userModel->check_exists_user_by_mobile($mobile))
        {
            return apiData()->send(4007,'改号码已经注册过了');
        }

        $userModel->register($mobile,$nickName);
        $userInfo   = $userModel->get_user_info($userModel->id);


        return apiData()
            ->set_data('userInfo',$userInfo)
            ->send(200,'注册成功');
    }


    /**
     * 获得用户信息
     * */
    public function get_user_info(Request $request)
    {
        $userId     = $request->input('userId');

        $userModel  = new UserModel();

        $userInfo   = $userModel->get_user_info($userId);

        return apiData()->set_data('userInfo',$userInfo)->send(200,'success');

    }



    /**
     * 更新用户信息
     * */
    public function update_user_info(Request $request)
    {

        $data   = $request->all();
        return $data;

    }

}
