<?php

namespace app\controller;


use PDOException;
use Redirect;
use Request;
use UrlHelper;

class CategoriaController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    public function show()
    {
        $nombreReactivada = $_SESSION["nombre_categoria_reactivada"] ?? null;
        $nombreDadaDeBaja = $_SESSION["nombre_categoria_dada_de_baja"] ?? null;
        unset($_SESSION["nombre_categoria_reactivada"], $_SESSION["nombre_categoria_dada_de_baja"]);
        $mensajes = [
            "creada" => "La categoría se creó correctamente.",
            "modificada" => "La categoría se modificó correctamente.",
            "baja" => $nombreDadaDeBaja === null
                ? "La categoría quedó inactiva."
                : "La categoría {$nombreDadaDeBaja} quedó inactiva.",
            "reactivada" => $nombreReactivada === null
                ? "La categoría volvió a estar activa."
                : "La categoría {$nombreReactivada} volvió a estar activa.",
            "inexistente" => "No se encontró la categoría solicitada."
        ];
        $estado = Request::get("estado");
        $this->mostrar("categorias", [
            "categorias" => $this->model->listar(),
            "mensaje" => is_string($estado) ? ($mensajes[$estado] ?? "") : ""
        ]);
    }

    public function crear()
    {
        $this->mostrar("categoriaFormulario", ["color" => "#6655d9"]);
    }

    public function editar()
    {
        $categoria = $this->buscarCategoria(Request::get("id"));
        $categoria["color"] ??= "#6655d9";
        $this->mostrar("categoriaFormulario", $categoria);
    }

    public function guardar()
    {
        $this->validarPost();
        $this->procesarFormulario(null);
    }

    public function actualizar()
    {
        $this->validarPost();
        $categoria = $this->buscarCategoria(Request::post("id"));
        $this->procesarFormulario($categoria["id"]);
    }

    public function confirmarBaja()
    {
        $categoria = $this->buscarCategoria(Request::get("id"));
        if (!$categoria["activa"]) {
            $this->volverAlListado("baja");
        }
        $this->mostrar("categoriaBaja", $categoria);
    }

    public function baja()
    {
        $this->validarPost();
        $categoria = $this->buscarCategoria(Request::post("id"));
        $this->model->darDeBaja($categoria["id"]);
        $_SESSION["nombre_categoria_dada_de_baja"] = $categoria["nombre"];
        $this->volverAlListado("baja");
    }

    public function reactivar()
    {
        $this->validarPost();
        $categoria = $this->buscarCategoria(Request::post("id"));
        $this->model->reactivar($categoria["id"]);
        $_SESSION["nombre_categoria_reactivada"] = $categoria["nombre"];
        $this->volverAlListado("reactivada");
    }

    private function mostrar($vista, $datos)
    {
        $_SESSION["csrf_categoria"] ??= bin2hex(random_bytes(32));
        $this->render->renderiza($vista, $datos + [
            "logueado" => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "",
            "csrf_categoria" => $_SESSION["csrf_categoria"]
        ]);
    }

    private function validarPost()
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
            header("Allow: POST");
            http_response_code(405);
            exit;
        }
        $token = Request::post("csrf_categoria");
        if (!is_string($token) || !isset($_SESSION["csrf_categoria"])
            || !hash_equals($_SESSION["csrf_categoria"], $token)) {
            http_response_code(403);
            $this->mostrar("categoriaFormulario", [
                "error_general" => "El formulario venció. Volvé a abrirlo para intentarlo de nuevo."
            ]);
            exit;
        }
    }

    private function buscarCategoria($valor)
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $categoria = $id ? $this->model->buscar($id) : null;
        if (!$categoria) {
            $this->volverAlListado("inexistente");
        }
        return $categoria;
    }

    private function procesarFormulario($id)
    {
        $datos = ["id" => $id];
        foreach (["nombre", "descripcion", "color"] as $campo) {
            $valor = Request::post($campo);
            $datos[$campo] = is_string($valor) ? trim($valor) : "";
        }

        $errores = [];
        if ($datos["nombre"] === "" || mb_strlen($datos["nombre"]) > 50) {
            $errores["nombre"] = "Ingresá un nombre de hasta 50 caracteres.";
        }
        if (mb_strlen($datos["descripcion"]) > 150) {
            $errores["descripcion"] = "La descripción puede tener hasta 150 caracteres.";
        }
        if (!preg_match('/^#[0-9a-f]{6}$/iD', $datos["color"])) {
            $errores["color"] = "Elegí un color válido.";
        }

        if (!$errores) {
            try {
                $this->model->guardar($datos, $id);
                $this->volverAlListado($id === null ? "creada" : "modificada");
            } catch (PDOException $error) {
                if ((int)($error->errorInfo[1] ?? 0) === 1062) {
                    $errores["nombre"] = "Ya existe una categoría con ese nombre.";
                } else {
                    \Log::error("Error al guardar categoría: " . $error->getMessage());
                    $datos["error_general"] = "No se pudo guardar la categoría.";
                }
            }
        }

        $this->mostrar("categoriaFormulario", $datos + ["errores" => $errores]);
    }

    private function volverAlListado($estado)
    {
        Redirect::to(UrlHelper::controllerUrl("categoria", "show") . "?estado=" . $estado);
    }
}
