<?php

namespace App\Http\Middleware;
use DB;
use Closure;
class SaveApiData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $url = $_SERVER['REQUEST_URI'];
        $surl = substr($url,8);

        $excepUrl   = [
            'homelist',
            'home',
            'mediaPlayLog',
            'memberOnlineLog'
        ];

        if(!in_array($surl,$excepUrl))
        {
            $data   = $request->all();
            $data['authorization']  = $request->header('Authorization') ? md5($request->header('Authorization')): '';
            $data   = json_encode($data);
            $data   = [
                'data'          =>$data,
                'url'           =>$url,
                'created_at'    =>date_time(),
            ];
            DB::table('api_input_data')->insert($data);
        }
        return $next($request);
    }
}