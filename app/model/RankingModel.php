<?php

namespace app\model;

class RankingModel
{
    private $database;

    private const FILTROS_PERIODO = [
        "historico" => "",
        "mes" => "AND p.fecha_creacion >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
        "semana" => "AND p.fecha_creacion >= CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY",
        "dia" => "AND p.fecha_creacion >= CURDATE()",
    ];

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerRanking($periodo = "historico")
    {
        $filtroFecha = self::FILTROS_PERIODO[$periodo] ?? "";

        $jugadores = $this->database->queryParams(
            "SELECT u.id, u.nombre_usuario, u.token_perfil, u.foto_perfil,
            COALESCE(SUM(p.puntaje), 0) AS puntaje_total,
            COUNT(p.id) AS partidas_jugadas
            FROM usuarios u 
            LEFT JOIN partidas p ON p.id_usuario = u.id AND p.estado = 'finalizada' $filtroFecha
            WHERE u.rol = 'jugador' AND u.activo = 1
            GROUP BY u.id, u.nombre_usuario, u.token_perfil, u.foto_perfil
            ORDER BY puntaje_total DESC, partidas_jugadas ASC, u.nombre_usuario ASC", []
        );

        foreach ($jugadores as $i => $jugador){
            $jugadores[$i]["posicion"] = $i + 1;
        }

        return $jugadores;
    }
}