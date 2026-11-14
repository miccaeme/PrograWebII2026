<?php

namespace app\controller;

use Auth;
use Request;

class AdminController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    private function autenticar()
    {
        Auth::requiereRol(["admin"]);
    }

    public function show()
    {
        $this->autenticar();
        $baseUrl = $_ENV['BASE_URL'] ?? '/PrograWebII2026';

        $data = [
            "logueado"       => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "Admin"
        ];

        $this->render->renderiza("admin", $data);
    }

    // Guarda los filtros en sesión y redirige a /admin/dashboard (URL limpia)
    public function filtrar()
    {
        $this->autenticar();

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $periodo = Request::post('periodo', 'todo');
            $fecha   = Request::post('fecha', date('Y-m-d'));
            $agrupar = Request::post('agrupar', 'dia');

            $_SESSION['admin_filtros'] = [
                'periodo' => in_array($periodo, ['todo', 'dia', 'mes', 'anio'], true) ? $periodo : 'todo',
                'fecha'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : date('Y-m-d'),
                'agrupar' => in_array($agrupar, ['dia', 'mes', 'anio'], true) ? $agrupar : 'dia'
            ];
        }

        header("Location: " . \UrlHelper::controllerUrl('admin', 'dashboard'));
        exit();
    }

    public function limpiarFiltros()
    {
        $this->autenticar();
        unset($_SESSION['admin_filtros']);
        header("Location: " . \UrlHelper::controllerUrl('admin', 'dashboard'));
        exit();
    }

    // Pantalla de Métricas (adminDashboardView.mustache)
    public function dashboard()
    {
        $this->autenticar();

        $baseUrl = $_ENV['BASE_URL'] ?? '/PrograWebII2026';

        $filtros = $_SESSION['admin_filtros'] ?? [
            'periodo' => 'todo',
            'fecha'   => date('Y-m-d'),
            'agrupar' => 'dia'
        ];

        $periodo = $filtros['periodo'];
        $fecha   = $filtros['fecha'];
        $agrupar = $filtros['agrupar'];

        $totalJugadores = $this->model->contarJugadores($periodo, $fecha);
        $totalPartidas  = $this->model->contarPartidasJugadas($periodo, $fecha);
        $totalPreguntas = $this->model->contarPreguntas($periodo, $fecha);

        $seriePartidas  = $this->model->obtenerSerieTemporal('partidas', 'fecha_creacion', "estado = 'finalizada'", $agrupar);
        $serieJugadores = $this->model->obtenerSerieTemporal('usuarios', 'fecha_registro', "rol = 'jugador'", $agrupar);
        $porCategoria   = $this->model->obtenerPreguntasPorCategoria($periodo, $fecha);

        $data = [
            "logueado"          => true,
            "nombre_usuario"    => $_SESSION["nombre_usuario"] ?? "Admin",
            "total_jugadores"   => $totalJugadores,
            "total_partidas"    => $totalPartidas,
            "total_preguntas"   => $totalPreguntas,
            "fecha_actual"      => $fecha,
            "es_todo"           => $periodo === 'todo',
            "es_dia"            => $periodo === 'dia',
            "es_mes"            => $periodo === 'mes',
            "es_anio"           => $periodo === 'anio',
            "agrupar_dia"       => $agrupar === 'dia',
            "agrupar_mes"       => $agrupar === 'mes',
            "agrupar_anio"      => $agrupar === 'anio',
            "partidas_labels"   => json_encode(array_column($seriePartidas, 'etiqueta')),
            "partidas_values"   => json_encode(array_map('intval', array_column($seriePartidas, 'total'))),
            "jugadores_labels"  => json_encode(array_column($serieJugadores, 'etiqueta')),
            "jugadores_values"  => json_encode(array_map('intval', array_column($serieJugadores, 'total'))),
            "categorias_labels" => json_encode(array_column($porCategoria, 'categoria')),
            "categorias_values" => json_encode(array_map('intval', array_column($porCategoria, 'total'))),
            "categorias_colors" => json_encode(array_column($porCategoria, 'color'))
        ];

        $this->render->renderiza("adminDashboard", $data);
    }
}