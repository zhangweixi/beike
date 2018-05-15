<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;
use Intervention\Image\Facades\Image;
use App\Common\ApiData;
use SimpleSoftwareIO\QrCode\BaconQrCodeGenerator;

/*
 * 打印日志
 */
if(!function_exists('mylogger')){

    function mylogger($content,$file = 'my.txt'){

        if(is_array($content)){
            $content = digui($content);
        }

        $logContent =  "\r\n".date('Y-m-d H:i:s')."\r\n";
        $logContent .= "------------------------ BEGIN -----------------------------"."\r\n";
        $logContent .= $content."\r\n";
        $logContent .= "-------------------------  END  ----------------------------"."\r\n";
        $logfile =  public_path()."/".$file;
        $max_size = 50000000;
        if(file_exists($logfile) and (abs(filesize($logfile)) > $max_size)){
            unlink($logfile);
        }
        file_put_contents($logfile,$logContent , FILE_APPEND);
    }
}


/*
 * 递归数组获得字符串格式
 * */
function digui($array){
    $string = "";
    $obj	= new stdClass();
    if(gettype($array) == gettype($obj)){
        $array	= object_to_array($array);
    }
    foreach ($array as $key => $value) {
        if(is_array($value)) {
            $string .=digui($value);
        }else{
            $string .= $key."--------->".$value."\r\n";
        }
    }
    return $string;
}



/**
 * 随机数字
 * @param $n 随机数的位数
 * @return string
 */
function randStr($n){
    $s = '';
    $str = "01238856789"; // 输出字符集
    $len = strlen($str) - 1;
    for ($i = 0; $i < $n; $i++) {
        $s .= $str[rand(0, $len)];
    }
    return $s;
}


/*
 * 当前详细时间
 * */
function date_time($time = ''){
    $time = $time?$time:time();
    return date('Y-m-d H:i:s',$time);
}

/*
 * 当前日期
 * */
function current_date($time=0){
    $time = $time?$time:time();
    return date('Y-m-d',$time);
}

//微信表情编码
function emoji_text_encode($str){
    if(!is_string($str))return $str;
    if(!$str || $str=='undefined')return '';

    $text = json_encode($str); //暴露出unicode
    $text = preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i",function($str){
        return addslashes($str[0]);
    },$text); //将emoji的unicode留下，其他不动，这里的正则比原答案增加了d，因为我发现我很多emoji实际上是\ud开头的，反而暂时没发现有\ue开头。
    return json_decode($text);
}


//微信表情解码
function emoji_text_decode($str){
    $text = json_encode($str); //暴露出unicode
    $text = preg_replace_callback('/\\\\\\\\/i',function($str){
        return '\\';
    },$text); //将两条斜杠变成一条，其他不动
    return json_decode($text);
}




if(function_exists('create_member_number')){
    throw new Exception('function create_member_number exists');
}else{
    /*创建会员编号*/
    function create_member_number($length = 6){
        //return date('YmdHi', time()).uniqid();
        // 密码字符集，可任意添加你需要的字符
        $chars = "0123456789";
        $password = date('YmdHi', time());
        for ( $i = 0; $i < $length; $i++ )
        {
            // 这里提供两种字符获取方式
            // 第一种是使用 substr 截取$chars中的任意一位字符；
            // 第二种是取字符数组 $chars 的任意元素
            // $password .= substr($chars, mt_rand(0, strlen($chars) – 1), 1);
            $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $password;
    }
}


if(function_exists('create_order_number')){
    throw new Exception('function create_order_number 已经存在');
}else{
    /*创建订单编号*/
    function create_order_number(){
        return substr(date('YmdHis',time()).uniqid(),0,20);
    }
}


if(function_exists('is_wexin')){
    throw new Exception('function is_weixin exists');
}
/*判断是不是微信环境*/
function is_weixin(){
    if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
        return true;
    }
    return false;
}

function is_ios()
{
    if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
        return true;
    }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
        return false;
    }else{
        return false;
    }
}

if(function_exists('get_weixin_code')){
    throw new Exception('function get_weixin_code is exists');
}else{
    //获取微信CODE
    function get_weixin_code(){

    }
}


/*获取当前url地址*/
function get_current_url(){
    return "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
}


//转换用户的头像
function change_member_head_img($headImg){
    if((!preg_match('/http/',$headImg) && $headImg)){
        $headImg = env('ADMIN_HOST').$headImg;
    }elseif(empty($headImg)){
        $headImg = env('ADMIN_HOST').'/images/default-images/default-head-img.jpg';
    }
    return  $headImg;
}


/*
 * 将数组的key转换成驼峰法的形式
 * @param arr 要转换的数组
 * */
