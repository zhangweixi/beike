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
    $yearn  =   date('Y');
    if($year1 > $yearn)
    {
        return 0;
    }

    return $yearn - $year1;
}