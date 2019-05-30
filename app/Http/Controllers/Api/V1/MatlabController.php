<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;


class MatlabController extends Controller
{

    private $cycleTime = 60;//单位秒
    public function test()
    {
        $users  = [
            '广胜水泥制品厂',
            '张维喜',
            '聚农优品星河店',
            '李永谦快点快点开的快点快点开'
        ];
        $savefile   = public_path("www.jpg");
        $qrimg      = "erweima.png";
        $type       = "share";
        $success    = false;
        if($type == 'share')
        {
            $bgImg      = public_path("share-bg.jpg");
        }else{
            if($success)
            {
                $bgImg      = public_path("detail-bg1.jpg");
            }else{
                $bgImg      = public_path("detail-bg.jpg");
            }

            
        }


        $name = "张维喜";
        $this->write_imgs($users,$name,$savefile,$qrimg,4,$type,$bgImg,$success);
    }

    public function write_imgs($texts,$name,$savefile,$qrimg,$num,$type,$bgImg,$success= false)
    {
        $userfontfile = public_path("PingFang-SC-Regular.ttf");
        $fontfile = public_path("zaozigongfangxingheixiti.ttf");

        $size   = 42;
        $angle = 0;
        if($type == 'share')
        {
            $margintop = 0;

        }else{
            $margintop = 90;
        }
        $erweima = imagecreatefrompng($qrimg);

        $image  = imagecreatefromjpeg($bgImg);

        //文字
        //$color = imagecolorallocate($image,92,99,132);
        $color  = imagecolorallocate($image,64,68,85);
        $yellow = imagecolorallocate($image,252,145,83);
        $textindent = 3;
        imageantialias($image,true);
        imagesavealpha($image , true);//不能去掉，否则二维码变黑

        if($success == false)
        {
            $numtop = $margintop + 275;
            $num = "还差{$num}人，全团可领取暑期红包";

            //写数字
            $wordsSpace = 3;
            //$numberText = $this->str_to_words($num);
            $numberText = preg_split('//u',$num);
            $uii = [];
            foreach($numberText as $xx)
            {
                if(trim($xx) == '')
                    continue;
                array_push($uii, $xx);

            }
            $numberText = $uii;

            $numberTextInfo = $this->get_words_width($numberText,$size,$fontfile,$wordsSpace);//['width'=>20,'words'=>[]]
            $baseLeft   = (750 - $numberTextInfo['width'])/2;
            foreach($numberTextInfo['words'] as $key => $word)
            {
                $textcolor = $key == 3 ? $yellow : $color;
                imagettftext($image, $size, $angle, $baseLeft+$word['left'], $numtop, $textcolor, $fontfile, $word['text']);
            }


            //写名字
            $name = $name ."邀您参团享福利";
            $nameText   = $this->str_to_words($name);
            $nameText   = $this->get_words_width($nameText,$size,$fontfile,$wordsSpace);
            $baseLeft   = (750-$nameText['width'])/2;

            $nametop    = $margintop + 190;
            $alllength  = count($nameText['words']);

            foreach($nameText['words'] as $key => $word)
            {
                $textcolor = $key > $alllength-8 ? $color:$yellow ;
                imagettftext($image,$size,$angle,$baseLeft + $word['left'],$nametop,$textcolor,$fontfile,$word['text']);
            }
        }





        //写用户名字列表
        $users  = [
            ['name'=>'','x'=>160,'y'=>520,'w'=>300],
            ['name'=>'','x'=>180,'y'=>445,'w'=>290,'w'=>300],
            ['name'=>'','x'=>130,'y'=>395,'w'=>310],
            ['name'=>'','x'=>150,'y'=>330,'w'=>280],
        ];

        foreach($texts as $k=>$n)
        {
            $users[$k]['name'] = $n;
        }

        $top = 300;
        $size = 18;
        $color = imagecolorallocate($image,255,255,255);
        foreach($users as $user)
        {
            $t = $user['name'];
            if(empty($t)){
                continue;
            }

            //$t = "李永谦快点快点开的快点快点开";
            if(mb_strlen($t)>10){
                $t = mb_substr($t,0,9)."...";
            }

            $info = imagettfbbox($size,$angle,$fontfile,$t);
            //$height = 0-$info[5];
            $width  = $info[4];

            $y = $top + $user['y']-3;
            $y = $y + $margintop;
            $x = $user['x'] + ($user['w']-$width)/2-20;

            //$user['name']   = "聚农优品星河店";

            imagettftext($image,$size,$angle,$x,$y,$color,$userfontfile,$t);
        }

        //画二维码
        if($type == 'share')
        {
            list($qrwidth,$qrheight) = getimagesize($qrimg);
            $padding = 36;
            imagecopyresized($image,$erweima,175,975,$padding,$padding,119,119,$qrwidth-$padding,$qrheight-$padding);
        }


        imagejpeg($image, $savefile);
    }


    /*计算位置宽度*/
    public function get_words_width($words,$size,$fontfile,$wordsSpace)
    {
        $left = 0;

        foreach($words as $k=>$text)
        {
            $info = imagettfbbox($size,0,$fontfile,$text);

            $left = $left + $wordsSpace;

            $words[$k] = [
                'text'  => $text,
                'width' => $info[4],
                'left'  => $left
            ];

            $left = $left + $info[4];
            $left = $left + $wordsSpace;
        }

        return ['width'=>$left,'words'=>$words];
    }


