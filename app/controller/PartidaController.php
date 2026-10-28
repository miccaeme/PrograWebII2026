<?php

namespace app\controller;
use Request;

class PartidaController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    private function autenticar()
    {
        if (empty($_SESSION["logueado"])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/login/show");
            exit();
        }
    }

    //nueva partida
    public function nueva()
    {
        $this->autenticar();

        $_SESSION['partida_activa'] = [
            'puntaje' => 0,
            'preguntas_usadas' => [],
            'categoria_actual' => null,
            'pregunta_actual' => null
        ];

        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/jugar");
        exit();
    }

    // Vista de juego con selección y ruleta de cartas
    public function jugar()
    {
        $this->autenticar();

        if (!isset($_SESSION['partida_activa'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/nueva");
            exit();
        }


        $todasCategorias = $this->model->obtenerCategorias();
        $categoriaSeleccionada = $this->model->obtenerCategoriaAleatoria();

        $pregunta = $this->model->obtenerPreguntaAleatoriaPorCategoria(
            $categoriaSeleccionada['id'],
            $_SESSION['partida_activa']['preguntas_usadas']
        );

        if (!$pregunta) {
            die("Error: No hay preguntas suficientes cargadas en la categoría: " . $categoriaSeleccionada['nombre']);
        }

        $_SESSION['partida_activa']['categoria_actual'] = $categoriaSeleccionada;
        $_SESSION['partida_activa']['pregunta_actual'] = $pregunta;
        $_SESSION['partida_activa']['preguntas_usadas'][] = $pregunta['id'];

        $data = [
            "logueado"              => true,
            "nombre_usuario"        => $_SESSION["nombre_usuario"] ?? "Jugador",
            "puntaje_actual"        => $_SESSION['partida_activa']['puntaje'],
            "numero_pregunta"       => $_SESSION['partida_activa']['puntaje'] + 1,
            "categoria_id"          => $categoriaSeleccionada['id'],
            "categoria_nombre"      => $categoriaSeleccionada['nombre'],
            "categoria_color"       => $categoriaSeleccionada['color'],
            "enunciado"             => $pregunta['enunciado'],
            "respuestas"            => $pregunta['respuestas'],
            "categorias_carrusel"   => $todasCategorias
        ];

        $this->render->renderiza("partida", $data);
    }

    // Procesar respuesta
    public function responder()
    {
        $this->autenticar();

        if (!isset($_SESSION['partida_activa'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/lobby/show");
            exit();
        }

        $idRespuesta = Request::post("id_respuesta", null);
        $esCorrecta = $idRespuesta && $this->model->esRespuestaCorrecta($idRespuesta);

        if ($esCorrecta) {
            $_SESSION['partida_activa']['puntaje']++;

            // Sigue jugando: pide otra categoría y pregunta
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/jugar");
            exit();
        } else {
            // Respuesta incorrecta: termina la partida
            $idUsuario = $_SESSION['usuario_id'] ?? 0;
            $idCategoria = $_SESSION['partida_activa']['categoria_actual']['id'] ?? 1;
            $puntajeFinal = $_SESSION['partida_activa']['puntaje'];

            $this->model->guardarPartida($idUsuario, $idCategoria, $puntajeFinal);

            $_SESSION['partida_finalizada'] = [
                'puntaje' => $puntajeFinal,
                'motivo' => 'incorrecta'
            ];
            unset($_SESSION['partida_activa']);

            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/resultado");
            exit();
        }
    }

    // vista resultados
    public function resultado()
    {
        $this->autenticar();

        $info = $_SESSION['partida_finalizada'] ?? null;
        $puntaje = $info['puntaje'] ?? 0;


        $data = [
            "logueado"        => true,
            "nombre_usuario"  => $_SESSION["nombre_usuario"] ?? "Jugador",
            "puntaje"         => $puntaje

        ];

        unset($_SESSION['partida_finalizada']);
        $this->render->renderiza("resultado", $data);
    }
}