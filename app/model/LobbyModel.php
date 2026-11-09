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
        // Volver al inicio también reconcilia una pregunta vencida, aunque JS no haya avisado.
        $activas = $this->database->queryParams(
            "SELECT id FROM partidas WHERE id_usuario = ? AND estado = 'en_curso'", [$idUsuario]
        );
        $partidaModel = new PartidaModel($this->database);
        foreach ($activas as $activa) {
            $partidaModel->consultarPartida($activa['id'], $idUsuario);
        }
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
