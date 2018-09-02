<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use DB;


class UserModel extends Model
{
    protected $table    = "users";

    private $selectColum = [
        'id',
        'name',
        'nick_name',
        'wx_openid',
        'wx_unionid',
        'qq_openid',
        'qq_name',
        'qq_head',
        'head_img',
        'mobile',
        'birthday',
        'sex',
        'height',
        'weight',
        'role1',
        'role2',
        'foot',
        'device_sn',
        'token'
    ];

    /**
     * 注册用户信息
     * */
    public function register($mobile,$nickName,$userInfo = [])
    {

        $this->mobile       = $mobile;
        $this->nick_name    = $nickName;
        $this->created_at   = date_time();
        $this->updated_at   = date_time();
        $this->token        = "";
        $this->save($userInfo);

        return $this;
    }

    /**
     * 检查是否存在用户
     * @param $mobile string 用户的手机号
     * */
    public function check_exists_user_by_mobile($mobile)
    {
        $user   = $this->where('mobile',$mobile)->select('id')->first();

        return  $user ? true : false ;
    }

    /**
     * 根据手机号获得用户信息
     * */
    public function get_user_info_by_mobile($mobile)
    {
        $userInfo = $this->where('mobile',$mobile)->select('id')->first();
        if($userInfo)
        {
            return $this->get_user_info($userInfo->id);
        }
        return false;
    }



    /**
     * 根据ID获取用户信息
     * */
    public function get_user_info($id)
    {
        $userInfo = $this->where('id',$id)->select($this->selectColum)->first();
        //$userInfo = $this->where('id',$id)->first();

        $userInfo   = $userInfo ? key_to_tuofeng($userInfo->toArray()) : $userInfo;
        $age = date('Y')-(int)substr($userInfo['birthday'],0,4);
        if($age > 200)
        {
            $age = 0;
        }
        $userInfo['age']        = $age;
        $userInfo['headImg']    = get_default_head($userInfo['headImg']);
        return $userInfo;
    }

    /*
     * 根据openid获取用户信息
     * */
    public function get_user_info_by_openid($openId,$type)
    {
        if($type == 'wx')
        {
            $colum  = "wx_unionid";

        }elseif($type == 'qq') {

            $colum  = "qq_openid";
        }

        $userInfo = $this->where($colum,$openId)->select('id')->first();
        if($userInfo)
        {
            $this->get_user_info($userInfo->id);
        }

        return false;
    }

    /*
     * 修改用户信息
     * */
    public function update_user_info($userId,$userInfo)
    {
        DB::table('users')->where('id',$userId)->update($userInfo);
    }


    /**
     * 刷新token
     * @param $userId   integer 用户ID
     * @return string
     * */
    public function fresh_token($userId)
    {
        $token  = create_token($userId);
        $this->update_user_info($userId,['token'=>$token]);
        return $token;
    }


    /**
     * 用户的整体数据
     * @param $userId int 用户ID
     * */
    public function user_global_ability($userId)
    {
        $colums         = ["shoot","pass","strength","dribble","defense","run"];
        $ability        = DB::table('user_global_ability')->select($colums)->where('user_id',$userId)->first();

        if($ability == null)
        {
            $ability        = new \stdClass();
            $ability->shoot     = 0;
            $ability->pass      = 0;
            $ability->strength  = 0;
            $ability->dribble   = 0;
            $ability->defense   = 0;
            $ability->run       = 0;
        }

        return $ability;
    }






}
