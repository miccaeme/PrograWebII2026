<?php

namespace app\controller;

use Request;
use Redirect;

class PerfilController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    public function show()
    {
        $this->requiereLogin();
        $this->mostrarPerfil([]);
    }

    public function subirFoto()
    {
        $this->requiereLogin();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("perfil", "show");
        }

        $archivo = Request::file("foto_perfil");

        if (!$archivo || $archivo["error"] === UPLOAD_ERR_NO_FILE) {
            $this->mostrarPerfil(["error" => "Elegí una imagen antes de subirla."]);
            return;
        }

        if ($archivo["error"] === UPLOAD_ERR_INI_SIZE || $archivo["error"] === UPLOAD_ERR_FORM_SIZE) {
            $this->mostrarPerfil(["error" => "La imagen es demasiado pesada."]);
            return;
        }

        if ($archivo["error"] !== UPLOAD_ERR_OK) {
            $this->mostrarPerfil(["error" => "No se pudo subir la imagen. Probá de nuevo."]);
            return;
        }

        $resultado = $this->model->cambiarFoto($_SESSION["usuario_id"], $archivo);

        if (isset($resultado["error"])) {
            $this->mostrarPerfil(["error" => $resultado["error"]]);
            return;
        }
        Redirect::toController("perfil", "show");
    }

    private function mostrarPerfil($extra)
    {
        $usuario = $this->model->buscarPorId($_SESSION["usuario_id"]);
        $this->render->renderiza("perfil", array_merge(["usuario" => $usuario, "logueado" => !empty($_SESSION["logueado"]), "nombre_usuario" => $_SESSION["nombre_usuario"] ?? ""], $extra));
    }

    private function requiereLogin()
    {
        if (empty($_SESSION["usuario_id"])) {
            Redirect::toController("login", "show");
        }
    }
}
