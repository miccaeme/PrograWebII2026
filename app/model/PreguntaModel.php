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

    public function obtenerCategorias($idPregunta = 0)
    {
        return $this->database->queryParams(
            "SELECT id, nombre, color, icono FROM categorias
             WHERE activa = 1 OR id = (SELECT id_categoria FROM preguntas WHERE id = ?)
             ORDER BY nombre",
            [$idPregunta]
        );
    }

    public function validar($enunciado, $idCategoria, $respuestas, $idExcluir = 0)
    {
        $errores = [];

        if($this->existeEnunciado($enunciado, $idExcluir)) {
            $errores["enunciado"] = "Esa pregunta ya existe.";
        }

        if(!$this->existeCategoria($idCategoria, $idExcluir)) {
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

    public function existeCategoria($idCategoria, $idPregunta = 0, $bloquear = false)
    {
        $sql = "SELECT id FROM categorias WHERE id = ?
                AND (activa = 1 OR id = (SELECT id_categoria FROM preguntas WHERE id = ?))";
        $fila = $this->database->queryOneParams($sql . ($bloquear ? " FOR UPDATE" : ""), [$idCategoria, $idPregunta]);
    
        return $fila !== null;
    }

    public function guardar($enunciado, $idCategoria, $respuestas, $correcta, $idUsuario, $estado)
    {
        $this->database->beginTransaction();

        try {
            if (!$this->existeCategoria($idCategoria, 0, true)) {
                $this->database->rollback();
                return false;
            }
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
            "activa"    => ["pendiente", "inactiva"],
            "rechazada" => ["pendiente"],
            "inactiva"  => ["activa"]
        ];

        if (!isset($estadoRequerido[$estadoNuevo])) {
            return false;
        }

        $origenes = $estadoRequerido[$estadoNuevo];
        $placeholders = implode(',', array_fill(0, count($origenes), '?'));

        return $this->database->executeParams(
            "UPDATE preguntas SET estado = ? WHERE id = ? AND estado IN ($placeholders)",
            array_merge([$estadoNuevo, $idPregunta], $origenes)
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

    public function obtenerActivas($limite = 10, $offset = 0, $busqueda = '', $idCategoria = 0)
    {
        $sql = "SELECT p.id, p.enunciado, p.fecha_creacion,
            c.nombre AS categoria,
            u.nombre_usuario AS creador
            FROM preguntas p
            INNER JOIN categorias c ON p.id_categoria = c.id
            LEFT JOIN usuarios u ON p.id_usuario_creador = u.id
            WHERE p.estado = 'activa'";
        $params = [];

        if ($busqueda !== '') {
            $sql .= " AND p.enunciado LIKE ?";
            $params[] = '%' . $busqueda . '%';
        }
        if ($idCategoria > 0) {
            $sql .= " AND p.id_categoria = ?";
            $params[] = $idCategoria;
        }

        $sql .= " ORDER BY p.fecha_creacion DESC LIMIT " . (int)$limite . " OFFSET " . (int)$offset;
        return $this->database->queryParams($sql, $params);
    }

    public function contarActivas($busqueda = '', $idCategoria = 0)
    {
        $sql = "SELECT COUNT(*) AS total FROM preguntas p WHERE p.estado = 'activa'";
        $params = [];

        if ($busqueda !== '') {
            $sql .= " AND p.enunciado LIKE ?";
            $params[] = '%' . $busqueda . '%';
        }
        if ($idCategoria > 0) {
            $sql .= " AND p.id_categoria = ?";
            $params[] = $idCategoria;
        }

        $fila = $this->database->queryOneParams($sql, $params);
        return (int)($fila['total'] ?? 0);
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
            if (!$this->existeCategoria($idCategoria, $idPregunta, true)) {
                $this->database->rollback();
                return false;
            }
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

    public function obtenerInactivas($limite = 10, $offset = 0)
    {
        return $this->database->queryAll("SELECT p.id, p.enunciado, p.fecha_creacion,
    c.nombre AS categoria,
    u.nombre_usuario AS creador
    FROM preguntas p
    INNER JOIN categorias c ON p.id_categoria = c.id
    LEFT JOIN usuarios u ON p.id_usuario_creador = u.id
    WHERE p.estado = 'inactiva'
    ORDER BY p.fecha_creacion DESC
    LIMIT " . (int)$limite . " OFFSET " . (int)$offset);
    }

    public function contarInactivas()
    {
        $fila = $this->database->queryOne("SELECT COUNT(*) AS total FROM preguntas WHERE estado = 'inactiva'");
        return (int)($fila['total'] ?? 0);
    }

    public function reportar($idUsuario, $idPregunta, $motivo)
    {
        return $this->database->executeParams(
            "INSERT INTO reportes_preguntas (id_pregunta, id_usuario, motivo) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
            fecha    = IF(resuelto = 1, NOW(), fecha),
            motivo   = IF(resuelto = 1, VALUES(motivo), motivo),
            resuelto = 0",
            [$idPregunta, $idUsuario, $motivo]
        );
    }

    public function obtenerReportadas()
    {
        return $this->database->queryAll("SELECT p.id, p.enunciado, p.fecha_creacion,
    c.nombre AS categoria,
    u.nombre_usuario AS creador,
    COUNT(r.id) AS cantidad_reportes,
    GROUP_CONCAT(DISTINCT r.motivo SEPARATOR ' / ') AS motivos
    FROM preguntas p
    INNER JOIN reportes_preguntas r ON r.id_pregunta = p.id
    INNER JOIN categorias c ON p.id_categoria = c.id
    LEFT JOIN usuarios u ON p.id_usuario_creador = u.id
    WHERE r.resuelto = 0 AND p.estado = 'activa'
    GROUP BY p.id, p.enunciado, p.fecha_creacion, c.nombre, u.nombre_usuario
    ORDER BY cantidad_reportes DESC");
    }


    public function resolverReportes($idPregunta)
    {
        return $this->database->executeParams(
            "UPDATE reportes_preguntas SET resuelto = 1 WHERE id_pregunta = ?",
            [$idPregunta]
        );
    }



}
