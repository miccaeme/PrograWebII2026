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

    private function sincronizarPartida($partida)
    {
        if (!$partida) return;
        $anterior = $_SESSION['partida_activa'] ?? [];
        if ($partida['estado'] !== 'en_curso') {
            if (($partida['motivo_finalizacion'] ?? '') === 'tiempo'
                && (empty($anterior) || (int)($anterior['id_partida'] ?? 0) === (int)$partida['id'])) {
                $_SESSION['partida_tiempo_agotado'] = (int)$partida['id'];
            }
            if ((int)($anterior['id_partida'] ?? 0) === (int)$partida['id']) unset($_SESSION['partida_activa']);
            return;
        }
        $pregunta = $partida['pregunta'];
        $_SESSION['partida_activa'] = [
            'id_partida' => (int)$partida['id'], 'puntaje' => (int)$partida['puntaje'],
            'preguntas_usadas' => (int)($anterior['id_partida'] ?? 0) === (int)$partida['id']
                ? ($anterior['preguntas_usadas'] ?? []) : [],
            'pregunta_actual' => $pregunta,
            'categoria_actual' => $pregunta ? [
                'id' => $pregunta['categoria_id'], 'nombre' => $pregunta['categoria_nombre'],
                'color' => $pregunta['categoria_color']
            ] : null
        ];
        if ($pregunta && !in_array($pregunta['id'], $_SESSION['partida_activa']['preguntas_usadas'])) {
            $_SESSION['partida_activa']['preguntas_usadas'][] = $pregunta['id'];
        }
    }

    private function mostrarPartida($partida)
    {
        header('Cache-Control: no-store');
        $pregunta = $partida['pregunta'];
        $habilitada = $partida['estado'] === 'en_curso' && $partida['pregunta_vence_en'] !== null;
        $this->render->renderiza('partida', [
            'logueado' => true, 'nombre_usuario' => $_SESSION['nombre_usuario'] ?? 'Jugador',
            'puntaje_actual' => (int)$partida['puntaje'], 'id_partida' => (int)$partida['id'],
            'id_pregunta' => $habilitada ? $partida['pregunta_actual_id'] : '',
            'pregunta_vence_en' => $habilitada ? $partida['pregunta_vence_en'] : '',
            'tiempo_restante_ms' => $habilitada ? $partida['tiempo_restante_ms'] : 0,
            'segundos_restantes' => $habilitada ? (int)ceil($partida['tiempo_restante_ms'] / 1000) : 0,
            'pregunta_habilitada' => $habilitada ? '1' : '0',
            'tiempo_agotado' => ($partida['motivo_finalizacion'] ?? '') === 'tiempo' ? '1' : '0',
            'categoria_id' => $pregunta['categoria_id'] ?? '',
            'categoria_nombre' => $pregunta['categoria_nombre'] ?? '',
            'categoria_color' => $pregunta['categoria_color'] ?? '#7028E8',
            // No entregar el contenido antes de habilitar el plazo en el servidor.
            'enunciado' => $habilitada ? $pregunta['enunciado'] : '',
            'respuestas' => $habilitada ? $pregunta['respuestas'] : [],
            'categorias_carrusel' => $this->model->obtenerCategorias()
        ]);
    }

    private function comprobarPostTemporizador()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit();
        }
    }

    /* utiliza la pregunta y el vencimiento persistidos.

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
    */

    public function nueva()
    {
        $this->autenticar();
        $idUsuario = $_SESSION['usuario_id'];
        $activa = $this->model->obtenerPartidaActiva($idUsuario);
        $idPartida = $activa['id'] ?? $this->model->crearPartida($idUsuario);
        $partida = $this->model->consultarPartida($idPartida, $idUsuario);
        $this->sincronizarPartida($partida);
        if ($partida['estado'] === 'en_curso') unset($_SESSION['partida_tiempo_agotado']);
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/jugar");
        exit();
    }

    /* utiliza la pregunta y el vencimiento persistidos.

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
    */

    public function jugar()
    {
        $this->autenticar();
        $idUsuario = $_SESSION['usuario_id'];
        $idPartida = $_SESSION['partida_activa']['id_partida'] ?? null;
        if (!$idPartida) {
            $activa = $this->model->obtenerPartidaActiva($idUsuario);
            $idPartida = $activa['id'] ?? $_SESSION['partida_tiempo_agotado'] ?? null;
            if (!$idPartida) {
                header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/nueva");
                exit();
            }
        }
        $partida = $this->model->prepararPregunta($idPartida, $idUsuario,
            $_SESSION['partida_activa']['preguntas_usadas'] ?? []);
        if (!$partida) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/nueva");
            exit();
        }
        $this->sincronizarPartida($partida);
        if ($partida['estado'] === 'finalizada' && $partida['motivo_finalizacion'] !== 'tiempo') {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/resultado");
            exit();
        }
        $this->mostrarPartida($partida);
    }

    public function tiempo()
    {
        $this->autenticar();
        $this->comprobarPostTemporizador();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $idPartida = filter_var(Request::post('id_partida'), FILTER_VALIDATE_INT);
        if (!$idPartida || $idPartida < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Partida invalida']);
            return;
        }
        try {
            $partida = Request::post('habilitar') === '1'
                ? $this->model->habilitarPregunta($idPartida, $_SESSION['usuario_id'])
                : $this->model->consultarPartida($idPartida, $_SESSION['usuario_id']);
            if (!$partida) {
                http_response_code(404);
                echo json_encode(['error' => 'Partida no encontrada']);
                return;
            }
            $this->sincronizarPartida($partida);
            echo json_encode([
                'estado' => $partida['estado'], 'motivo' => $partida['motivo_finalizacion'],
                'id_pregunta' => $partida['pregunta_actual_id'],
                'pregunta_vence_en' => $partida['pregunta_vence_en'],
                'tiempo_restante_ms' => $partida['tiempo_restante_ms'],
                'pregunta' => $partida['estado'] === 'en_curso' && $partida['pregunta_vence_en']
                    ? $partida['pregunta'] : null
            ]);
        } catch (\Throwable $error) {
            \Log::error('Error al consultar el temporizador: ' . $error->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo verificar el tiempo']);
        }
    }

    /*
utiliza la pregunta y el vencimiento persistidos.

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
    */

    public function responder()
    {
        $this->autenticar();
        $this->comprobarPostTemporizador();
        $idPartida = filter_var(Request::post('id_partida'), FILTER_VALIDATE_INT);
        $idRespuesta = filter_var(Request::post('id_respuesta'), FILTER_VALIDATE_INT);
        $idPregunta = filter_var(Request::post('id_pregunta'), FILTER_VALIDATE_INT);
        $vencimiento = Request::post('pregunta_vence_en');
        if (!$idPartida || !$idRespuesta || !$idPregunta || !is_string($vencimiento)) {
            http_response_code(400);
            return;
        }
        $partida = $this->model->responderConTiempo($idPartida, $_SESSION['usuario_id'],
            $idRespuesta, $idPregunta, $vencimiento);
        if (!$partida) {
            http_response_code(404);
            return;
        }
        $this->sincronizarPartida($partida);
        if ($partida['estado'] === 'finalizada') {
            if ($partida['motivo_finalizacion'] === 'tiempo') {
                header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/jugar");
                exit();
            }
            $pregunta = $partida['pregunta'];
            $_SESSION['partida_finalizada'] = [
                'puntaje' => $partida['puntaje'], 'enunciado' => $pregunta['enunciado'] ?? '',
                'id_pregunta' => $pregunta['id'] ?? null,
                'respuesta_correcta' => $pregunta ? $this->model->obtenerRespuestaCorrecta($pregunta['id']) : null,
                'categoria_nombre' => $pregunta['categoria_nombre'] ?? '',
                'categoria_color' => $pregunta['categoria_color'] ?? '#ef4444'
            ];
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/resultado");
            exit();
        }
        if (!empty($partida['respuesta_aceptada'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/respuestaCorrecta");
        } else {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/jugar");
        }
        exit();
    }

    public function respuestaCorrecta()
    {
        $this->autenticar();

        if (isset($_SESSION['partida_activa']['id_partida'])) {
            $partida = $this->model->consultarPartida($_SESSION['partida_activa']['id_partida'], $_SESSION['usuario_id']);
            $this->sincronizarPartida($partida);
            if (!$partida || $partida['estado'] !== 'en_curso') {
                header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/partida/jugar");
                exit();
            }
        }

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

        $_SESSION['ultima_pregunta_resultado'] = $info['id_pregunta'] ?? null;
        unset($_SESSION['partida_finalizada']);
        $this->render->renderiza("resultado", $data);
    }
}