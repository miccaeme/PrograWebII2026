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





}