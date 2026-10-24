<?php

namespace app\controller;

use Request;
use Redirect;
use Mailer;
use UrlHelper;
use Log;

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
            "pais"            => Request::post("pais", ""),
            "latitud"         => Request::post("latitud", ""),
            "longitud"        => Request::post("longitud", "")
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

        $token = $this->model->crear($datos, $password);

        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $link = $protocolo . '://' . $_SERVER['HTTP_HOST']
            . UrlHelper::controllerUrl("registro", "activar")
            . "?token=" . $token;


        $mailer = new Mailer();
        $enviado = $mailer->enviarValidacion($datos["email"], $datos["nombre_completo"], $link);

        if ($enviado) {
            $datos = ["exito" => "¡Registro exitoso! Te enviamos un mail para activar tu cuenta."];
        } else {
            $datos = ["error" => "Tu cuenta se creó, pero no pudimos enviar el mail de activación."];
        }

        $this->render->renderiza("login", $datos);

}

    public function activar(){
        $token = Request::get("token", "");
        $activada = $this->model->activarCuenta($token);

        if ($activada) {
            $datos = ["exito" => "¡Cuenta activada! Ya podés iniciar sesión."];
        } else {
            $datos = ["error" => "El link de activación no es válido o ya fue usado."];
        }

        $this->render->renderiza("login", $datos);
    }
}
