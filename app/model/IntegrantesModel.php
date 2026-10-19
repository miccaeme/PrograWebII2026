<?php

namespace app\model;

class IntegrantesModel
{
    private $database;
    public function __construct($database){
        $this->database = $database;
    }

    public function getIntegrantes(){
        return $this->database->queryAll("SELECT * FROM integrantes");
    }
    public function show()
    {
        $data = [
            "logueado"       => $_SESSION["logueado"] ?? false,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? null,

        ];

        $this->render->renderiza("integrantes", $data);
    }




}