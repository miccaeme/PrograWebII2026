<?php

namespace app;

use app\controller\LoginController;
use app\model\UsuarioModel;
use MustacheRender;
use MyDatabase;
use Router;
use app\model\IntegrantesModel;
use app\model\LoginModel;
use app\controller\IntegrantesController;
use app\controller\RegistroController;

require_once("helper/Autoloader.php");
require_once("helper/Router.php");
include_once("model/IntegrantesModel.php");
include_once("model/UsuarioModel.php");
include_once("controller/IntegrantesController.php");
include_once("controller/RegistroController.php");
include_once("controller/LoginController.php");

class Configuration
{
    public function __construct()
    {
    }

    public function getIntegrantesModel()
    {
        return new IntegrantesModel($this->getDatabase());
    }

    public function getUsuarioModel()
    {
        return new UsuarioModel($this->getDatabase());
    }

    public function getIntegrantesController()
    {
        return new IntegrantesController($this->getIntegrantesModel(), $this->getRender());
    }

    public function getLoginController()
    {
        return new LoginController($this->getUsuarioModel(), $this->getRender());
    }
    public function getRegistroController(){
        return new RegistroController($this->getUsuarioModel(), $this->getRender());
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
