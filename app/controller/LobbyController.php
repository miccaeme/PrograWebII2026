<?php

namespace app\controller;
use Redirect;
use Auth;

class LobbyController
{
    private $model;
    private $rankingModel;
    private $render;

    public function __construct($model, $rankingModel, $render){
        $this->model = $model;
        $this->rankingModel = $rankingModel;
        $this->render = $render;
    }

    public function show(){


        Auth::requiereRol(["jugador"]);

        $idUsuario = $_SESSION["usuario_id"]?? null;
        $partidasRecientes = [];
        $partidasRestantes = [];
        $tieneMasPartidas = false;

        if($idUsuario){
            $todasLasPartidas = $this->model->getUltimasPartidas($idUsuario);

            foreach ($todasLasPartidas as &$p){
                $p['fecha_formateada'] = date("d/m/Y H:i", strtotime($p['fecha_creacion']));
            }
            $partidasRecientes = array_slice($todasLasPartidas, 0, 5);
            $partidasRestantes = array_slice($todasLasPartidas, 5);
            $tieneMasPartidas = count ($partidasRestantes)>0;
        }

        $miRanking = $idUsuario ? $this->rankingModel->obtenerPosicionDe($idUsuario) : null;

        $data = [
            "logueado"       => !empty($_SESSION["logueado"]),
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "Jugador",
            "puntos_ranking" => $miRanking["puntaje_total"] ?? 0,
            "posicion_ranking"=> $miRanking ? "#" . $miRanking["posicion"] : "-",
            "partidas_recientes"=> $partidasRecientes,
            "partidas_restantes" => $partidasRestantes,
            "tiene_mas_partidas" => $tieneMasPartidas,
            "sin_partidas"=>empty($partidasRecientes)
        ];


         $this->render->renderiza("lobby", $data);
    }
}