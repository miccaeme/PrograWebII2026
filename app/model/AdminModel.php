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
                    'sql' => " AND DATE($columnaFecha) = ? ",
                    'params' => [$fechaValor]
                ];
            case 'mes':

                $mes = substr($fechaValor, 0, 7);
                return [
                    'sql' => " AND DATE_FORMAT($columnaFecha, '%Y-%m') = ? ",
                    'params' => [$mes]
                ];
            case 'anio':
                $anio = (int)substr($fechaValor, 0, 4);
                return [
                    'sql' => " AND YEAR($columnaFecha) = ? ",
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

    public function obtenerUsuariosPorPais($periodo, $fecha)
    {

        $filtro = $this->construirFiltroFecha('fecha_registro', $periodo, $fecha);
        $sql = "SELECT pais AS grupo, COUNT(*) AS total
                FROM usuarios
                WHERE rol = 'jugador' {$filtro['sql']}
                GROUP BY pais
                ORDER BY total DESC";
        return $this->database->queryParams($sql, $filtro['params']) ?? [];
    }

    public function obtenerUsuariosPorSexo($periodo, $fecha){
        $filtro = $this->construirFiltroFecha('fecha_registro', $periodo, $fecha);
        $sql = "SELECT CASE sexo
                WHEN 'M' THEN 'Masculino'
                WHEN 'F' THEN 'Femenino'
                ELSE 'Prefiere no decirlo'
                END AS grupo,
                COUNT(*) AS total
                FROM usuarios
                WHERE rol = 'jugador' {$filtro['sql']}
                GROUP BY sexo";
        $filas = $this->database->queryParams($sql, $filtro['params']) ?? [];
        return $this->completarGrupos($filas, ['Masculino', 'Femenino', 'Prefiere no decirlo']);
    }

    public function obtenerUsuariosPorEdad($periodo, $fecha){
        $filtro = $this->construirFiltroFecha('fecha_registro', $periodo, $fecha);
       $sql = "SELECT CASE
                WHEN YEAR(CURDATE()) - anio_nacimiento < 18 THEN 'Menores'
                WHEN YEAR(CURDATE()) - anio_nacimiento >= 65 THEN 'Jubilados'
                ELSE 'Medio'
                END AS grupo,
                COUNT(*) AS total
                FROM usuarios
                WHERE rol = 'jugador' {$filtro['sql']}
                GROUP BY grupo";
        $filas = $this->database->queryParams($sql, $filtro['params']) ?? [];
        return $this->completarGrupos($filas, ['Menores', 'Medio', 'Jubilados']);
    }

    //metodo para que los grupos que no existan aparezcan en el dashboard con 0.
    //ej: si no hay jubilados, sin este metodo no aparecian en los reportes, ahora aparece jubilado: 0
    private function completarGrupos($filas, $grupos)
    {
        $totales = array_column($filas, 'total', 'grupo');
        $resultado = [];
        foreach ($grupos as $grupo) {
            $resultado[] = ['grupo' => $grupo, 'total' => $totales[$grupo] ?? 0];
        }
        return $resultado;
    }

    private function formatoAgrupacion($agrupacion)
    {
        switch ($agrupacion) {
            case 'anio':
                return '%Y';
            case 'mes':
                return '%Y-%m';
            default:
                return '%Y-%m-%d';
        }
    }

    public function contarSerieTemporal($tabla, $columnaFecha, $condicionExtra = '1=1', $agrupacion = 'dia')
    {
        $formato = $this->formatoAgrupacion($agrupacion);
        $sql = "SELECT COUNT(DISTINCT DATE_FORMAT($columnaFecha, '$formato')) AS total
                FROM $tabla
                WHERE $condicionExtra AND $columnaFecha IS NOT NULL";
        $res = $this->database->queryOneParams($sql, []);
        return (int)($res['total'] ?? 0);
    }

    public function obtenerSerieTemporalPaginada($tabla, $columnaFecha, $condicionExtra, $agrupacion, $limite, $offset)
    {
        $formato = $this->formatoAgrupacion($agrupacion);
        $sql = "SELECT DATE_FORMAT($columnaFecha, '$formato') AS etiqueta, COUNT(*) AS total
                FROM $tabla
                WHERE $condicionExtra AND $columnaFecha IS NOT NULL
                GROUP BY etiqueta
                ORDER BY etiqueta DESC
                LIMIT " . (int)$limite . " OFFSET " . (int)$offset;
        return $this->database->queryParams($sql, []) ?? [];
    }

    public function contarUsuariosConPartidas()
    {
        $sql = "SELECT COUNT(DISTINCT u.id) AS total
                FROM usuarios u
                INNER JOIN partidas p ON p.id_usuario = u.id AND p.estado = 'finalizada'
                WHERE u.rol = 'jugador'";
        $res = $this->database->queryOneParams($sql, []);
        return (int)($res['total'] ?? 0);
    }

    public function obtenerCorrectasPorUsuario($limite, $offset)
    {
        $sql = "SELECT u.nombre_usuario AS usuario,
                   SUM(p.puntaje) AS correctas,
                   SUM(p.puntaje) + COUNT(p.id) AS respondidas,
                   ROUND(SUM(p.puntaje) * 100 / (SUM(p.puntaje) + COUNT(p.id)), 1) AS porcentaje
            FROM usuarios u
            INNER JOIN partidas p ON p.id_usuario = u.id AND p.estado = 'finalizada'
            WHERE u.rol = 'jugador'
            GROUP BY u.id, u.nombre_usuario
            ORDER BY porcentaje DESC, respondidas DESC, u.id ASC
            LIMIT " . (int)$limite . " OFFSET " . (int)$offset;
        return $this->database->queryAll($sql) ?? [];
    }
}