<?php

namespace app\controller;

use Auth;
use Redirect;
use Request;
use UrlHelper;

class PreguntaController 
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    public function crear()
    {
        Auth::requiereLogin();
        $this->mostrarFormulario(["enviada" => Request::get("enviada") === "1"]);
    }

    public function guardar()
    {
        Auth::requiereLogin();

        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("pregunta", "crear");
        }

        $enunciado = trim(Request::post("enunciado", ""));

        $idCategoria = Request::post("id_categoria", "");

        $respuestas = Request::post("respuestas", []);

        $correcta = Request::post("correcta", "");

        if(!is_array($respuestas))
            {
                $respuestas = [];
            }

            $respuestas = array_values(array_map("trim", $respuestas));

            $errores = $this->validarFormulario($enunciado, $idCategoria, $respuestas, $correcta) + $this->model->validar($enunciado, $idCategoria, $respuestas);

            $datosIngresados = [
                "enunciado" => $enunciado,
                "id_categoria" => $idCategoria,
                "respuestas" => $respuestas,
                "correcta" => $correcta
            ];

            if(!empty($errores)) {
                $this->mostrarFormulario($datosIngresados + ["errores" => $errores]);
                return;
            }

            $estado = in_array($_SESSION["rol"] ?? "jugador", ["editor", "admin"], true) ? "activa" : "pendiente";

            $guardada = $this->model->guardar($enunciado, $idCategoria, $respuestas, $correcta, $_SESSION["usuario_id"], $estado);

            if(!$guardada){
                $this->mostrarFormulario($datosIngresados + ["error_general" => "No se pudo guardar la pregunta. Probá de nuevo."]);
                return;
            }

            Redirect::to(UrlHelper::controllerUrl("pregunta", "crear") . "?enviada=1");
    }

    private function validarFormulario($enunciado, $idCategoria, $respuestas, $correcta)
    {
        $errores = [];

        if($enunciado === "") {
            $errores["enunciado"] = "La pregunta es obligatoria.";
        } elseif (mb_strlen($enunciado) > 255){
            $errores["enunciado"] = "La pregunta no puede tener más de 255 caracteres.";
        }

        if(!filter_var($idCategoria, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
            $errores["categoria"] = "Elegí una categoría.";
        }

        if(count($respuestas) !== 4){
            $errores["respuestas"] = "Tenés que cargar 4 respuestas.";
        } elseif (in_array("", $respuestas, true)) {
            $errores["respuestas"] = "Completá las 4 respuestas.";
        } elseif (max(array_map("mb_strlen", $respuestas)) > 150) {
            $errores["respuestas"] = "Cada respuesta puede tener hasta 150 caracteres.";
        }

        if(!in_array($correcta, ["0", "1", "2", "3"], true)) {
            $errores["correcta"] = "Marcá cuál es la respuesta correcta.";
        }

        return $errores;
    }

    private function mostrarFormulario($datos)
    {
        $idCategoria = $datos["id_categoria"] ?? "";
        $respuestas = $datos["respuestas"] ?? [];
        $correcta = $datos["correcta"] ?? "";

        $categorias = $this->model->obtenerCategorias();
        foreach($categorias as &$categoria) {
            $categoria["seleccionada"] = (string)$categoria["id"] === $idCategoria;
        }
        unset($categoria);

        $opciones = [];
        foreach(["A", "B", "C", "D"] as $posicion => $letra){
            $opciones[] = [
                "posicion" => $posicion,
                "letra" => $letra,
                "texto" => $respuestas[$posicion] ?? "",
                "es_correcta" => (string)$posicion === $correcta
            ];
        }

        $this->render->renderiza("crearPregunta", [
            "logueado" => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "",
            "categorias" => $categorias,
            "opciones" => $opciones,
            "enunciado" => $datos["enunciado"] ?? "",
            "errores" => $datos["errores"] ?? [],
            "error_general" => $datos["error_general"] ?? null,
            "enviada" => $datos["enviada"] ?? false
        ]);
    }

}