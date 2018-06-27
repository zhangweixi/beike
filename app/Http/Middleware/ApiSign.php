<?php

namespace App\Http\Middleware;

use Closure;

class ApiSign
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

        $sign       = $request->input('sign');
        $mustSign   = $request->input('mustSign',1);

        $except     = ['sign'];

        if($mustSign == 1)
        {
            $secret = "lanqi888";
            $data   =  $request->except($except);
            if(count($data) > 0)
            {
                ksort($data);

                $str    = "";
                foreach($data as $v)
                {
                    $str .= $v;
                }

                $newSign=  md5(md5($str.$secret));

                if($newSign != $sign)
                {
                    return apiData()->send(4004,"签名错误");
                }
            }
        }
        return $next($request);
    }
}
