<?php

namespace app\controller;

use Request;

class LoginController {
    private $usuarioModel;
    private $renderer;

    public function __construct($usuarioModel, $renderer) {
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
    }

    public function show() {
        $this->renderer->renderiza("login");
    }

    public function procesar() {
        $usuarioRecibido = Request::post("usuario", "");
        $passwordRecibida = Request::post("password", "");
        $usuario = is_string($usuarioRecibido) ? trim($usuarioRecibido) : "";
        $password = is_string($passwordRecibida) ? $passwordRecibida : "";
        $errores = [];

        if ($usuario === "") {
            $errores["usuario"] = "Ingresá tu nombre de usuario o email.";
        }

        if ($password === "") {
            $errores["password"] = "Ingresá tu contraseña.";
        }

        $datos = ["usuario" => $usuario, "errores" => $errores];
        $this->renderer->renderiza("login", $datos);
    }
}
