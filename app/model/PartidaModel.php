<?php

namespace app\model;

use Exception;
use Log;

class PartidaModel
{

    const MINIMO_RESPUESTAS = 5;
    const PORCENTAJE_FACIL = 0.7;
    const PORCENTAJE_DIFICIL = 0.3;
    const DIAS_SIN_REPETIR = 10;


    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerCategorias()
    {
        return $this->database->queryAll("SELECT id, nombre, color, icono FROM categorias");
    }

    public function obtenerCategoriaAleatoria()
    {
        return $this->database->queryOne("SELECT id, nombre, color, icono FROM categorias ORDER BY RAND() LIMIT 1");
    }

    public function obtenerPreguntaAleatoriaPorCategoria($idCategoria, $preguntasRespondidas = [], $dificultad = null)
    {
        $sql = "SELECT id, enunciado FROM preguntas WHERE id_categoria = ? AND estado = 'activa'";
        $params = [$idCategoria];

        if (!empty($preguntasRespondidas)) {
            $placeholders = implode(',', array_fill(0, count($preguntasRespondidas), '?'));
            $sql .= " AND id NOT IN ($placeholders)";
            $params = array_merge($params, $preguntasRespondidas);
        }

        $pregunta = null;

        if($dificultad) {
            [$condicion, $paramsDificultad] = $this->condicionDificultad($dificultad);

            $pregunta = $this->database->queryOneParams($sql . " AND $condicion ORDER BY RAND() LIMIT 1", array_merge($params, $paramsDificultad));
        }

        if(!$pregunta) {
            $pregunta = $this->database->queryOneParams($sql . " ORDER BY RAND() LIMIT 1", $params);
        }

        // Si ya respondió todas las de esta categoría, tomamos cualquiera de la categoría
        if (!$pregunta) {
            $pregunta = $this->database->queryOneParams(
                "SELECT id, enunciado FROM preguntas WHERE id_categoria = ? AND estado = 'activa' ORDER BY RAND() LIMIT 1",
                [$idCategoria]
            );
        }

        if (!$pregunta) {
            return null;
        }

        $respuestas = $this->database->queryParams(
            "SELECT id, texto FROM respuestas WHERE id_pregunta = ? ORDER BY RAND()",
            [$pregunta['id']]
        );

        $pregunta['respuestas'] = $respuestas;
        return $pregunta;
    }

    public function esRespuestaCorrecta($idRespuesta)
    {
        $resultado = $this->database->queryOneParams(
            "SELECT es_correcta FROM respuestas WHERE id = ?",
            [$idRespuesta]
        );
        return $resultado && (int)$resultado['es_correcta'] === 1;
    }
// Busca si el usuario tiene una partida en curso en la BD
    public function obtenerPartidaActiva($idUsuario)
    {
        return $this->database->queryOneParams(
            "SELECT * FROM partidas WHERE id_usuario = ? AND estado = 'en_curso' LIMIT 1",
            [(int)$idUsuario]
        );
    }

    // Registra una nueva partida en estado 'en_curso'
    public function crearPartida($idUsuario)
    {
        $this->database->executeParams(
            "INSERT INTO partidas (id_usuario, id_categoria, puntaje, estado) VALUES (?, 1, 0, 'en_curso')",
            [(int)$idUsuario]
        );

        return (int)$this->database->lastInsertId();
    }

    // Actualiza el puntaje de la partida activa
    public function actualizarPuntajePartida($idPartida, $puntaje)
    {
        return $this->database->executeParams(
            "UPDATE partidas SET puntaje = ? WHERE id = ?",
            [(int)$puntaje, (int)$idPartida]
        );
    }

    // Cierra la partida activa marcándola como finalizada
    public function finalizarPartida($idPartida, $idCategoriaUltima, $puntaje, $idUsuario, $motivo = 'respuesta_incorrecta')
    {
        return $this->database->executeParams(
            "UPDATE partidas SET id_categoria = ?, puntaje = ?, estado = 'finalizada',
             motivo_finalizacion = ? WHERE id = ? AND id_usuario = ? AND estado = 'en_curso'",
            [(int)$idCategoriaUltima, (int)$puntaje, $motivo, (int)$idPartida, (int)$idUsuario]
        );
    }

    // Todas las operaciones del temporizador y las respuestas bloquean la misma fila.
    private function operarPartida($idPartida, $idUsuario, callable $operacion)
    {
        $this->database->beginTransaction();
        try {
            $partida = $this->database->queryOneParams(
                "SELECT * FROM partidas WHERE id = ? AND id_usuario = ? FOR UPDATE",
                [(int)$idPartida, (int)$idUsuario]
            );
            if ($partida) {
                $partida = $this->comprobarVencimiento($partida);
                $partida = $operacion($partida);
                $partida = $this->comprobarVencimiento($partida);
                $partida['pregunta'] = $this->obtenerPreguntaActual($partida['pregunta_actual_id']);
            }
            $this->database->commit();
            return $partida;
        } catch (\Throwable $error) {
            $this->database->rollback();
            throw $error;
        }
    }

