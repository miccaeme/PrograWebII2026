<?php

class Auth
{
    public static function requiereLogin()
    {
        if (empty($_SESSION["logueado"])) {
            Redirect::toController("login", "show");
        }
    }

    public static function requiereRol($rolesPermitidos)
    {
        self::requiereLogin();
        if (!in_array($_SESSION["rol"] ?? "jugador", $rolesPermitidos, true)) {
            self::irAlInicio();
        }
    }

    public static function irAlInicio()
    {
        $rol = $_SESSION["rol"] ?? "";

        if ($rol === "admin") {
            Redirect::toController("admin", "show");
        } elseif ($rol === "editor") {
            Redirect::toController("editor", "show");
        } else {
            Redirect::toController("lobby", "show");
        }
    }
}