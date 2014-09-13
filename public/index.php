<?php
use IpnForwarder\App;

ini_set('max_execution_time', 0); /* Do not abort with timeouts */
ini_set('display_errors', 'Off'); /* Do not display any errors to anyone */
date_default_timezone_set('UTC');

$composer = require __DIR__ . '/../vendor/autoload.php';

$app = new App('mytest', __DIR__.'/..');
App::setInstance($app);
require __DIR__ . '/../bindings.php';

$app->boot();

loadListenersFromFile(app()->path . '/config/listeners.php');

$app->run();

$app->shutdown();