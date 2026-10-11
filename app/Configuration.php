<?php

namespace app;

use MustacheRender;
use MyDatabase;
use Router;
use app\model\IntegrantesModel;
use app\controller\IntegrantesController;

require_once("helper/Autoloader.php");
require_once("helper/Router.php");
include_once("model/IntegrantesModel.php");
include_once("controller/IntegrantesController.php");

class Configuration
{
    public function __construct()
    {
    }

    public function getIntegrantesModel()
    {
        return new IntegrantesModel($this->getDatabase());
    }

    public function getIntegrantesController()
    {
        return new IntegrantesController($this->getIntegrantesModel(), $this->getRender());
    }

    private function getDatabase()
    {
        $config = parse_ini_file("config/config.ini");

        return new MyDatabase(
            $config["db_host"],
            $config["db_user"],
            $config["db_pass"],
            $config["db_name"]
        );
    }

    private function getRender()
    {
        return new MustacheRender();
    }

    public function getRouter()
    {
        return new Router($this, "integrantes", "show");
    }
}
