<?php
/**
 * 获得默认头像
 * @param $head string 头像
 * @return string
 * */
function get_default_head($head)
{
    return $head ? config('app.filehost')."/".$head : url("beike/images/default/head.png");
}

/**
 * 生日转年龄
 * */
function birthday_to_age($birthday)
{
    if(empty($birthday))
    {
        return 0;
    }

    $year1  =  substr($birthday,0,4);
    $yearn  =  date('Y');
    $age    =  $yearn - $year1;
    if($age < 0 || $age > 100)
    {
        return 0;
    }

    return $age;

}


function credit_to_text($credit)
{
    switch ($credit)
    {
        case 0:     $text = "不限";break;
        case 60:    $text = "中等以上";break;
        case 80:    $text = "良好以上";break;
        case 90:    $text = "优秀以上";break;
    }
    return $text;
}


function text_to_credit($text)
{
    switch ($text)
    {
        case "不限":       $credit = 0;break;
        case "中等以上":    $credit = 60;break;
        case "良好以上":    $credit = 80;break;
        case "优秀以上":    $credit = 90;break;
        default: $credit = 0;
    }
    return $credit;
}