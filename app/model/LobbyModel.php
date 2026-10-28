<?php

namespace app\model;

class LobbyModel
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
    public function getUltimasPartidas($idUsuario){
        return $this->database->queryParams(
            "SELECT p.id, p.puntaje, p.fecha_creacion, c.nombre AS categoria, c.color, c.icono
            FROM partidas p
            INNER JOIN categorias c ON p.id_categoria = c.id
            WHERE p.id_usuario=?
            ORDER BY p.fecha_creacion DESC",
            [$idUsuario]
        );
    }



}