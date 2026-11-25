<?php
require_once __DIR__ . '/vendor/autoload.php';
use app\Configuration;

ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require_once("app/Configuration.php");

$configuration = new Configuration();
$router = $configuration->getRouter();

$router->dispatch(
    Request::get("controller", "login"),
    Request::get("method", "show")
);