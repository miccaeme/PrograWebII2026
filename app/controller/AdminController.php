<?php

namespace app\controller;


use Request;

class AdminController
{
    private $model;
    private $render;

    private const POR_PAGINA = 5;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }


    public function show()
    {
        $baseUrl = $_ENV['BASE_URL'] ?? '/PrograWebII2026';

        $data = [
            "logueado" => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "Admin"
        ];

        $this->render->renderiza("admin", $data);
    }

    // Guarda los filtros en sesión y redirige a /admin/dashboard (URL limpia)
    public function filtrar()
    {


        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $periodo = Request::post('periodo', 'todo');
            $fecha = Request::post('fecha', date('Y-m-d'));
            $agrupar = Request::post('agrupar', 'dia');

            $_SESSION['admin_filtros'] = [
                'periodo' => in_array($periodo, ['todo', 'dia', 'mes', 'anio'], true) ? $periodo : 'todo',
                'fecha' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : date('Y-m-d'),
                'agrupar' => in_array($agrupar, ['dia', 'mes', 'anio'], true) ? $agrupar : 'dia'
            ];
        }

        header("Location: " . \UrlHelper::controllerUrl('admin', 'dashboard'));
        exit();
    }

    public function limpiarFiltros()
    {

        unset($_SESSION['admin_filtros']);
        header("Location: " . \UrlHelper::controllerUrl('admin', 'dashboard'));
        exit();
    }

    // Pantalla de Métricas (adminDashboardView.mustache)
    public function dashboard()
    {

        $baseUrl = $_ENV['BASE_URL'] ?? '/PrograWebII2026';

        $filtros = $_SESSION['admin_filtros'] ?? [
            'periodo' => 'todo',
            'fecha' => date('Y-m-d'),
            'agrupar' => 'dia'
        ];

        $periodo = $filtros['periodo'];
        $fecha = $filtros['fecha'];
        $agrupar = $filtros['agrupar'];

        $totalJugadores = $this->model->contarJugadores($periodo, $fecha);
        $totalPartidas = $this->model->contarPartidasJugadas($periodo, $fecha);
        $totalPreguntas = $this->model->contarPreguntas($periodo, $fecha);

        $seriePartidas = $this->model->obtenerSerieTemporal('partidas', 'fecha_creacion', "estado = 'finalizada'", $agrupar);
        $serieJugadores = $this->model->obtenerSerieTemporal('usuarios', 'fecha_registro', "rol = 'jugador'", $agrupar);
        $porCategoria = $this->model->obtenerPreguntasPorCategoria($periodo, $fecha);
        $condicionPartidas = "estado = 'finalizada'";
        $condicionJugadores = "rol = 'jugador'";

        $pagPartidas = $this->datosPaginado($this->model->contarSerieTemporal('partidas', 'fecha_creacion', $condicionPartidas, $agrupar), 'pagina_partidas');
        $pagJugadores = $this->datosPaginado($this->model->contarSerieTemporal('usuarios', 'fecha_registro', $condicionJugadores, $agrupar), 'pagina_jugadores');
        $pagUsuarios = $this->datosPaginado($this->model->contarUsuariosConPartidas(), 'pagina_usuarios');

        $tablaPartidas = $this->model->obtenerSerieTemporalPaginada('partidas', 'fecha_creacion', $condicionPartidas, $agrupar, self::POR_PAGINA, $pagPartidas['offset']);
        $tablaJugadores = $this->model->obtenerSerieTemporalPaginada('usuarios', 'fecha_registro', $condicionJugadores, $agrupar, self::POR_PAGINA, $pagJugadores['offset']);
        $tablaUsuarios = $this->model->obtenerCorrectasPorUsuario(self::POR_PAGINA, $pagUsuarios['offset']);

        $etiquetas = [
            'todo' => 'Reporte histórico completo',
            'dia' => 'Reporte del día ' . $fecha,
            'mes' => 'Reporte del mes ' . substr($fecha, 0, 7),
            'anio' => 'Reporte del año ' . substr($fecha, 0, 4),
        ];
        $tituloReporte = $etiquetas[$periodo] ?? 'Reporte';

        $data = [
            "logueado" => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "Admin",
            "total_jugadores" => $totalJugadores,
            "total_partidas" => $totalPartidas,
            "total_preguntas" => $totalPreguntas,
            "fecha_actual" => $fecha,
            "es_todo" => $periodo === 'todo',
            "es_dia" => $periodo === 'dia',
            "es_mes" => $periodo === 'mes',
            "es_anio" => $periodo === 'anio',
            "agrupar_dia" => $agrupar === 'dia',
            "agrupar_mes" => $agrupar === 'mes',
            "agrupar_anio" => $agrupar === 'anio',
            "tabla_partidas"    => $tablaPartidas,
            "tabla_jugadores"   => $tablaJugadores,
            "tabla_categorias" => $porCategoria,
            "por_pais" => $this->model->obtenerUsuariosPorPais($periodo, $fecha),
            "por_sexo" => $this->model->obtenerUsuariosPorSexo($periodo, $fecha),
            "por_edad" => $this->model->obtenerUsuariosPorEdad($periodo, $fecha),
            "titulo_reporte" => $tituloReporte,
            "partidas_labels" => json_encode(array_column($seriePartidas, 'etiqueta')),
            "partidas_values" => json_encode(array_map('intval', array_column($seriePartidas, 'total'))),
            "jugadores_labels" => json_encode(array_column($serieJugadores, 'etiqueta')),
            "jugadores_values" => json_encode(array_map('intval', array_column($serieJugadores, 'total'))),
            "categorias_labels" => json_encode(array_column($porCategoria, 'categoria')),
            "categorias_values" => json_encode(array_map('intval', array_column($porCategoria, 'total'))),
            "categorias_colors" => json_encode(array_column($porCategoria, 'color')),
            "por_usuario" => $tablaUsuarios,
            "pag_partidas" => $pagPartidas,
            "pag_jugadores" => $pagJugadores,
            "pag_usuarios" => $pagUsuarios,
            "pagina_partidas" => $pagPartidas['pagina'],
            "pagina_jugadores" => $pagJugadores['pagina'],
            "pagina_usuarios" => $pagUsuarios['pagina'],
        ];

        $this->render->renderiza("adminDashboard", $data);
    }

    private function datosPaginado($total, $parametro)
    {
        $totalPaginas = max(1, (int)ceil($total / self::POR_PAGINA));
        $pagina = (int)Request::get($parametro, 1);
        $pagina = min(max(1, $pagina), $totalPaginas);

        return [
            "pagina" => $pagina,
            "offset" => ($pagina - 1) * self::POR_PAGINA,
            "total_paginas" => $totalPaginas,
            "hay_paginas" => $totalPaginas > 1,
            "hay_anterior" => $pagina > 1,
            "hay_siguiente" => $pagina < $totalPaginas,
            "pagina_anterior" => $pagina - 1,
            "pagina_siguiente" => $pagina + 1
        ];
    }
}