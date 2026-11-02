<?php

namespace app\controller;

use Auth;
use Redirect;
use Request;

class EditorController
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
        Auth::requiereRol(["editor", "admin"]);

        $preguntas = $this->model->obtenerPendientes();

        $this->render->renderiza("editor", [
            "logueado" => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "",
            "preguntas" => $preguntas,
            "sin_pendientes" => empty($preguntas)
        ]);
    }

    public function aprobar()
    {
        Auth::requiereRol(["editor", "admin"]);

        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("editor", "show");
        }

        $id = Request::post("id");
        $this->model->cambiarEstado($id, "activa");

        Redirect::toController("editor", "show");
    }

    public function rechazar()
    {
        Auth::requiereRol(["editor", "admin"]);

        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("editor", "show");
        }

        $id = Request::post("id");
        $this->model->cambiarEstado($id, "rechazada");

        Redirect::toController("editor", "show");
    }
}