<?php
require_once __DIR__ . '/vendor/autoload.php';
use app\Configuration;

session_start();

require_once("app/Configuration.php");

$configuration = new Configuration();
$router = $configuration->getRouter();

$router->dispatch(
    Request::get("controller", "login"),
    Request::get("method", "show")
);