function key_to_tuofeng( $arr){
    if(is_object($arr)){
        $arr = object_to_array($arr);
    }

    foreach($arr as $key => $v){
        $key1 = $key;
        preg_match_all('/_/',$key,$rule);
        $len = count($rule[0]);


        for($i = 0;$i<$len;$i++){
            $p = strpos($key1,'_');
            $key1   = substr_replace($key1,"-",$p,1);
            $begin  = substr($key1,0,$p);
            $middle = substr($key1,$p+1,1);
            $middle = strtoupper($middle);
            $end    = substr($key1,$p+2);
            $key1   = $begin.$middle.$end;
        }


        if(is_array($v)){
            $v = key_to_tuofeng($v);
        }
        unset($arr[$key]);
        $arr[$key1]  = $v;
    }
    return $arr;
}


/*
 * 获取IP地址
 * */
function get_real_ip(){
    if (isset($_SERVER)) {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $realip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $realip = $_SERVER["REMOTE_ADDR"];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = getenv("REMOTE_ADDR");
        }
    }
    return $realip;
}

//判断是否是windows系统
function is_windows(){
    return strtoupper(substr(PHP_OS,0,3))==='WIN'?true:false;
}



function object_to_array($array) {
    if(is_object($array)) {
        $array = (array)$array;
    } if(is_array($array)) {
        foreach($array as $key=>$value) {
            $array[$key] = object_to_array($value);
        }
    }
    return $array;
}



//获得用户的手机号码
function change_member_mobile_to_city($userId=0){
    $host = env('ADMIN_HOST');
    if($host != "http://wx.laohoulundao.com"){
        return true;
    }

    //$host = "http://test1.wx.laohoulundao.com";
    $url = $host."/Tools/CreateData/methodPort?method=get_member_city&userId=".$userId;
    $ch = curl_init($url);
    curl_setopt($ch,CURLOPT_NOSIGNAL,true);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_TIMEOUT,1);//100MS
    curl_exec($ch);
    curl_close($ch);
}


//按值删除数组中的元素
function array_unset_value(&$arr,$value,$all=false)
{
    $i=0;
    foreach($arr as $key=>$v)
    {
        if($v == $value)
        {
            array_splice($arr,$i,1);
            if($all == false)
            {
                break;
            }
        }
        $i++;
    }
}
//====================表日志begin================
/**
 * 记录表的操作日志
 * @param string $tableName 表明
 * @param integer $key 标的主键ID
 * @param integer $userId 操作表的用户ID
 * @param string|array $content 内容
 * @param string $note 备注信息
 * */
function table_log($tableName,$key,$content,$userId=0,$note="")
{
    if(!is_array($content))
    {
        $content = [$content];
    }

    if($userId == 0)
    {
        $manager	= session('manageUser');
        $userId		= $manager->UserID;
    }
    $content = json_encode($content);
    $time	= date_time();
    $data	= [
        'user_id'		=> $userId,
        'table_name'	=> $tableName,
        'table_key'		=> $key,
        'content'		=> $content,
        'ip'			=> get_real_ip(),
        'created_at'	=> $time,
        'updated_at'	=> $time,
        'note'			=> $note,
        'route'			=> $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']
    ];
    DB::table('table_log')->insert($data);
}

function TableLog(){
    return $tableLog	= new TableLog();
}

class TableLog{
    public $userId	= 0;
    public $content	= '';
    public $note	= '';
    public $tableName='';
    public $key		= 0;

