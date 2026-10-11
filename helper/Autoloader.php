<?php

spl_autoload_register(function ($class) {
    $path = str_replace('\\', '/', $class) . '.php';

    // Clases con namespace (app\controller\EventoController, app\model\...):
    // el namespace refleja la carpeta real a partir de la raíz del proyecto.
    $file = __DIR__ . "/../$path";
    if (file_exists($file)) {
        require_once $file;
        return;
    }

    // Clases globales sin namespace (MyDatabase, Router, Redirect, Request...):
    // todas viven en helper/.
    $file = __DIR__ . "/$path";
    if (file_exists($file)) {
        require_once $file;
    }
});
