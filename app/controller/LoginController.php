<?php

namespace app\controller;

use Mailer;
use Request;
use Redirect;
use UrlHelper;


class LoginController
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
        $this->render->renderiza("login", []);
    }

    public function procesar()
    {
        $identificador = trim(Request::post("usuario", ""));
        $password = Request::post("password", "");

        if (empty($identificador) || empty($password)) {
            $this->render->renderiza("login", [
                "error" => "Por favor, completa todos los campos.",
                "usuario" => $identificador
            ]);
            return;
        }

        $resultado = $this->model->autenticar($identificador, $password);

        if (isset($resultado["error"])) {
            $this->render->renderiza("login", [
                "error" => $resultado["error"],
                "usuario" => $identificador
            ]);
            return;
        }


        $usuarioLogueado = $resultado["usuario"];
        $_SESSION["usuario_id"] = $usuarioLogueado["id"] ?? null;
        $_SESSION["nombre_usuario"] = $usuarioLogueado["nombre_usuario"] ?? "";
        $_SESSION["logueado"] = true;

        // Redireccionamos a la pantalla principal o de inicio
        Redirect::toController("integrantes", "show");
    }

    public function logout()
    {
        session_destroy();
        Redirect::toController("login", "show");
    }

    public function recuperar(){
        $this->render->renderiza("recuperar", []);

    }

    public function solicitarRecuperacion(){
        $identificador = trim(Request::post("formulario", ""));
        if (empty($identificador)) {
            $this->render->renderiza("recuperar", ["error" => "Por favor, completa todos los campos."]);
            return;
        }
        $resultado = $this->model->crearTokenRecuperacion($identificador);
        if ($resultado !== null) {
            $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $link = $protocolo . '://' . $_SERVER['HTTP_HOST']
                . UrlHelper::controllerUrl("login", "restablecer")
                . "?token=" . $resultado["token"];

            $mailer = new Mailer();
            $enviado = $mailer->enviarRecuperacion($resultado["usuario"]["email"], $resultado["usuario"]["nombre_completo"], $link);
        }

        $this->render->renderiza("login", ["exito" => "Si los datos corresponden a una cuenta activa, te enviamos un mail para restablecer tu contraseña."]);

    }

}
