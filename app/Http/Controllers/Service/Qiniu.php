<?php
namespace App\Http\Controllers\Service;
use App\Http\Controllers\Controller;
use Qiniu\Auth;



class Qiniu extends Controller{

    public $qiniu;
    private $token;

    public function __construct()
    {
        $this->qiniu    = new Auth(config('qiniu.accessKey'),config('qiniu.secretKey'));
        $this->token    = $this->qiniu->uploadToken(config('qiniu.bucketName'));
    }

    public function get_token()
    {
        return apiData()->set_data('token',$this->token)->send();
    }





}