<?php

class Auth
{
    private const PUBLICAS = [
        "login", 
        "registro",
        "pregunta/reportar", // contesta JSON, valida adentro del método
    ];

    private const PERMISOS = [
        "admin"               => ["admin"],
        "editor"              => ["editor"],
        "categoria"           => ["editor"],
        "lobby"               => ["jugador"],
        "partida"             => ["jugador"],
        "ranking"             => ["jugador"],
        "pregunta"            => ["jugador", "editor"],
        "pregunta/mias"       => ["jugador"],
        "pregunta/editar"     => ["editor"],
        "pregunta/actualizar" => ["editor"],
    ];

    public static function verificarAcceso($controller, $method)
    {
        $controller = strtolower($controller);
        $method = strtolower($method);
        $ruta = "$controller/$method";

        if(in_array($controller, self::PUBLICAS, true) || in_array($ruta, self::PUBLICAS, true)){
            return;
        }

        self::requiereLogin();

        $roles = self::PERMISOS[$ruta] ?? self::PERMISOS[$controller] ?? null;

        if($roles !== null){
            self::requiereRol($roles);
        }
    }

    public static function rolActual(){
        return ($_SESSION["rol"] ?? "") ?: "jugador";
    }

    public static function tieneRol($roles){
        return in_array(self::rolActual(), $roles, true);
    }

    public static function requiereLogin()
    {
        if (empty($_SESSION["logueado"]) || empty($_SESSION["usuario_id"])) {
            Redirect::toController("login", "show");
        }
        self::verificarSesionUnica();
    }

    public static function requiereRol($rolesPermitidos)
    {
        self::requiereLogin();

        if (!self::tieneRol($rolesPermitidos)) {
            self::irAlInicio();
        }
    }

    public static function irAlInicio()
    {
        $rol = self::rolActual();

        if ($rol === "admin") {
            Redirect::toController("admin", "show");
        } elseif ($rol === "editor") {
            Redirect::toController("editor", "show");
        } else {
            Redirect::toController("lobby", "show");
        }
    }

    private static function verificarSesionUnica()
    {
        $config = new \app\Configuration();
        $usuarioModel = $config->getUsuarioModel();

        $sessionActivaEnDb = $usuarioModel->obtenerSessionId($_SESSION["usuario_id"]);

        if ($sessionActivaEnDb !== null && $sessionActivaEnDb !== session_id()) {
            $_SESSION = [];
            session_destroy();

            $esAjax = str_contains($_SERVER["HTTP_ACCEPT"] ?? "", "application/json")
                || strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "xmlhttprequest";

            if ($esAjax) {
                http_response_code(401);
                header("Content-Type: application/json; charset=utf-8");
                echo json_encode(["error" => "Tu sesión fue cerrada porque ingresaste desde otro dispositivo o ventana."]);
                exit();
            }

            Redirect::toController("login", "show");
        }
    }
}