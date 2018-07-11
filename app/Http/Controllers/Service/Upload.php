<?php
namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;



class Upload extends Controller{

    public function upload(Request $request)
    {
        $filename = Storage::disk('web')->putFile('speed',$request->file('file'));
        $filepath = public_path('/uploads/'.$filename);

        return apiData()->set_data('filepath',$filepath)->send();
    }
}