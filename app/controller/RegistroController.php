<?php

namespace app\controller;

use Request;

class RegistroController
{
    private $model;
    private $render;

    public function __construct($model, $render){
        $this->model = $model;
        $this->render = $render;
    }

    public function show(){
        $this->render->renderiza("registro", []);
    }

    public function procesar(){

        $datos = [
            "nombre_completo" => trim(Request::post("nombre_completo", "")),
            "anio_nacimiento" => trim(Request::post("anio_nacimiento", "")),
            "sexo" => Request::post("sexo", ""),
            "email" => trim(Request::post("email", "")),
            "nombre_usuario" => trim(Request::post("nombre_usuario", "")),
        ];
        $password = Request::post("password", "");
        $passwordRepetida = Request::post("password_repetida", "");

        $errores = $this->model->validar($datos, $password);
        if($password !== $passwordRepetida){
            $errores["password_repetida"] = "Las contraseñas no coinciden.";
        }

        if($errores){
            $datos["errores"] = $errores;
            $datos["sexo_M"] = $datos["sexo"] === "M";
            $datos["sexo_F"] = $datos["sexo"] === "F";
            $datos["sexo_N"] = $datos["sexo"] === "N";
            $this->render->renderiza("registro", $datos);
            return;
        }
        $this->model->crear($datos, $password);
        $this->render->renderiza("registro", ["exito" => "¡Registro exitoso!"]);
    }

}
