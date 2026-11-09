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

        foreach ($preguntas as $i => $p) {
            $preguntas[$i]["respuestas"] = $this->model->obtenerRespuestas($p["id"]);
        }

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

    public function activas()
    {
        Auth::requiereRol(["editor", "admin"]);

        $preguntas = $this->model->obtenerActivas();

        foreach ($preguntas as $i => $p) {
            $preguntas[$i]["respuestas"] = $this->model->obtenerRespuestas($p["id"]);
        }

        $this->render->renderiza("editorActivas", [
            "logueado" => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "",
            "preguntas" => $preguntas,
            "sin_activas" => empty($preguntas)
        ]);
    }

    public function inactivas()
    {
        Auth::requiereRol(["editor", "admin"]);

        $preguntas = $this->model->obtenerInactivas();

        foreach ($preguntas as $i => $p) {
            $preguntas[$i]["respuestas"] = $this->model->obtenerRespuestas($p["id"]);
        }

        $this->render->renderiza("editorInactivas", [
            "logueado" => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "",
            "preguntas" => $preguntas,
            "sin_inactivas" => empty($preguntas)
        ]);
    }

    public function baja()
    {
        Auth::requiereRol(["editor", "admin"]);

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("editor", "activas");
        }

        $this->model->cambiarEstado(Request::post("id"), "inactiva");

        Redirect::toController("editor", "activas");
    }

    public function reactivar()
    {
        Auth::requiereRol(["editor", "admin"]);

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("editor", "inactivas");
        }

        $this->model->cambiarEstado(Request::post("id"), "activa");

        Redirect::toController("editor", "inactivas");
    }
}