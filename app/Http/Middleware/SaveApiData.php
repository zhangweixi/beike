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
            'memberOnlineLog',
            'match/uploadMatchData',
            'court/checkSingleGps'
        ];

        if(!in_array($surl,$excepUrl))
        {
            $data   = $request->all();
            $data['token']  = $request->header('token') ? $request->header('token'): '';
            $data['client'] = $request->header('Client-Type').":".$request->header("Client-Version");
            $data   = json_encode($data);
            $data   = [
                'data'          =>$data,
                'url'           =>$url,
                'created_at'    =>date_time(),
            ];
            $tables = ['dev'=>'dev_api_data','production'=>'pro_api_data'];
            $env    = config('app.env');
            DB::connection('DB_LOG')->table($tables[$env])->insert($data);
        }
        return $next($request);
    }
}
