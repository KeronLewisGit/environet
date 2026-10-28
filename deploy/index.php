<?php

/*
|--------------------------------------------------------------------------
| Hostinger front controller
|--------------------------------------------------------------------------
| This file lives in public_html/. The Laravel application itself lives in
| public_html/environet-app/ (blocked from the web by its own .htaccess).
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appPath = __DIR__.'/environet-app';

if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
