<?php

namespace app;

use app\controller\AdminController;
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
use app\model\PreguntaModel;
use app\controller\PreguntaController;

require_once("helper/Autoloader.php");
require_once("helper/Router.php");
include_once("model/LobbyModel.php");
include_once("model/UsuarioModel.php");
include_once("model/PreguntaModel.php");
include_once("controller/LobbyController.php");
include_once("controller/RegistroController.php");
include_once("controller/LoginController.php");
include_once("controller/PerfilController.php");
include_once("controller/PreguntaController.php");

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

    public function getPreguntaModel()
    {
        return new PreguntaModel($this->getDatabase());
    }

    public function getPreguntaController()
    {
        return new PreguntaController($this->getPreguntaModel(), $this->getRender());
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

    public function getAdminController(){
        return new AdminController($this->getUsuarioModel(), $this->getRender());
    }
}