    private function comprobarVencimiento(array $partida)
    {
        // Consulta posterior al FOR UPDATE: el reloj no queda fijado antes de esperar el bloqueo.
        $reloj = $this->database->queryOneParams(
            "SELECT GREATEST(0, TIMESTAMPDIFF(MICROSECOND, NOW(6), ?) / 1000) AS restante",
            [$partida['pregunta_vence_en']]
        );
        $partida['tiempo_restante_ms'] = (float)($reloj['restante'] ?? 0);
        if ($partida['estado'] === 'en_curso' && $partida['pregunta_vence_en'] !== null
            && $partida['tiempo_restante_ms'] <= 0) {
            $this->finalizarPartida($partida['id'], $partida['id_categoria'], $partida['puntaje'],
                $partida['id_usuario'], 'tiempo');
            $partida['estado'] = 'finalizada';
            $partida['motivo_finalizacion'] = 'tiempo';
        }
        return $partida;
    }

    private function obtenerPreguntaActual($idPregunta)
    {
        if (!$idPregunta) return null;
        $pregunta = $this->database->queryOneParams(
            "SELECT p.id, p.enunciado, c.id AS categoria_id, c.nombre AS categoria_nombre,
                    c.color AS categoria_color
             FROM preguntas p JOIN categorias c ON c.id = p.id_categoria WHERE p.id = ?",
            [(int)$idPregunta]
        );
        if ($pregunta) {
            $pregunta['respuestas'] = $this->database->queryParams(
                "SELECT id, texto FROM respuestas WHERE id_pregunta = ? ORDER BY RAND()",
                [(int)$idPregunta]
            );
        }
        return $pregunta;
    }

    public function consultarPartida($idPartida, $idUsuario)
    {
        return $this->operarPartida($idPartida, $idUsuario, function ($partida) { return $partida; });
    }

    public function prepararPregunta($idPartida, $idUsuario, array $usadas = [])
    {
        return $this->operarPartida($idPartida, $idUsuario, function ($partida) use ($idUsuario, $usadas) {
            if ($partida['estado'] !== 'en_curso' || $partida['pregunta_actual_id']) return $partida;
            $categoria = $this->obtenerCategoriaAleatoria();
            $pregunta = $this->obtenerPreguntaAleatoriaPorCategoria($categoria['id'],
                array_merge($usadas, $this->obtenerIdVistos($idUsuario)),
                $this->obtenerDificultadParaUsuario($idUsuario));
            if (!$pregunta) throw new \RuntimeException('No hay preguntas disponibles en la categoría.');
            // Preparar la ruleta no habilita ni entrega el contenido de la pregunta.
            $this->database->executeParams(
                "UPDATE partidas SET pregunta_actual_id = ?, id_categoria = ? WHERE id = ?",
                [$pregunta['id'], $categoria['id'], $partida['id']]
            );
            $partida['pregunta_actual_id'] = $pregunta['id'];
            $partida['id_categoria'] = $categoria['id'];
            return $partida;
        });
    }

    public function habilitarPregunta($idPartida, $idUsuario)
    {
        return $this->operarPartida($idPartida, $idUsuario, function ($partida) {
            if ($partida['estado'] === 'en_curso' && $partida['pregunta_actual_id']
                && $partida['pregunta_vence_en'] === null) {
                $this->database->executeParams(
                    "UPDATE partidas SET pregunta_vence_en = DATE_ADD(NOW(6), INTERVAL 15 SECOND) WHERE id = ?",
                    [$partida['id']]
                );
                $partida['pregunta_vence_en'] = $this->database->queryOneParams(
                    "SELECT pregunta_vence_en FROM partidas WHERE id = ?", [$partida['id']]
                )['pregunta_vence_en'];
            }
            return $partida;
        });
    }

