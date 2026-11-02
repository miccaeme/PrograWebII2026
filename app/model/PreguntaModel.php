<?php

namespace app\model;

use Exception;
use Log;

class PreguntaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerCategorias()
    {
        return $this->database->queryAll("SELECT id, nombre, color, icono
        FROM categorias ORDER BY nombre");
    }

    public function validar($enunciado, $idCategoria, $respuestas, $idExcluir = 0)
    {
        $errores = [];

        if($this->existeEnunciado($enunciado, $idExcluir)) {
            $errores["enunciado"] = "Esa pregunta ya existe.";
        }

        if(!$this->existeCategoria($idCategoria)) {
            $errores["categoria"] = "Elegí una categoría válida";
        }

        $enMinusculas = array_map("mb_strtolower", $respuestas);
        $sinRepetidos = array_unique($enMinusculas);

        if(count($sinRepetidos) !== count($respuestas)){
            $errores["respuestas"] = "Las respuestas no pueden repetirse.";
        }

        return $errores;
    }

    public function existeEnunciado($enunciado, $idExcluir = 0)
    {
        $fila = $this->database->queryOneParams("SELECT id FROM preguntas 
    WHERE enunciado = ? AND estado <> 'rechazada' AND id <> ?", [$enunciado, $idExcluir]);

        return $fila !== null;
    }

    public function existeCategoria($idCategoria)
    {
        $fila = $this->database->queryOneParams("SELECT id FROM categorias
        WHERE id = ?", [$idCategoria]);
    
        return $fila !== null;
    }

    public function guardar($enunciado, $idCategoria, $respuestas, $correcta, $idUsuario, $estado)
    {
        $this->database->beginTransaction();

        try {
            $this->database->executeParams(
                "INSERT INTO preguntas (enunciado, id_categoria, estado, id_usuario_creador) VALUES (?, ?, ?, ?)", [$enunciado, $idCategoria, $estado, $idUsuario]);
                $idPregunta = $this->database->lastInsertId();
            
                foreach($respuestas as $posicion => $texto) {
                    $this->database->executeParams("INSERT INTO respuestas (id_pregunta, texto, es_correcta) VALUES (?, ?, ?)",
                    [$idPregunta, $texto, $posicion === (int)$correcta ? 1 : 0]);
                }

                $this->database->commit();
                return true;
        } catch (Exception $error) {
            $this->database->rollback();
            Log::error("Error al guardar pregunta: " . $error->getMessage());
            
            return false; 
        } 
    }

    public function obtenerPendientes(){
        return $this->database->queryAll("SELECT p.id, p.enunciado, p.fecha_creacion,
        c.nombre AS categoria, c.color, c.icono,
        u.nombre_usuario AS creador
        FROM preguntas p
        INNER JOIN categorias c ON p.id_categoria = c.id
        LEFT JOIN usuarios u ON p.id_usuario_creador = u.id
        WHERE p.estado = 'pendiente'
        ORDER BY p.fecha_creacion ASC");
    }

    public function cambiarEstado($idPregunta, $estadoNuevo)
    {
        $estadoRequerido = [
            "activa"    => "pendiente",
            "rechazada" => "pendiente",
            "inactiva"  => "activa"
        ];

        if (!isset($estadoRequerido[$estadoNuevo])) {
            return false;
        }

        return $this->database->executeParams(
            "UPDATE preguntas SET estado = ? WHERE id = ? AND estado = ?",
            [$estadoNuevo, $idPregunta, $estadoRequerido[$estadoNuevo]]
        );
    }

    public function obtenerPorCreador($idUsuario)
    {
        return $this->database->queryParams(
            "SELECT p.id, p.enunciado, p.estado, p.fecha_creacion, c.nombre AS categoria
        FROM preguntas p
        INNER JOIN categorias c ON p.id_categoria = c.id
        WHERE p.id_usuario_creador = ?
        ORDER BY p.fecha_creacion DESC",
            [$idUsuario]
        );
    }

    public function obtenerRespuestas($idPregunta)
    {
        $respuestas = $this->database->queryParams(
            "SELECT texto, es_correcta FROM respuestas WHERE id_pregunta = ? ORDER BY id",
            [$idPregunta]
        );

        foreach ($respuestas as $i => $r) {
            $respuestas[$i]["es_correcta"] = (int)$r["es_correcta"] === 1;
        }

        return $respuestas;
    }

    public function obtenerActivas()
    {
        return $this->database->queryAll("SELECT p.id, p.enunciado, p.fecha_creacion,
        c.nombre AS categoria,
        u.nombre_usuario AS creador
        FROM preguntas p
        INNER JOIN categorias c ON p.id_categoria = c.id
        LEFT JOIN usuarios u ON p.id_usuario_creador = u.id
        WHERE p.estado = 'activa'
        ORDER BY p.fecha_creacion DESC");
    }

    public function obtenerPorId($idPregunta)
    {
        $pregunta = $this->database->queryOneParams(
            "SELECT id, enunciado, id_categoria, estado FROM preguntas WHERE id = ?",
            [$idPregunta]
        );

        if ($pregunta === null) {
            return null;
        }

        $pregunta["respuestas"] = $this->database->queryParams(
            "SELECT texto, es_correcta FROM respuestas WHERE id_pregunta = ? ORDER BY id",
            [$idPregunta]
        );

        return $pregunta;
    }

    public function actualizar($idPregunta, $enunciado, $idCategoria, $respuestas, $correcta)
    {
        $this->database->beginTransaction();

        try {
            $this->database->executeParams(
                "UPDATE preguntas SET enunciado = ?, id_categoria = ? WHERE id = ?",
                [$enunciado, $idCategoria, $idPregunta]
            );

            $this->database->executeParams(
                "DELETE FROM respuestas WHERE id_pregunta = ?",
                [$idPregunta]
            );

            foreach ($respuestas as $posicion => $texto) {
                $this->database->executeParams(
                    "INSERT INTO respuestas (id_pregunta, texto, es_correcta) VALUES (?, ?, ?)",
                    [$idPregunta, $texto, $posicion === (int)$correcta ? 1 : 0]
                );
            }

            $this->database->commit();
            return true;
        } catch (Exception $error) {
            $this->database->rollback();
            Log::error("Error al actualizar pregunta: " . $error->getMessage());

            return false;
        }
    }




}