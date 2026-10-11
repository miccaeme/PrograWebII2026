<?php
use app\Configuration;

session_start();
require_once("app/Configuration.php");

$configuration = new Configuration();
$router = $configuration->getRouter();

$router->dispatch(
    Request::get("controller", "integrantes"),
    Request::get("method", "show")
);