<?php

namespace app\model;

class UsuarioModel
{
    private $database;
    public function __construct($database){
        $this->database = $database;
    }



    public function validar($datos, $password) {
        if(empty(trim($datos["nombre_completo"]))){
            return "El nombre es obligatorio";
        }
        if (!filter_var($datos["email"], FILTER_VALIDATE_EMAIL)) {
            return "El email no es valido";
        }

        $anioActual = (int)date("Y");
        $opcionesAnio = [
            "options" => [
                "min_range" => 1900,
                "max_range" => $anioActual
            ]
        ];
        if (empty($datos["anio_nacimiento"])) {
            return "El año de nacimiento es obligatorio.";
        }

        if (!filter_var($datos["anio_nacimiento"], FILTER_VALIDATE_INT, $opcionesAnio)) {
            return "Introduce un año válido entre 1920 y " . $anioActual;
        }

        $sexosPermitidos = ["M", "F", "N"];
        if (!in_array( $datos["sexo"] , $sexosPermitidos , true)) {
            return "Elegí una opción válida para el sexo.";
        }

        if(empty(trim($datos["nombre_usuario"]))){
            return "El nombre de usuario es obligatorio";
        }

        if(empty(($password))){
            return "La contraseña es obligatoria";
        }

        if(strlen($password) < 8){
            return "La contraseña debe tener al menos 8 caracteres";
        }

        return null;

    }
}