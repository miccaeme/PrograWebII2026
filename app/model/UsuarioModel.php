<?php

namespace app\model;

class UsuarioModel
{
    private $database;
    public function __construct($database){
        $this->database = $database;
    }



    public function validar($datos, $password) {
        if (empty($datos["email"])) {
            return "El email es obligatorio";
        }
    }
}