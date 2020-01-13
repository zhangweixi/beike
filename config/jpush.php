<?php
return [
    "appKey"    => env('JPUSH_KEY',''),
    "secret"    => env('JPUSH_SECRET',''),
    "logFile"	=> env('JPUSH_LOG_FILE',public_path("logs/jpush.log")),
    'isProduction'=> env('APP_ENV','dev') == "dev" ? false : true,
];