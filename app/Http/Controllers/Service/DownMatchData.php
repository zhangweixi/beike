<?php

set_time_limit (24 * 60 * 60);

//file_put_contents("log.txt",implode(",",$argv)."\n",FILE_APPEND);



if($argc < 3){
    die('错误');
}

$fileUrl   = $argv[1];
$fileNew   = $argv[2];
$begin = time();

//file_put_contents($fileNew,file_get_contents($fileUrl));
if(1){

    $file = fopen($fileUrl, "rb");

    if ($file)
    {
        $newf = fopen ($fileNew, "wb");

        if ($newf){

            while(!feof($file)) {

                fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
            }

            fclose($newf);
        }

        fclose($file);
    }
}


$time = time()-$begin;
file_put_contents("log.txt",$time."-".basename($fileNew)."\n",FILE_APPEND);

