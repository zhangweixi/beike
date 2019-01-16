<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;


class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {

        mylogger($_SERVER['REQUEST_URI']);


        //给管理员报错
        if(!isset($GLOBALS['SaveError']) && config('app.env') == 'production' )
        {
            $message    = $exception->getMessage();
            $code       = $exception->getCode();
            $line       = $exception->getLine();
            $file       = $exception->getFile();
            $msg        = $message."\n【".$line."】".$file;
            $traice     = $exception->getTraceAsString();
            $GLOBALS['SaveError'] = true;

            mylogger($message."\n".$file."\n".$line."\n".$traice);
            //jpush_content("标题",$msg,9000,1,1,[]);


        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {

        return parent::render($request, $exception);
    }
}
