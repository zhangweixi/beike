<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        DB::listen(function () {
            $log = 10;
            if ($log) {

                $args = func_get_args();
                $args = $args[0];
                $sql = $args->sql;
                $param = $args->bindings;
                $sqls = explode('?', $sql);
                $sql = "";
                $i = 0;
                $sqlsLength = count($sqls);
                foreach ($sqls as $key => $v) {
                    $sql .= $v;
                    if ($i < $sqlsLength - 1) {
                        $sql .= "'" . $param[$i] . "'";
                    }
                    $i++;
                }
                mylogger($sql);
            }

        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
