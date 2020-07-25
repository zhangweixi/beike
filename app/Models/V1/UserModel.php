<?php

namespace App\Models\V1;

use App\Common\Geohash;
use App\Models\Base\BaseUserAbilityModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserModel extends Model
{
    protected $table    = "users";
    protected $guarded  = [];
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
        'token',
        'football_team',
        'credit',
        'star_id',
        'password',
    ];

    /**
     * 注册用户信息
     * */
    public function register($mobile,$nickName,$userInfo = [])
    {

        //$this->mobile       = $mobile;
        //$this->nick_name    = $nickName;
        //$this->created_at   = date_time();
        //$this->updated_at   = date_time();
        //$this->token        = "";

        $userInfo['mobile']     = $mobile;
        $userInfo['nick_name']  = $nickName;
        $userInfo['created_at'] = date_time();
        $userInfo['updated_at'] = date_time();
        $userInfo['token']      = "";

        $this->create($userInfo);

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

        $userInfo   = $userInfo ? key_to_tuofeng($userInfo->toArray()) : $userInfo;
        $age = date('Y')-(int)substr($userInfo['birthday'],0,4);
        if($age > 200)
        {
            $age = 0;
        }
        $userInfo['age']        = $age;
        $userInfo['headImg']    = get_default_head($userInfo['headImg']);

        $ability = BaseUserAbilityModel::where('user_id',$id)->select('grade')->first();
        $userInfo['grade'] = 0;
        if($ability){
            $userInfo['grade']  = $ability->grade;
        }
        return $userInfo;
    }

    /*
     * 根据openid获取用户信息
     * */
    public function get_user_info_by_openid($openId,$type)
    {
        if($type == 'wx')
        {
            $column  = "wx_unionid";

        }elseif($type == 'qq') {

            $column  = "qq_openid";

        } elseif($type == 'ios') {

            $column = 'apple_id';
        }

        $userInfo = $this->where($column,$openId)->select('id')->first();
        if($userInfo)
        {
            return $this->get_user_info($userInfo->id);
        }

        return false;
    }


    public function get_user_info_by_apple_id($appleId) {
        $userInfo = self::where('apple_id', $appleId)->first();
        if($userInfo) {
            return $this->get_user_info($userInfo->id);
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
     * 用户的整体数据
     * @param $userId int 用户ID
     * @return Object
     * */
    public function user_global_ability($userId)
    {
        $ability        = DB::table('user_global_ability')->where('user_id',$userId)->first();
        if(!$ability) {

            $defaultAbility = DB::table('user_global_ability')->first();
            $ability = new \stdClass();
            foreach($defaultAbility as $key => $v) {
                $ability->$key = 0;
            }
        }
        return $ability;
    }



    /**
     * 根据经纬度来查找附近的用户
     * @param $lat double 纬度
     * @param $lon double 经度
     * @param $strlen integer 字符串长度
     * @return array
     * */
    public function get_user_ids_by_geohash($lat,$lon,$strlen)
    {
        $geo        = new Geohash();
        $geohash    = $geo->encode($lat,$lon);
        $geohash    = substr($geohash,0,$strlen);
        $areas      = $geo->neighbors($geohash);
        $areas['middle']    = substr($geohash,0,$strlen);

        $users      = [];
        foreach($areas as $area)
        {
            $ids    = $this->where('geohash','like',$area."%")->pluck('id')->toArray();

            if(count($ids) > 0)
            {
                $users  = array_merge($users,$ids);
            }
        }
        return $users;
    }

    /**
     * @param $rankColumn string 排序的字段
     * @param $userId int 用户,如果有这个字段，将只获取好友，否则获取系统所有成员
     * @return array
     */
    static function rank($rankColumn,$userId=0) {
        $db = DB::table('user_global_ability as a')
            ->leftJoin('users as b','b.id','=','a.user_id');
        if($userId) {
            $db->leftJoin('friend as d','d.friend_user_id','=','a.user_id')->where('d.user_id', $userId)->orWhere('a.user_id', $userId);
        }

        $friends = $db->select('b.nick_name','b.id','b.head_img','b.mobile','b.country','b.province','b.city','a.'.$rankColumn.' as grade')
            ->orderBy("a.".$rankColumn,'desc')
            ->paginate(20);
        foreach($friends as $friend) {
            $friend->head_img = get_default_head($friend->head_img);
            $friend->sn = substr($friend->mobile,2,8)*2;
            $friend->country = $friend->country ?: '';
            $friend->province = $friend->province ?: '';
            $friend->city = $friend->city ?: '';
        }
        return $friends;
    }

    static function userRank($userId, $rankColumn,$onlyFriend=0) {
        $grade = BaseUserAbilityModel::where('user_id', $userId)->value($rankColumn);
        if(!$grade) {
            return 0;
        }

        $db = DB::table('user_global_ability as a');
        if($onlyFriend) {
            $db->leftJoin('friend as d','d.friend_user_id','=','a.user_id')->where('d.user_id', $userId)->orWhere('a.user_id', $userId);
        }
        $rank = $db->where('a.'.$rankColumn,'<',$grade)->count();
        return $rank + 1;
    }

}
