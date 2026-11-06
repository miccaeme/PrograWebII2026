<?php

namespace app\model;

use Exception;
use Log;

class PartidaModel
{

    const MINIMO_RESPUESTAS = 5;
    const PORCENTAJE_FACIL = 0.7;
    const PORCENTAJE_DIFICIL = 0.3;


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

    public function obtenerPreguntaAleatoriaPorCategoria($idCategoria, $preguntasRespondidas = [], $dificultad == null)
    {
        $sql = "SELECT id, enunciado FROM preguntas WHERE id_categoria = ? AND estado = 'activa'";
        $params = [$idCategoria];

        if (!empty($preguntasRespondidas)) {
            $placeholders = implode(',', array_fill(0, count($preguntasRespondidas), '?'));
            $sql .= " AND id NOT IN ($placeholders)";
            $params = array_merge($params, $preguntasRespondidas);
        }

        // $sql .= " ORDER BY RAND() LIMIT 1";

        // $pregunta = $this->database->queryOneParams($sql, $params);

        $pregunta = null;

        if($dificultad) {
            [$condicion, $paramsDificultad] = $this->condicionDificultar($dificultad);

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

    private function condicionDificultar($dificultad)
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
}