    public function responderConTiempo($idPartida, $idUsuario, $idRespuesta, $idPregunta, $vencimiento)
    {
        return $this->operarPartida($idPartida, $idUsuario,
            function ($partida) use ($idRespuesta, $idPregunta, $vencimiento) {
                $partida['respuesta_aceptada'] = false;
                if ($partida['estado'] !== 'en_curso' || !$partida['pregunta_actual_id']
                    || !$partida['pregunta_vence_en']
                    || (int)$idPregunta !== (int)$partida['pregunta_actual_id']
                    || $vencimiento !== $partida['pregunta_vence_en']) return $partida;

                $respuesta = $this->database->queryOneParams(
                    "SELECT es_correcta FROM respuestas WHERE id = ? AND id_pregunta = ?",
                    [$idRespuesta, $partida['pregunta_actual_id']]
                );
                if (!$respuesta) return $partida;
                $this->marcarPreguntaComoVista($partida['id_usuario'], $partida['pregunta_actual_id']);
                $partida = $this->comprobarVencimiento($partida);
                if ($partida['estado'] !== 'en_curso') return $partida;

                if ((int)$respuesta['es_correcta'] === 1) {
                    // El propio UPDATE impide sumar si se cruza el límite durante la consulta anterior.
                    $actualizadas = $this->database->executeParams(
                        "UPDATE partidas SET puntaje = puntaje + 1, pregunta_actual_id = NULL,
                         pregunta_vence_en = NULL WHERE id = ? AND estado = 'en_curso'
                         AND pregunta_vence_en > NOW(6)", [$partida['id']]
                    );
                    if (!$actualizadas) return $this->comprobarVencimiento($partida);
                    $partida['puntaje']++;
                    $partida['pregunta_actual_id'] = null;
                    $partida['pregunta_vence_en'] = null;
                } else {
                    $this->finalizarPartida($partida['id'], $partida['id_categoria'], $partida['puntaje'],
                        $partida['id_usuario']);
                    $partida['estado'] = 'finalizada';
                    $partida['motivo_finalizacion'] = 'respuesta_incorrecta';
                }

                $this->sumarContadores($partida['id_usuario'], $idPregunta, (int)$respuesta['es_correcta'] === 1);
                $partida['respuesta_aceptada'] = true;
                return $partida;
            });
    }
    //public function guardarPartida($idUsuario, $idCategoriaUltima, $puntaje)
    //{
    //  return $this->finalizarPartida($idPartida, $idCategoriaUltima, $puntaje);
    //}

    private function sumarContadores($idUsuario, $idPregunta, $acerto)
    {
        $suma = $acerto ? 1 : 0;

        $this->database->executeParams("UPDATE preguntas 
        SET veces_respondida = veces_respondida + 1, 
        veces_acertada = veces_acertada + ? 
        WHERE id = ?", [$suma, $idPregunta]);

        $this->database->executeParams("UPDATE usuarios
        SET preguntas_respondidas = preguntas_respondidas + 1,
        preguntas_acertadas = preguntas_acertadas + ?
        WHERE id = ?", [$suma, $idUsuario]);
    }

    public function registrarRespuesta($idUsuario, $idPregunta, $acerto)
    {
       

        $this->database->beginTransaction();

        try{

            $this->sumarContadores($idUsuario, $idPregunta, $acerto);

            $this->database->commit();
            return true;
        } catch (Exception $error) {
            $this->database->rollback();
            Log::error("Error al registrar respuesta: " . $error->getMessage());

            return false;
        }
    }

    public function obtenerDificultadParaUsuario($idUsuario)
    {
        $usuario = $this->database->queryOneParams(
            "SELECT preguntas_respondidas, preguntas_acertadas
            FROM usuarios WHERE id = ?", [$idUsuario]
        );

        $respondidas = $usuario ? (int)$usuario['preguntas_respondidas'] : 0;
        $acertadas = $usuario ? (int)$usuario['preguntas_acertadas'] : 0;

        if ($respondidas < self::MINIMO_RESPUESTAS) {
            return 'media';
        }

        $porcentaje = $acertadas / $respondidas;

        if ($porcentaje > self::PORCENTAJE_FACIL) {
            return 'dificil';
        }

        if($porcentaje < self::PORCENTAJE_DIFICIL){
            return 'facil';
        }

        return 'media';
    }

    private function condicionDificultad($dificultad)
    {
        $porcentaje = "veces_acertada / veces_respondida";

        if($dificultad === "facil") {
            return["veces_respondida >= ? AND $porcentaje > ?", [self::MINIMO_RESPUESTAS, self::PORCENTAJE_FACIL]];
        }

        if($dificultad === "dificil") {
            return ["veces_respondida >= ? AND $porcentaje < ?", [self::MINIMO_RESPUESTAS, self::PORCENTAJE_DIFICIL]];
        }

        return["(veces_respondida < ? OR $porcentaje BETWEEN ? AND ?)", [self::MINIMO_RESPUESTAS, self::PORCENTAJE_DIFICIL, self::PORCENTAJE_FACIL]];
    }


    public function marcarPreguntaComoVista($idUsuario, $idPregunta)
    {
        $this->database->executeParams(
            "INSERT INTO preguntas_vistas (id_usuario, id_pregunta) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE fecha_vista = NOW()",
            [$idUsuario, $idPregunta]
        );
    }

    public function obtenerIdVistos($idUsuario)
    {
        $filas = $this->database->queryParams(
            "SELECT id_pregunta FROM preguntas_vistas
        WHERE id_usuario = ? AND fecha_vista >= NOW() - INTERVAL ? DAY",
            [$idUsuario, self::DIAS_SIN_REPETIR]
        );

        return array_column($filas, 'id_pregunta');
    }

    public function obtenerRespuestaCorrecta($idPregunta)
    {
        $resultado = $this->database->queryOneParams(
            "SELECT texto FROM respuestas WHERE id_pregunta = ? AND es_correcta = 1 LIMIT 1",
            [(int)$idPregunta]
        );

        return $resultado['texto'] ?? null;
    }
}