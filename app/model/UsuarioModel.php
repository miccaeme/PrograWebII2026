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
        $token = bin2hex(random_bytes(32));

        $filas = $this->database->executeParams(
            "INSERT INTO usuarios (nombre_completo, anio_nacimiento, sexo,  email, nombre_usuario, password,  pais, latitud, longitud, token_validacion)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,?)",
            [
                $datos["nombre_completo"],
                $datos["anio_nacimiento"],
                $datos["sexo"],
                $datos["email"],
                $datos["nombre_usuario"],
                $hash,
                $datos["pais"],
                $datos["latitud"],
                $datos["longitud"],
                $token
            ]
        );

        if ($filas === 1) {
            return $token;
        }
        return null;
    }
    public function buscarPorUsuarioOEmail($identificador)
    {
        return $this->database->queryOneParams(
            "SELECT * FROM usuarios WHERE email = ? OR nombre_usuario = ?",
            [$identificador, $identificador]
        );
    }

    public function buscarPorId($id)
    {
        $usuario = $this->database->queryOneParams(
            "SELECT id, nombre_completo, anio_nacimiento, sexo, pais, ciudad, email, nombre_usuario, foto_perfil, fecha_registro
            FROM usuarios WHERE id = ?", [$id]
        );

        if(!$usuario) {
            return null;
        }

        $sexos = ["M" => "Masculino", "F" => "Femenino", "N" => "Prefiero no cargarlo"];
        $usuario["sexo_texto"] = $sexos[$usuario["sexo"]] ?? "";
        
        return $usuario;
    }

    private function actualizarFoto($id, $ruta)
    {
        return $this->database->executeParams(
            "UPDATE usuarios SET foto_perfil = ? WHERE id = ?",
            [$ruta, $id]
        );
    }

    public function cambiarFoto($id, $archivo)
    {
        $resultado = $this->guardarFoto($id, $archivo);
        if(isset($resultado["error"])) {
            return $resultado;
        }

        $this->actualizarFoto($id, $resultado["ruta"]);
        return $resultado;
    }

    private function guardarFoto($id, $archivo)
    {
        if($archivo["size"] > 2 * 1024 * 1024) {
            return ["error" => "La imagen no puede superar los 2 MB."];
        }

        $permitidos = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
        $tipo = (new \finfo(FILEINFO_MIME_TYPE))->file($archivo["tmp_name"]);
        if(!isset($permitidos[$tipo])){
            return ["error" => "Solo se permiten imágenes JPG, PNG o WEBP."];
        }

        $carpeta = "public/uploads/perfiles";
        if(!is_dir($carpeta)){
            mkdir($carpeta, 0755, true);
        }

        $ruta = $carpeta . "/usuario_" . $id . "_" . bin2hex(random_bytes(8)) . "." . $permitidos[$tipo];

        if (!move_uploaded_file($archivo["tmp_name"], $ruta)){
            return["error" => "No se pudo guardar la imagen."];
        }
        return ["ruta" => $ruta];
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

        if($usuario["activo"] == 0){
            return ["error" => "El usuario no esta activo. Tenés que activar tu cuenta desde el mail que te enviamos."];
        }


        return ["usuario" => $usuario];
    }

    public function activarCuenta($token){
        $usuario = $this->database->queryOneParams(
            "SELECT id FROM usuarios WHERE token_validacion = ?",
            [$token]
        );

        if ($usuario === null) {
            return false;
        }

        $this->database->executeParams(
            "UPDATE usuarios SET activo = 1, token_validacion = NULL WHERE id = ?",
            [$usuario["id"]]
        );

        return true;
    }
}
