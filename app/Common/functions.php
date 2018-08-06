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
        $logfile =  public_path()."/logs/".$file;
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


/*获取当前url地址*/
function get_current_url(){
    return "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
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


/**
 * 十六进制转换成ascll格式的字符串
 */
function strToAscll($str)
{
    $len    = strlen($str);
    $temp   = "";

    for($i = 0;$i<$len;$i=$i+2)
    {

        $temp .= chr(hexdec(substr($str,$i,2)));   //十六进制转换成ASCLL

    }
    return $temp;
}



/**
 * 驼峰转化成下划线
 * @param $str string:array
 * */
function tofeng_to_line($str)
{
    if(gettype($str) == 'array')
    {
        foreach($str as $key=>$v)
        {
            $str[$key] = tofeng_to_line($v);
        }
        return $str;

    }else{

        $str = preg_replace_callback("/[A-Z]/", function($ma){return "_".strtolower($ma[0]);}, $str);
        return $str;
    }
}


/**
 * 创建token
 * */
function create_token($userId)
{
    $prev   = 10000000 + $userId;
    $token  = $prev.md5(create_member_number());
    return base64_encode($token);
}

class ParseToken{

    public $userId;
    public $token;
}

/**
 * 解析token
 * */
function parse_token(\Illuminate\Http\Request $request)
{
    $token  = $request->header('token');
    if($token)
    {

        $token  = base64_decode($token);
        $userId = substr($token,0,8);
        $userId = $userId - 10000000;
        $tokenInfo = new ParseToken();
        $tokenInfo->token   = $token;
        $tokenInfo->userId  = $userId;
        return $tokenInfo;
    }

    return false;
}



/**
 * 反转十六进制
 * @param $hex string  十六进制字符串
 * @return string:boolean
 * */
function reverse_hex($hex)
{
    if( strlen($hex) % 2 != 0)
    {
        return false;
    }

    $hexArr = str_split($hex,2);
    $hexArr = array_reverse($hexArr);//将低位在前高位在后转换成 高位在前低位在后
    $hex    = implode("",$hexArr);
    return $hex;
}


/*
 * 创建随机图谱
 * */
function create_round_array($y,$x)
{

    $arr    = [];
    for($i=0;$i<$y;$i++)
    {
        for($j=0;$j<$x;$j++)
        {

            $arr[$i][$j] = rand(0,10);
        }
    }
    return $arr;
}

/*
 * 获得毫秒
 * */
function getMillisecond()
{
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
}



/**
 * 十六进制转十进制 高位在前 低位在后
 * @param $hex string 十六进制字符串
 * @return string|boolean
 * */
function hexToInt($hex)
{
    //反转16进制
    $hex    = reverse_hex($hex);
    if($hex == false)
    {
        return $hex;
    }
    return unpack("l", pack("l", hexdec($hex)))[1];
}

/**
 * @param $hex string 十六进制字符串
 * @return string
 * */
function HexToFloat($hex){

    //$hex = "0080a43e"; //0.3212890625 参考数据

    $hex = reverse_hex($hex);

    if($hex == false)
    {
        return $hex;
    }

    return unpack("f", pack("l", hexdec($hex)))[1];
}


//经纬度坐标转换
function gps_to_gps($num)
{
    if(empty($num))
    {
        return 0;
    }

    bcscale (8);
    $num = explode(".",$num);
    if(count($num) == 1)
    {
        $num[1]=0;
    }

    $int    = (int)bcdiv($num[0],100);
    $fint   = (int)bcmod($num[0],100);
    $fnum   = $fint.".".$num[1];
    $fnum   = bcdiv($fnum,60);

    return bcadd($int,$fnum);
}


/**
 * 判断某年的某月有多少天
 * @param $year string
 * @param $month string
 * @return days Integer
 */
function daysInmonth($year='',$month='')
{
    if (empty($year)) $year = date('Y');
    if (empty($month)) $month = date('m');
    $day = '01';

    //检测日期是否合法
    if (!checkdate($month, $day, $year)) return '输入的时间有误';

    //获取当年当月第一天的时间戳(时,分,秒,月,日,年)
    $timestamp = mktime(0, 0, 0, $month, $day, $year);
    $result = date('t', $timestamp);
    return $result;
}

/*填充数据的长度*/
function full_str_length($str,$length,$fullstr)
{
    $strleng = mb_strlen($str);

    for($i=$strleng; $i<$length; $i++)
    {
        $str = $fullstr.$str;
    }
    return $str;
}

/* *
 * 二维数组根据字段进行排序
 * @params array $array 需要排序的数组
 * @params string $field 排序的字段
 * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
 * @return Array
 */
function arraySequence($array, $field, $sort = 'SORT_DESC')
{
    $arrSort = array();
    foreach ($array as $uniqid => $row) {
        foreach ($row as $key => $value) {
            $arrSort[$key][$uniqid] = $value;
        }
    }
    array_multisort($arrSort[$field], constant($sort), $array);
    return $array;
}


?>