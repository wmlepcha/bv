<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Hide startup warnings from the response body
|--------------------------------------------------------------------------
|
| When POST Content-Length exceeds post_max_size, PHP emits a Request
| Startup warning. If display_errors is on, that warning is prepended to
| the response and breaks Inertia JSON parsing. Log instead of display.
|
*/
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
