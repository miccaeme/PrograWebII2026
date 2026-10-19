<?php

namespace app\controller;

use Request;

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

        // Guardamos los datos clave del usuario en la sesión
        $usuarioLogueado = $resultado["usuario"];
        $_SESSION["usuario_id"] = $usuarioLogueado["id"] ?? null;
        $_SESSION["nombre_usuario"] = $usuarioLogueado["nombre_usuario"] ?? "";
        $_SESSION["logueado"] = true;

        // Redireccionamos a la pantalla principal o de inicio
        header("Location: index.php?controller=integrantes&method=show");
        exit();
    }

    public function logout()
    {
        session_destroy();
        header("Location: index.php?controller=login&method=show");
        exit();
    }
}