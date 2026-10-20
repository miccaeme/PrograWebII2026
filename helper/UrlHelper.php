<?php

class UrlHelper
{

    /* Genera automaticamente las URL del proyecto sin importar en que carpeta esté*/

    /*Este método obtiene la ruta base donde se encuentra tu aplicación.*/
    public static function baseUrl()
    {
        $scriptName = $_SERVER["SCRIPT_NAME"] ?? "/index.php";
        $scriptDirectory = str_replace(DIRECTORY_SEPARATOR, "/", dirname($scriptName));
        $baseUrl = rtrim($scriptDirectory, "/");

        return $baseUrl === "." ? "" : $baseUrl;
    }

    public static function indexUrl()
    {
        /*La palabra self:: permite llamar a otro método estático de la misma clase.
        Por ejemplo, en tu localhost: UrlHelper::indexUrl();
         Devuelve:/PrograWebII2026/index.php
        Para no tener que escribir manualmente la ruta del archivo index.php en distintas partes del proyecto.
        */
        return self::baseUrl() . "/index.php";
    }


/*Construir una direccion web*/
    public static function controllerUrl($controller, $method)
    {
        $query = http_build_query([
            "controller" => $controller,
            "method" => $method
        ]);

        return self::indexUrl() . "?" . $query;
    }
}
