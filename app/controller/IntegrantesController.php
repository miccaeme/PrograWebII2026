<?php

namespace app\controller;
use Redirect;

class IntegrantesController
{
    private $model;
    private $render;

    public function __construct($model, $render){
        $this->model = $model;
        $this->render = $render;
    }

    public function show(){

        $data = [
            "logueado"       => !empty($_SESSION["logueado"]),
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "",
            "integrantes"    => $this->model->getIntegrantes()
        ];


         $this->render->renderiza("integrantes", $data);
    }
}