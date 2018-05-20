<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use DB;


class UserModel extends Model
{
    protected $table    = "users";


    /**
     * 注册用户信息
     * */
    public function register($mobile,$nickName,$userInfo = [])
    {

        $this->mobile       = $mobile;
        $this->nick_name    = $nickName;
        $this->created_at   = date_time();
        $this->updated_at   = date_time();
        $this->save($userInfo);

        return $this;
    }

    /**
     * 检查是否存在用户
     * @param $mobile string 用户的手机号
     * */
    public function check_exists_user_by_mobile($mobile)
    {
        $user   = $this->where('mobile',$mobile)->first();

        return  $user ? true : false ;
    }

    /**
     * 根据手机号获得用户信息
     * */
    public function get_user_info_by_mobile($mobile)
    {
        $userInfo = $this->where('mobile',$mobile)->get();

        return $userInfo;
    }


    /**
     * 根据ID获取用户信息
     * */
    public function get_user_info($id)
    {
        $userInfo = $this->where('id',$id)->first();

        $userInfo   = $userInfo ? key_to_tuofeng($userInfo->toArray()) : $userInfo;

        return $userInfo;
    }



    public function update_user_info($userId,$userInfo)
    {

        $this->id   = $userId;
        $this->update($userInfo);

    }





}
