<?php

namespace app\controller;

use Auth;

class AdminController
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
        Auth::requiereRol(["admin"]);
        $this->render->renderiza("admin", ["logueado" => true, "nombre_usuario" => $_SESSION["nombre_usuario"] ?? ""]);
    }

}