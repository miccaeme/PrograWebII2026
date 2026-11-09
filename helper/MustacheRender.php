<?php

// require_once __DIR__ . '/../vendor/mustache/Mustache/Autoloader.php';
//Mustache_Autoloader::register(__DIR__ . "/../vendor/mustache");

class MustacheRender
{
    private $mustache;
    private $viewPath;

    public function __construct()
    {
        $this->viewPath = dirname(__DIR__) . "/app/view";

        $loader = new Mustache_Loader_FilesystemLoader($this->viewPath, [
            "extension" => ".mustache"
        ]);
        $this->mustache = new Mustache_Engine([
            "loader" => $loader,
            // Configuramos el partials_loader apuntando a la misma carpeta
            "partials_loader" => $loader
        ]);
    }
    public function renderiza($view, $data = [])
    {
        $rol = $_SESSION["rol"] ?? "";
        // Las vistas usan {{baseUrl}} para cargar assets y construir enlaces.
        // Debe calcularse aquí para que esté disponible en todas las vistas.
        $data["baseUrl"] = UrlHelper::baseUrl();
        $data["es_admin"] = $rol === "admin";
        $data["es_editor"] = $rol === "editor";

        echo $this->mustache->render($view . "View", $data);
    }

}
