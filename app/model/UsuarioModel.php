<?php

namespace app\model;

class UsuarioModel
{
    private $database;
    public function __construct($database){
        $this->database = $database;
    }



    public function validar($datos, $password) {
        $errores = [];
        if(empty(trim($datos["nombre_completo"]))){
            $errores["nombre_completo"] = "El nombre es obligatorio.";
        }
        if (!filter_var($datos["email"], FILTER_VALIDATE_EMAIL)) {
            $errores["email"] = "Ingresá un email válido.";
        }

        $anioActual = (int)date("Y");
        $opcionesAnio = [
            "options" => [
                "min_range" => 1900,
                "max_range" => $anioActual
            ]
        ];
        if (empty($datos["anio_nacimiento"])) {
            $errores["anio_nacimiento"] = "El año de nacimiento es obligatorio.";
        } elseif (!filter_var($datos["anio_nacimiento"], FILTER_VALIDATE_INT, $opcionesAnio)) {
            $errores["anio_nacimiento"] = "Ingresá un año válido entre 1900 y " . $anioActual . ".";
        }

        $sexosPermitidos = ["M", "F", "N"];
        if (!in_array( $datos["sexo"] , $sexosPermitidos , true)) {
            $errores["sexo"] = "Elegí una opción válida.";
        }

        if(empty(trim($datos["nombre_usuario"]))){
            $errores["nombre_usuario"] = "El nombre de usuario es obligatorio.";
        }

        if(empty(($password))){
            $errores["password"] = "La contraseña es obligatoria.";
        } elseif(strlen($password) < 8){
            $errores["password"] = "La contraseña debe tener al menos 8 caracteres.";
        }

        if (filter_var($datos["email"], FILTER_VALIDATE_EMAIL) && $this->existeEmail($datos["email"])) {
            $errores["email"] = "Ese email ya está registrado.";
        }

        if ($datos["nombre_usuario"] !== "" && $this->existeUsuario($datos["nombre_usuario"])) {
            $errores["nombre_usuario"] = "Ese nombre de usuario ya existe.";
        }
        if (empty($datos["pais"]) && empty($datos["latitud"])) {
            return "Debes seleccionar tu ubicación en el mapa.";
        }

        return $errores;

    }

    public function existeEmail($email)
    {
        $fila = $this->database->queryOneParams(
            "SELECT id FROM usuarios WHERE email = ?",
            [$email]
        );
        return $fila !== null;
    }

    public function existeUsuario($nombre_usuario)
    {
        $fila = $this->database->queryOneParams(
            "SELECT id FROM usuarios WHERE nombre_usuario = ?",
            [$nombre_usuario]
        );
        return $fila !== null;
    }

    public function crear($datos, $password){
        $hash = password_hash($password, PASSWORD_DEFAULT);

        return $this->database->executeParams(
            "INSERT INTO usuarios (nombre_completo, anio_nacimiento, sexo,  email, nombre_usuario, password,  pais, latitud, longitud)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $datos["nombre_completo"],
                $datos["anio_nacimiento"],
                $datos["sexo"],
                $datos["email"],
                $datos["nombre_usuario"],
                $hash,
                $datos["pais"],
                $datos["latitud"],
                $datos["longitud"]
            ]
        );
    }
    public function buscarPorUsuarioOEmail($identificador)
    {
        return $this->database->queryOneParams(
            "SELECT * FROM usuarios WHERE email = ? OR nombre_usuario = ?",
            [$identificador, $identificador]
        );
    }
    public function autenticar($identificador, $password)
    {
        $usuario = $this->buscarPorUsuarioOEmail($identificador);

        if (!$usuario) {
            return ["error" => "El usuario o correo no existe."];
        }


        if (!password_verify($password, $usuario['password'])) {
            return ["error" => "Contraseña incorrecta."];
        }


        return ["usuario" => $usuario];
    }
}
