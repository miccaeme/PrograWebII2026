<?php

require_once __DIR__ . "/../vendor/mustache/Mustache/Autoloader.php";
Mustache_Autoloader::register(__DIR__ . "/../vendor/mustache");

class MustacheRender
{
    private $mustache;
    private $viewPath;

    public function __construct()
    {
        $this->viewPath = dirname(__DIR__) . "/app/view";
        $this->mustache = new Mustache_Engine([
            "loader" => new Mustache_Loader_FilesystemLoader($this->viewPath, ["extension" => ".mustache"])
        ]);
    }

    public function renderiza($view, $data = [])
    {
        require_once($this->viewPath . "/header.php");
        echo $this->mustache->render($view . "View", $data);
        require_once($this->viewPath . "/footer.php");
    }
}
