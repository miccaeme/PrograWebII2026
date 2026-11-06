<?php

namespace app\model;

use Exception;
use Log;

class PartidaModel
{
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

    public function obtenerPreguntaAleatoriaPorCategoria($idCategoria, $preguntasRespondidas = [])
    {
        $sql = "SELECT id, enunciado FROM preguntas WHERE id_categoria = ? AND estado = 'activa'";
        $params = [$idCategoria];

        if (!empty($preguntasRespondidas)) {
            $placeholders = implode(',', array_fill(0, count($preguntasRespondidas), '?'));
            $sql .= " AND id NOT IN ($placeholders)";
            $params = array_merge($params, $preguntasRespondidas);
        }

        $sql .= " ORDER BY RAND() LIMIT 1";

        $pregunta = $this->database->queryOneParams($sql, $params);

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

    public function guardarPartida($idUsuario, $idCategoriaUltima, $puntaje)
    {
        return $this->database->executeParams(
            "INSERT INTO partidas (id_usuario, id_categoria, puntaje) VALUES (?, ?, ?)",
            [$idUsuario, $idCategoriaUltima, $puntaje]
        );
    }

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
}