    public function table($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * 添加内容
     * @param Array|string $content 更改内容
     * @return TableLog
     * */
    public function content($content)
    {
        $this->content = $content;
        return $this;
    }

    public function key($key)
    {
        $this->key	= $key;
        return $this;
    }
    public function note($note)
    {
        $this->note	= $note;
        return $this;
    }

    public function user($userId)
    {
        $this->userId	= $userId;
        return $this;
    }

    public function save()
    {
        table_log($this->tableName,$this->key,$this->content,$this->userId,$this->note);
    }
}
//====================表日志 end================




/**
 * 压缩图片尺寸和质量
 * @param $path 要获取所有路径的图片
 * @param $maxwidth 允许最大的宽度
 * @param null $savePath 要保存的路经默认为覆盖
 * @param int $quality 图片的质量
 */
function change_img_size($path, $maxwidth, $savePath = null, $quality = 20)
{
    $data = getfiles($path);
    foreach ($data as $k => $v) {
        $pos = substr($v, strrpos($v, '.') + 1);

        if ($pos == 'png' || $pos == 'jpg' || $pos == 'jpeg') {
            $pos = strrpos($v, '/');
            $pos = substr($v, $pos + 1);
            $img = Image::make($v);
            if ($img->width() > $maxwidth) {
                $width   = $img->width();
                $height  = $img->height();
                $n       = $width / $maxwidth;
                $newPath = $savePath ? public_path() . '/' . $savePath . "/$pos" : $v;

                $img->resize($maxwidth, $height / $n)->save($newPath, $quality);
            }
        }
    }
}


/**
 * 获取目录下面的所有的文件
 * @param $path 要遍历的路近
 * @return array
 */
function getfiles($path)
{
    static $tmp = [];
    foreach (scandir($path) as $afile) {
        if ($afile == '.' || $afile == '..')
            continue;

        if (is_dir($path . '/' . $afile)) {
            getfiles($path . '/' . $afile);
        } else {
            array_push($tmp, $path . '/' . $afile);
        }
    }
    return $tmp;
}

/**
 * 获取系统日志表的内容
 * @param string $key  关键字
 * @return boolea
 * */
function system_log($key)
{
    $info = DB::table('system_log')->where('key',$key)->select('value')->first();
    if(empty($info))
    {
        return "";
    }
    else{
        return $info->value;
    }
}


/**
 * 统一的API接口数据
 * */
function apiData(){
    $apiData	= new ApiData();
    return $apiData;
}

/**
 * 获得上一周的星期几的开始时间
 * */
function prev_week_day_time($weekDay)
{
    $time   = time();
    $week 	= date('w',$time);//当前星期几
    $deadLineTime = $weekDay;//上一周的星期几
    if($week < $deadLineTime)//如果当前的星期小于获取的截止日期 比如今日星期2，截止是上周5 那么就将当前日期提升一个周期 然后算时间差
    {
        $week = 7 + $week;
    }
    $days 			= $week - $deadLineTime;
    $prevWeekDay  	= $time - $days*24*60*60;
    return $prevWeekDay;
}


/**
 * 记录到数据库的日志
 * @param string $key 说明关键字
 * @param string $content 日志内容
 * */
function debug_log($key,$content)
{
    if(is_array($content)){
        $content = digui($content);
    }

    $data = [
        'key'	=> $key,
        'value'	=> $content,
        'created_at'=> date_time()
    ];
    DB::table('debug_log')->insert($data);
}

/**
 * 根据key的数组删除元素
 * @param array $arr 要删除的数组
 * @param array $keys 要删除的key
 * */
function unset_array_by_keys(&$arr,array $keys)
{
    foreach($keys as $key)
    {
        unset($arr[$key]);
    }
}

function return_json($code, $message, $data = '') {
    return apiData()
        ->set_data('data',$data)
        ->set_data('error_code',(string)$code)
        ->set_data('error_info',$message)
        ->send_old($code,$message);

}



/**
 * 获得ID地址
 * @param inteter $ip IP地址
 * @return object
 * */
function get_ip_address($ip)
{
    $host = "https://dm-81.data.aliyun.com";
    $path = "/rest/160601/ip/getIpInfo.json";
    $method = "GET";
    $appcode = config('aliyun')['AppCode'];
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    $querys = "ip=".$ip;
    $bodys = "";
    $url = $host . $path . "?" . $querys;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false); //是否返回头部信息
    if (1 == strpos("$".$host, "https://"))
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    $res 	= curl_exec($curl);
    if(empty(trim($res)))
    {
        return false;
    }
    $res 	= \GuzzleHttp\json_decode($res);
    if($res->code != '')
    {
        return false;
    }else{
        return $res->data;
    }

}

//创建多级目录
function mk_dir( $dir ){

    return  is_dir ( $dir ) or mk_dir(dirname( $dir )) and  mkdir ( $dir , 0777);

}

/*
 * 创建二维码
 * */
function create_qrcode($file,$text)
{
    $qrcode = new BaconQrCodeGenerator;
    $qrcode->format('png')->size(300)->generate($text, $file);
}


function get_user_status($isLogin,$status = 0,$endTime = '', $appKey = '')
{

    define('mem_status_unlogin',3);
    define('mem_status_had_end',1);
    define('mem_status_payed',2);
    define('mem_status_unpay',4);
    $memStatus = mem_status_unlogin;
    //判断用户的状态

    if($isLogin)
    {
        if ($status == 1) {
            $nowtime = date('Y-m-d h:i:s', time());

            if (strtotime($nowtime) > strtotime($endTime)) {
                $memStatus = mem_status_had_end;//会员到期
            } else {

                $memStatus = mem_status_payed; //已付费
            }
        } else {
            $memStatus = mem_status_unpay;//未付费
        }
    }

    if($memStatus != mem_status_payed && $appKey)
    {
        $isPay = DB::table('orderprepaidlog')
            ->where('app_key',$appKey)
            ->where('pay_status',1)->count();
        $memStatus = $isPay > 0 ? mem_status_payed : mem_status_unpay;
    }
    return $memStatus;
}


function change_read_number($readNumber)
{
    if($readNumber < 10000)
    {
        return $readNumber."次播放";

    } else {

        return sprintf("%.2f",$readNumber / 10000)."万次播放";
    }

}

?>