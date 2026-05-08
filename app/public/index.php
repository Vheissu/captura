<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Prevent PHP 8.5 vendor deprecations from corrupting JSON and image responses
// before Laravel's exception handler is booted.
error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
