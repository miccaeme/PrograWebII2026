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

        return $this->database->queryOneParams(
            "SELECT id FROM partidas WHERE id_usuario = ? AND estado = 'en_curso' ORDER BY id DESC LIMIT 1",
            [(int)$idUsuario]
        )['id'] ?? null;
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
    public function finalizarPartida($idPartida, $idCategoriaUltima, $puntaje)
    {
        return $this->database->executeParams(
            "UPDATE partidas SET id_categoria = ?, puntaje = ?, estado = 'finalizada' WHERE id = ?",
            [(int)$idCategoriaUltima, (int)$puntaje, (int)$idPartida]
        );
    }
    //public function guardarPartida($idUsuario, $idCategoriaUltima, $puntaje)
    //{
      //  return $this->finalizarPartida($idPartida, $idCategoriaUltima, $puntaje);
    //}

    public function registrarRespuesta($idUsuario, $idPregunta, $acerto) 
    { 
        $suma = $acerto ? 1 : 0;

        $this->database->beginTransaction();

        try{
            $this->database->executeParams(
                "UPDATE preguntas SET veces_respondida = veces_respondida + 1,
                veces_acertada = veces_acertada + ? WHERE id = ?",
                [$suma, $idPregunta]
            );

            $this->database->executeParams(
                "UPDATE usuarios SET preguntas_respondidas = preguntas_respondidas + 1,
                preguntas_acertadas = preguntas_acertadas + ? WHERE id = ?",
                [$suma, $idUsuario]
            );

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
