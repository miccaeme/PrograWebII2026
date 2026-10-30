<?php

namespace app\controller;
use Redirect;

class LobbyController
{
    private $model;
    private $render;

    public function __construct($model, $render){
        $this->model = $model;
        $this->render = $render;
    }

    public function show(){


        if(empty($_SESSION["logueado"])){
            header("Location: login");
            exit();

        }

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


        $data = [
            "logueado"       => !empty($_SESSION["logueado"]),
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "Jugador",
            "puntos_ranking" => 1250,
            "posicion_ranking"=> "#12",
            "partidas_recientes"=> $partidasRecientes,
            "partidas_restantes" => $partidasRestantes,
            "tiene_mas_partidas" => $tieneMasPartidas,
            "sin_partidas"=>empty($partidasRecientes)
        ];


         $this->render->renderiza("lobby", $data);
    }
}