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

    private function redirigir($metodo)
    {
        header('Location: ' . \UrlHelper::controllerUrl('partida', $metodo));
        exit();
    }

    public function show()
    {
        $this->autenticar();
        $this->redirigir('nueva');
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



    public function nueva()
    {
        $this->autenticar();
        $idUsuario = $_SESSION['usuario_id'];
        $activa = $this->model->obtenerPartidaActiva($idUsuario);
        $idPartida = $activa['id'] ?? $this->model->crearPartida($idUsuario);
        $partida = $this->model->consultarPartida($idPartida, $idUsuario);
        $this->sincronizarPartida($partida);
        if ($partida['estado'] === 'en_curso') unset($_SESSION['partida_tiempo_agotado']);
        $this->redirigir('jugar');
    }



    public function jugar()
    {
        $this->autenticar();
        $idUsuario = $_SESSION['usuario_id'];
        $idPartida = $_SESSION['partida_activa']['id_partida'] ?? null;
        if (!$idPartida) {
            $activa = $this->model->obtenerPartidaActiva($idUsuario);
            $idPartida = $activa['id'] ?? $_SESSION['partida_tiempo_agotado'] ?? null;
            if (!$idPartida) {
                $this->redirigir('nueva');
            }
        }
        $partida = $this->model->prepararPregunta($idPartida, $idUsuario,
            $_SESSION['partida_activa']['preguntas_usadas'] ?? []);
        if (!$partida) {
            $this->redirigir('nueva');
        }
        $this->sincronizarPartida($partida);
        if ($partida['estado'] === 'finalizada' && $partida['motivo_finalizacion'] !== 'tiempo') {
            $this->redirigir('resultado');
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
                $this->redirigir('jugar');
            }
            $pregunta = $partida['pregunta'];
            $_SESSION['partida_finalizada'] = [
                'puntaje' => $partida['puntaje'], 'enunciado' => $pregunta['enunciado'] ?? '',
                'id_pregunta' => $pregunta['id'] ?? null,
                'respuesta_correcta' => $pregunta ? $this->model->obtenerRespuestaCorrecta($pregunta['id']) : null,
                'categoria_nombre' => $pregunta['categoria_nombre'] ?? '',
                'categoria_color' => $pregunta['categoria_color'] ?? '#ef4444'
            ];
            $this->redirigir('resultado');
        }
        if (!empty($partida['respuesta_aceptada'])) {
            $this->redirigir('respuestaCorrecta');
        } else {
            $this->redirigir('jugar');
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
                $this->redirigir('jugar');
            }
        }

        if (!isset($_SESSION['partida_activa'])) {
            $this->redirigir('jugar');
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