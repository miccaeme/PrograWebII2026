<?php

namespace app\controller;

use Auth;
use Request;

class RankingController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    public function show()
    {
        Auth::requiereLogin();

        $periodo = Request::get("periodo", "historico");
        
        if(!in_array($periodo, ["historico", "mes", "semana", "dia"], true)){
            $periodo = "historico";
        }

        $jugadores = $this->model->obtenerRanking($periodo);


        $idUsuario = $_SESSION["usuario_id"] ?? null;


        foreach ($jugadores as $i => $jugador) {
            $jugadores[$i]["es_yo"] = (int)$jugador["id"] === (int)$idUsuario;
            $jugadores[$i]["inicial"] = mb_strtoupper(mb_substr($jugador["nombre_usuario"], 0, 1));
        }


        $podio = array_slice($jugadores, 0, 3);
        $resto = array_slice($jugadores, 3);

        $this->render->renderiza("ranking", [
            "logueado"       => !empty($_SESSION["logueado"]),
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "",
            "podio"          => $podio,
            "resto"          => $resto,
            "hay_resto"      => !empty($resto),
            "sin_jugadores"  => empty($jugadores),
            "es_historico"   => $periodo === "historico",
            "es_mes"         => $periodo === "mes",
            "es_semana"      => $periodo === "semana",
            "es_dia"         => $periodo === "dia",
        ]);
    }
}