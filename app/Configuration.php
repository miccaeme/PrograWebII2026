<?php

namespace app;

use app\controller\LoginController;
use app\model\UsuarioModel;
use MustacheRender;
use MyDatabase;
use Router;
use app\model\LobbyModel;
use app\model\LoginModel;
use app\controller\LobbyController;
use app\controller\RegistroController;
use app\controller\PerfilController;

require_once("helper/Autoloader.php");
require_once __DIR__ . "/../vendor/autoload.php";
require_once("helper/Router.php");
include_once("model/LobbyModel.php");
include_once("model/UsuarioModel.php");
include_once("controller/LobbyController.php");
include_once("controller/RegistroController.php");
include_once("controller/LoginController.php");
include_once("controller/PerfilController.php");

class Configuration
{
    public function __construct()
    {
    }

    public function getLobbyModel()
    {
        return new LobbyModel($this->getDatabase());
    }

    public function getUsuarioModel()
    {
        return new UsuarioModel($this->getDatabase());
    }

    public function getLobbyController()
    {
        return new LobbyController($this->getLobbyModel(), $this->getRender());
    }

    public function getLoginController()
    {
        return new LoginController($this->getUsuarioModel(), $this->getRender());
    }
    public function getRegistroController(){
        return new RegistroController($this->getUsuarioModel(), $this->getRender());
    }

    public function getPerfilController(){
        return new PerfilController($this->getUsuarioModel(), $this->getRender());
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
        return new Router($this, "lobby", "show");
    }
}
