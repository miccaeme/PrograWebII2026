<?php

namespace app\model;

class CategoriaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function listar()
    {
        return $this->database->queryAll(
            "SELECT id, nombre, descripcion, color, icono, activa FROM categorias ORDER BY activa DESC, nombre"
        );
    }

    public function buscar($id)
    {
        return $this->database->queryOneParams(
            "SELECT id, nombre, descripcion, color, icono, activa FROM categorias WHERE id = ?",
            [$id]
        );
    }

    public function guardar($datos, $id)
    {
        $descripcion = $datos["descripcion"] === "" ? null : $datos["descripcion"];
        $valores = [$datos["nombre"], $descripcion, $datos["color"]];
        if ($id === null) {
            $this->database->executeParams(
                "INSERT INTO categorias (nombre, descripcion, color) VALUES (?, ?, ?)",
                $valores
            );
            return;
        }

        $this->database->executeParams(
            "UPDATE categorias SET nombre = ?, descripcion = ?, color = ? WHERE id = ?",
            array_merge($valores, [$id])
        );
    }

    public function darDeBaja($id)
    {
        $this->database->executeParams("UPDATE categorias SET activa = 0 WHERE id = ? AND activa = 1", [$id]);
    }

    public function reactivar($id)
    {
        $this->database->executeParams("UPDATE categorias SET activa = 1 WHERE id = ? AND activa = 0", [$id]);
    }
}
