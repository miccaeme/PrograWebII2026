<?php

namespace app\controller;
use Request;
use Auth;

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
        Auth::requiereRol(["jugador"]);
    }

    public function show()
    {
        $this->autenticar();
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/nueva");
        exit();
    }

    public function nueva()
    {
        $this->autenticar();
        $idUsuario = $_SESSION['usuario_id'] ?? 0;

        $partidaActivaBD = $this->model->obtenerPartidaActiva($idUsuario);

        if ($partidaActivaBD) {
            // continua partida si hay una en curso en la sesion
            $_SESSION['partida_activa'] = [
                'id_partida'       => (int)$partidaActivaBD['id'],
                'puntaje'          => (int)$partidaActivaBD['puntaje'],
                'preguntas_usadas' => $_SESSION['partida_activa']['preguntas_usadas'] ?? [],
                'categoria_actual' => $_SESSION['partida_activa']['categoria_actual'] ?? null,
                'pregunta_actual'  => $_SESSION['partida_activa']['pregunta_actual'] ?? null
            ];

            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/jugar");
            exit();
        }


        $idNuevaPartida = $this->model->crearPartida($idUsuario);

        $_SESSION['partida_activa'] = [
            'id_partida'       => $idNuevaPartida,
            'puntaje'          => 0,
            'preguntas_usadas' => [],
            'categoria_actual' => null,
            'pregunta_actual'  => null
        ];

        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/jugar");
        exit();
    }

    public function jugar()
    {
        $this->autenticar();

        if (!isset($_SESSION['partida_activa'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/nueva");
            exit();
        }

        $todasCategorias = $this->model->obtenerCategorias();
        //Check si hay pregunta en la sesion

        if (!empty($_SESSION['partida_activa']['pregunta_actual'])) {
            $categoriaActual = $_SESSION['partida_activa']['categoria_actual'];
            $preguntaActual = $_SESSION['partida_activa']['pregunta_actual'];

            $data = [
                "logueado"              => true,
                "nombre_usuario"        => $_SESSION["nombre_usuario"] ?? "Jugador",
                "puntaje_actual"        => $_SESSION['partida_activa']['puntaje'],
                "numero_pregunta"       => $_SESSION['partida_activa']['puntaje'] + 1,
                "categoria_id"          => $categoriaActual['id'],
                "categoria_nombre"      => $categoriaActual['nombre'],
                "categoria_color"       => $categoriaActual['color'],
                "enunciado"             => $preguntaActual['enunciado'],
                "respuestas"            => $preguntaActual['respuestas'],
                "categorias_carrusel"   => $todasCategorias,
                "es_recarga"            => true // Bandera para omitir la animación
            ];

            $this->render->renderiza("partida", $data);
            return;
        }

        //partida desde cero

        $categoriaSeleccionada = $this->model->obtenerCategoriaAleatoria();

        $idUsuario = $_SESSION['usuario_id'] ?? 0;
        $dificultad = $this->model->obtenerDificultadParaUsuario($idUsuario);

        $vistas = $this->model->obtenerIdVistos($idUsuario);
        $excluidas = array_merge($_SESSION['partida_activa']['preguntas_usadas'], $vistas);

        $pregunta = $this->model->obtenerPreguntaAleatoriaPorCategoria(
            $categoriaSeleccionada['id'],
            $excluidas,
            $dificultad
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

    public function responder()
    {
        $this->autenticar();

        if (!isset($_SESSION['partida_activa'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/lobby/show");
            exit();
        }

        $idRespuesta = Request::post("id_respuesta", null);
        $esCorrecta = $idRespuesta && $this->model->esRespuestaCorrecta($idRespuesta);
        $idPartida = $_SESSION['partida_activa']['id_partida'] ?? null;

        if ($esCorrecta) {
            $_SESSION['partida_activa']['puntaje']++;


            if ($idPartida) {
                $this->model->actualizarPuntajePartida($idPartida, $_SESSION['partida_activa']['puntaje']);
            }
            //limpiamos la pregunta de la sesion.
            $_SESSION['partida_activa']['pregunta_actual'] = null;
            $_SESSION['partida_activa']['categoria_actual'] = null;

            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/respuestaCorrecta");
            exit();
        } else {
            $idCategoria = $_SESSION['partida_activa']['categoria_actual']['id'] ?? 1;
            $puntajeFinal = $_SESSION['partida_activa']['puntaje'];

            $preguntaActual = $_SESSION['partida_activa']['pregunta_actual'] ?? null;
            $respuestaCorrectaTexto = null;

            if ($preguntaActual) {
                $respuestaCorrectaTexto = $this->model->obtenerRespuestaCorrecta($preguntaActual['id']);
            }

            // finaliza la partida
            if ($idPartida) {
                $this->model->finalizarPartida($idPartida, $idCategoria, $puntajeFinal);
            }

            $_SESSION['partida_finalizada'] = [
                'puntaje'            => $puntajeFinal,
                'enunciado'          => $preguntaActual['enunciado'] ?? '',
                'respuesta_correcta' => $respuestaCorrectaTexto,
                'categoria_nombre'   => $_SESSION['partida_activa']['categoria_actual']['nombre'] ?? '',
                'categoria_color'    => $_SESSION['partida_activa']['categoria_actual']['color'] ?? '#ef4444'
            ];

            unset($_SESSION['partida_activa']);

            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/resultado");
            exit();
        }
    }

    public function respuestaCorrecta()
    {
        $this->autenticar();

        if (!isset($_SESSION['partida_activa'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/jugar");
            exit();
        }

        $data = [
            "logueado"         => true,
            "nombre_usuario"   => $_SESSION["nombre_usuario"] ?? "Jugador",
            "puntaje_actual"   => $_SESSION['partida_activa']['puntaje'],
            "enunciado"        => $_SESSION['partida_activa']['pregunta_actual']['enunciado'] ?? '',
            "categoria_nombre" => $_SESSION['partida_activa']['categoria_actual']['nombre'] ?? 'Trivia',
            "categoria_color"  => $_SESSION['partida_activa']['categoria_actual']['color'] ?? '#10b981'
        ];

        $this->render->renderiza("respuestaCorrecta", $data);
    }

    // resultados
    public function resultado()
    {
        $this->autenticar();

        $info = $_SESSION['partida_finalizada'] ?? null;
        $puntaje = $info['puntaje'] ?? 0;


        $data = [
            "logueado"           => true,
            "nombre_usuario"     => $_SESSION["nombre_usuario"] ?? "Jugador",
            "puntaje"            => $puntaje,
            "enunciado"          => $info['enunciado'] ?? null,
            "respuesta_correcta" => $info['respuesta_correcta'] ?? null,
            "categoria_nombre"   => $info['categoria_nombre'] ?? null,
            "categoria_color"    => $info['categoria_color'] ?? '#ef4444'

        ];

        unset($_SESSION['partida_finalizada']);
        $this->render->renderiza("resultado", $data);
    }
}