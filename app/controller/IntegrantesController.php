<?php

namespace app\controller;

class IntegrantesController
{
    private $model;
    private $render;

    public function __construct($model, $render){
        $this->model = $model;
        $this->render = $render;
    }

    public function show(){
        $data["mensaje"] = "¡Hola! Somos los integrantes del equipo:";
        $data["integrantes"] = $this->model->getIntegrantes();

         $this->render->renderiza("integrantes", $data);
    }
}