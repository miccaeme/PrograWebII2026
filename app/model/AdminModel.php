<?php

namespace app\model;

class AdminModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    private function construirFiltroFecha($columnaFecha, $periodo, $fechaValor)
    {
        if (empty($fechaValor) || $periodo === 'todo') {
            return ['sql' => '', 'params' => []];
        }

        switch ($periodo) {
            case 'dia':
                // Espera YYYY-MM-DD
                return [
                    'sql'    => " AND DATE($columnaFecha) = ? ",
                    'params' => [$fechaValor]
                ];
            case 'mes':

                $mes = substr($fechaValor, 0, 7);
                return [
                    'sql'    => " AND DATE_FORMAT($columnaFecha, '%Y-%m') = ? ",
                    'params' => [$mes]
                ];
            case 'anio':
                $anio = (int)substr($fechaValor, 0, 4);
                return [
                    'sql'    => " AND YEAR($columnaFecha) = ? ",
                    'params' => [$anio]
                ];
            default:
                return ['sql' => '', 'params' => []];
        }
    }

    public function contarJugadores($periodo = 'todo', $fechaValor = null)
    {
        $filtro = $this->construirFiltroFecha('fecha_registro', $periodo, $fechaValor);
        $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'jugador'" . $filtro['sql'];

        $res = $this->database->queryOneParams($sql, $filtro['params']);
        return (int)($res['total'] ?? 0);
    }

    public function contarPartidasJugadas($periodo = 'todo', $fechaValor = null)
    {
        $filtro = $this->construirFiltroFecha('fecha_creacion', $periodo, $fechaValor);
        $sql = "SELECT COUNT(*) AS total FROM partidas WHERE estado = 'finalizada'" . $filtro['sql'];

        $res = $this->database->queryOneParams($sql, $filtro['params']);
        return (int)($res['total'] ?? 0);
    }

    public function contarPreguntas($periodo = 'todo', $fechaValor = null)
    {
        $filtro = $this->construirFiltroFecha('fecha_creacion', $periodo, $fechaValor);
        $sql = "SELECT COUNT(*) AS total FROM preguntas WHERE 1=1" . $filtro['sql'];

        $res = $this->database->queryOneParams($sql, $filtro['params']);
        return (int)($res['total'] ?? 0);
    }

    public function obtenerSerieTemporal($tabla, $columnaFecha, $condicionExtra = '1=1', $agrupacion = 'dia')
    {
        switch ($agrupacion) {
            case 'anio':
                $formato = '%Y';
                $limite = 10;
                break;
            case 'mes':
                $formato = '%Y-%m';
                $limite = 12;
                break;
            case 'dia':
            default:
                $formato = '%Y-%m-%d';
                $limite = 15;
                break;
        }

        $sql = "SELECT DATE_FORMAT($columnaFecha, '$formato') AS etiqueta, COUNT(*) AS total
                FROM $tabla
                WHERE $condicionExtra AND $columnaFecha IS NOT NULL
                GROUP BY etiqueta
                ORDER BY etiqueta DESC
                LIMIT $limite";


        $filas = $this->database->queryParams($sql, []) ?? [];
        return array_reverse($filas);
    }
    public function obtenerPreguntasPorCategoria($periodo = 'todo', $fechaValor = null)
    {
        $filtro = $this->construirFiltroFecha('p.fecha_creacion', $periodo, $fechaValor);
        $sql = "SELECT c.nombre AS categoria, c.color, COUNT(p.id) AS total
                FROM categorias c
                LEFT JOIN preguntas p ON p.id_categoria = c.id " . $filtro['sql'] . "
                GROUP BY c.id, c.nombre, c.color
                ORDER BY total DESC";

        return $this->database->queryParams($sql, $filtro['params']) ?? [];
    }
}