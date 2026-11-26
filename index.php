<?php
require_once __DIR__ . '/vendor/autoload.php';
use app\Configuration;

ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require_once("app/Configuration.php");

$configuration = new Configuration();
$router = $configuration->getRouter();

$controller = Request::get("controller", "login");
$method     = Request::get("method", "show");

Auth::verificarAcceso($controller, $method);

$router->dispatch($controller, $method);