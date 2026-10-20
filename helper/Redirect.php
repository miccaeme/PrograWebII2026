<?php

class Redirect
{
    public static function baseUrl()
    {
        $scriptName = $_SERVER["SCRIPT_NAME"] ?? "/index.php";
        $scriptDirectory = str_replace(DIRECTORY_SEPARATOR, "/", dirname($scriptName));
        $baseUrl = rtrim($scriptDirectory, "/");

        return $baseUrl === "." ? "" : $baseUrl;
    }

    public static function to($url)
    {
        header("Location: $url");
        exit();
    }

    public static function toIndex()
    {
        self::to(self::baseUrl() . "/index.php");
    }

    public static function toController($controller, $method)
    {
        self::to(self::controllerUrl($controller, $method));
    }

    public static function controllerUrl($controller, $method)
    {
        $query = http_build_query([
            "controller" => $controller,
            "method" => $method
        ]);

        return self::baseUrl() . "/index.php?" . $query;
    }
}