    public function str_to_words($str)
    {
        $len = mb_strlen($str);
        $arrs = preg_split('/(?<!^)(?!$)/u', $str );
        $finished = true;
        $prev       = "";
        $texts      =[];
        $previsletter = false;

        foreach($arrs as $key=>$text){
            
            $text = trim($text);
            
            $type = gettype($text);


            if(preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)/', $text))
            {
                
                $prev .= $text;
                $finished = true;
                $previsletter = true;
                
            }elseif($text != "" && $type!= "NULL"){

                if($previsletter == true)
                {
                    array_push($texts,$prev);
                    $prev = "";
                }
                $prev .= $text;
                $finished = true;
                $previsletter = false;
                
                
            }elseif($text == "" || $type!= "NULL"){
                
                $finished = true;
                $previsletter = false;
                
            }
            
            
        
            if($key == $len-1)
            {
                $finished = true;
            }
            
            if($finished)
            {
                try{
                     
                     array_push($texts,$prev);
                     
                }catch(Exception $e){
                    
                    
                }
               
                $prev = "";
                $finished = false;
            }
        }
        
        return $texts;

    }

     public function create_img($text,$fontfile,$size,$savefile)
    {
        
        $angle = 0;
        $static = dirname(dirname(dirname(dirname(__FILE__))))."/public/static/";
        
        
        $info = imagettfbbox($size,$angle,$fontfile,$text);
        $height = 0-$info[5];
        $width  = $info[4];

        $image  = imagecreatetruecolor($width, $height*1.2);
        $bg     = imagecolorallocatealpha($image , 0 , 0 , 0 , 127);//
        imagealphablending($image , false);
        imagefill($image , 0 , 0 , $bg);//填充   

        //$image = imagecreatefrompng("moban.png");
        $zhibg = imagecolorallocatealpha($image, 255, 255, 255,255);
                 imagecolortransparent($image,$zhibg);

        //文字
        $color = imagecolorallocate($image,42,46,73);

        imagettftext($image, $size, $angle, 0, $size+5, $color, $fontfile, $text);
        imagesavealpha($image , true);
        //$file = $static."images/diditongqin/".$companyId.".png";
        imagepng($image, $savefile);
        //return "/DidiQuanzhou/public/static/images/diditongqin/".$companyId.".png";
    }


    /*
     * 处理数据
     * */
    public function handle_data(Request $request)
    {
        $matchId    = $request->input('matchId');
        $userId     = $request->input('userId');


        //1.提取数据
        $data   = $this->get_data($matchId,$userId);
        return $data;
        //2.发送处理


        //3.获取结果并存储

        //4.标记已处理的数据
    }

    /*
     * 获得数据
     * @param $matchId integer 比赛场次ID
     * @param $userId  integer 用户ID
     * */
    public function get_data($matchId,$userId)
    {
        //这个时间获得的数据是两个数据
        //将一定时间内的数据提取出来 生成json文件

        $data  = [
            'ax'    => [],
            'ay'    => [],
            'az'    => [],
            'gx'    => [],
            'gy'    => [],
            'gz'    => [],
            'lat'   => [],
            'lon'   => [],
            'tis'   => []
        ];

        //获取gps数据
        $table = "user_".$userId."_gps";
        DB::connection('matchdata')
            ->table($table)
            ->where('match_id',$matchId)
            ->orderBy('id','asc')
            ->chunk(100,function($gpsList)use(&$data)
            {
                foreach($gpsList as $gps)
                {
                    array_push($data['lon'],$gps->longitude);
                    array_push($data['lat'],$gps->latitude);
                }
            });

        $table  = "user_".$userId."_sensor";
        DB::connection('matchdata')
            ->table($table)
            ->where('match_id',$matchId)
            ->where('timestamp',">",0)
            ->select('x','y','z','type','timestamp')
            ->orderBy('id')
            ->chunk(1000,function($sensors) use(&$data)
            {
                foreach($sensors as $sensor)
                {
                    if($sensor->type == 'A')
                    {
                        array_push($data['ax'],$sensor->x);
                        array_push($data['ay'],$sensor->y);
                        array_push($data['az'],$sensor->z);
                        array_push($data['tis'],$sensor->timestamp);

                    }elseif($sensor->type == "G"){

                        array_push($data['gx'],$sensor->x);
                        array_push($data['gy'],$sensor->y);
                        array_push($data['gz'],$sensor->z);
                        array_push($data['tis'],$sensor->timestamp);
                    }
                }
            });

        return $data;
    }


    /*
     * 调用matlab软件
     * */
    public function call_matlab($data)
    {

        //调用python文件
        $pythonExe  = "D:\Program Files (x86)\python";//python执行文件
        $pyScript   = "";//python脚本

        $command    = "$pythonExe $pyScript";
        system($command);

    }

    /**
     * 存储计算结果
     * */
    public function save_result($result)
    {


    }

    /**
     * 处理旧的数据
     * */
    public function handle_old_data()
    {

    }


}
