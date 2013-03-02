<?php

error_reporting(-1);

require dirname(__DIR__) . '/config/config.php';
require INCLUDE_DIR . '/controller/Common.php';
require INCLUDE_DIR . '/controller/GetData.php';
require INCLUDE_DIR . '/controller/PostData.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Timeline\PostData;
use Timeline\GetData;

$log = new Logger('timeline-api');
$log->pushHandler(new StreamHandler(LOG_DIR . '/' . date('Ymd') . '.log', Logger::DEBUG));


$controller_name = $_GET['_controller'];

$log->addInfo("Controller: " . $controller_name);

$class = "\Timeline\\$controller_name";

if (class_exists($class))
{
    $controller = new $class($log);
    $controller->process();
}
else
{
    $log->addInfo("Unknown controller");
}







