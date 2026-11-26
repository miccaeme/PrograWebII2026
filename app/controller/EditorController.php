<?php

namespace app\controller;

use Redirect;
use Request;

class EditorController
{

    private $model;
    private $categoriaModel;
    private $render;

    private const POR_PAGINA = 3;
    public function __construct($model, $categoriaModel, $render)
    {
        $this->model = $model;
        $this->categoriaModel = $categoriaModel;
        $this->render = $render;
    }

    public function show()
    {

        $preguntas = $this->model->obtenerPendientes();

        foreach ($preguntas as $i => $p) {
            $preguntas[$i]["respuestas"] = $this->model->obtenerRespuestas($p["id"]);
        }

        $this->render->renderiza("editor", [
            "logueado" => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "",
            "preguntas" => $preguntas,
            "sin_pendientes" => empty($preguntas)
        ]);
    }

    public function categorias()
    {

        Redirect::toController("categoria", Request::get("crear") === "1" ? "crear" : "show");
    }

    public function aprobar()
    {
        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("editor", "show");
        }

        $id = Request::post("id");
        $this->model->cambiarEstado($id, "activa");

        Redirect::toController("editor", "show");
    }

    public function rechazar()
    {

        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("editor", "show");
        }

        $id = Request::post("id");
        $this->model->cambiarEstado($id, "rechazada");

        Redirect::toController("editor", "show");
    }

    public function activas()
    {

        $busqueda = trim((string) Request::get("q", ""));
        $idCategoria = (int) Request::get("categoria", 0);

        $paginado = $this->datosPaginado($this->model->contarActivas($busqueda, $idCategoria));
        $preguntas = $this->model->obtenerActivas(self::POR_PAGINA, $paginado["offset"], $busqueda, $idCategoria);

        foreach ($preguntas as $i => $p) {
            $preguntas[$i]["respuestas"] = $this->model->obtenerRespuestas($p["id"]);
        }

        $this->render->renderiza("editorActivas", array_merge($paginado, $this->datosFiltros($busqueda, $idCategoria, "activas"), [
            "logueado" => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "",
            "preguntas" => $preguntas,
            "sin_resultados" => empty($preguntas)
        ]));
    }

    public function inactivas()
    {

        $busqueda = trim((string) Request::get("q", ""));
        $idCategoria = (int) Request::get("categoria", 0);

        $paginado = $this->datosPaginado($this->model->contarInactivas($busqueda, $idCategoria));
        $preguntas = $this->model->obtenerInactivas(self::POR_PAGINA, $paginado["offset"], $busqueda, $idCategoria);

        foreach ($preguntas as $i => $p) {
            $preguntas[$i]["respuestas"] = $this->model->obtenerRespuestas($p["id"]);
        }

        $this->render->renderiza("editorInactivas", array_merge($paginado, $this->datosFiltros($busqueda, $idCategoria, "inactivas"), [
            "logueado" => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "",
            "preguntas" => $preguntas,
            "sin_inactivas" => empty($preguntas)
        ]));
    }

    private function datosFiltros($busqueda, $idCategoria, $pestana)
    {
        $categorias = $this->categoriaModel->listar();
        foreach ($categorias as $i => $categoria) {
            $categorias[$i]["seleccionada"] = (int) $categoria["id"] === $idCategoria;
            $categorias[$i]["desactivada"] = (int) $categoria["activa"] === 0;
        }

        return [
            "categorias" => $categorias,
            "busqueda" => $busqueda,
            "busqueda_url" => urlencode($busqueda),
            "categoria_actual" => $idCategoria,
            "hay_filtros" => $busqueda !== "" || $idCategoria > 0,
            "pestana_filtros" => $pestana
        ];
    }

    public function baja()
    {

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("editor", "activas");
        }

        $this->model->cambiarEstado(Request::post("id"), "inactiva");

        Redirect::toController("editor", "activas");
    }

    public function reactivar()
    {

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("editor", "inactivas");
        }

        $this->model->cambiarEstado(Request::post("id"), "activa");

        Redirect::toController("editor", "inactivas");
    }

    public function reportadas()
    {
      

        $preguntas = $this->model->obtenerReportadas();

        foreach ($preguntas as $i => $p) {
            $preguntas[$i]["respuestas"] = $this->model->obtenerRespuestas($p["id"]);
        }

        $this->render->renderiza("editorReportadas", [
            "logueado" => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "",
            "preguntas" => $preguntas,
            "sin_reportadas" => empty($preguntas)
        ]);
    }

    public function mantener()
    {

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("editor", "reportadas");
        }

        $this->model->resolverReportes(Request::post("id"));

        Redirect::toController("editor", "reportadas");
    }

    public function bajaReportada()
    {

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("editor", "reportadas");
        }

        $id = Request::post("id");
        $this->model->cambiarEstado($id, "inactiva");
        $this->model->resolverReportes($id);

        Redirect::toController("editor", "reportadas");
    }

    private function datosPaginado($total)
    {
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = (int) Request::get("pagina", 1);
        $pagina = min(max(1, $pagina), $totalPaginas);

        return [
            "pagina"           => $pagina,
            "offset"           => ($pagina - 1) * self::POR_PAGINA,
            "total_paginas"    => $totalPaginas,
            "hay_paginas"      => $totalPaginas > 1,
            "hay_anterior"     => $pagina > 1,
            "hay_siguiente"    => $pagina < $totalPaginas,
            "pagina_anterior"  => $pagina - 1,
            "pagina_siguiente" => $pagina + 1
        ];
    }


}
