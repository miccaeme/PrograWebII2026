<?php

namespace app\model;

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
        $sql = "SELECT id, enunciado FROM preguntas WHERE id_categoria = ?";
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
                "SELECT id, enunciado FROM preguntas WHERE id_categoria = ? ORDER BY RAND() LIMIT 1",
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
}