<?php


class Auth
{
public static function requiereLogin()
{
    if(empty($_SESSION["logueado"])) {
        Redirect::toController("login", "show");
    }
}


public static function requiereRol($rolesPermitidos){
    self::requiereLogin();
    if(!in_array($_SESSION["rol"] ?? "", $rolesPermitidos, true)){
        Redirect::toController("lobby", "show");
    }


}
}