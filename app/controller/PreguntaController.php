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
            "enviada" => $datos["enviada"] ?? false,
            "id_pregunta" => $datos["id_pregunta"] ?? null,
            "modo_edicion" => isset($datos["id_pregunta"]),
            "puede_publicar" => in_array($_SESSION["rol"] ?? "jugador", ["editor", "admin"], true)
        ]);
    }

    public function mias()
    {
        Auth::requiereLogin();

        $preguntas = $this->model->obtenerPorCreador($_SESSION["usuario_id"]);

        foreach ($preguntas as &$p) {
            $p["es_pendiente"] = $p["estado"] === "pendiente";
            $p["es_activa"] = $p["estado"] === "activa";
            $p["es_rechazada"] = $p["estado"] === "rechazada";
        }
        unset($p);

        $this->render->renderiza("misPreguntas", [
            "logueado" => true,
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? "",
            "preguntas" => $preguntas,
            "sin_preguntas" => empty($preguntas)
        ]);
    }

    public function editar()
    {
        Auth::requiereRol(["editor", "admin"]);

        $pregunta = $this->model->obtenerPorId(Request::get("id"));

        if ($pregunta === null) {
            Redirect::toController("editor", "activas");
        }

        $textos = array_column($pregunta["respuestas"], "texto");
        $correcta = "";
        foreach ($pregunta["respuestas"] as $posicion => $r) {
            if ((int)$r["es_correcta"] === 1) {
                $correcta = (string)$posicion;
            }
        }

        $this->mostrarFormulario([
            "id_pregunta" => $pregunta["id"],
            "enunciado" => $pregunta["enunciado"],
            "id_categoria" => (string)$pregunta["id_categoria"],
            "respuestas" => $textos,
            "correcta" => $correcta
        ]);
    }

    public function actualizar()
    {
        Auth::requiereRol(["editor", "admin"]);

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("editor", "activas");
        }

        $idPregunta = (int)Request::post("id_pregunta", 0);
        $enunciado = trim(Request::post("enunciado", ""));
        $idCategoria = Request::post("id_categoria", "");
        $respuestas = Request::post("respuestas", []);
        $correcta = Request::post("correcta", "");

        if (!is_array($respuestas)) {
            $respuestas = [];
        }
        $respuestas = array_values(array_map("trim", $respuestas));

        $errores = $this->validarFormulario($enunciado, $idCategoria, $respuestas, $correcta)
            + $this->model->validar($enunciado, $idCategoria, $respuestas, $idPregunta);

        $datos = [
            "id_pregunta" => $idPregunta,
            "enunciado" => $enunciado,
            "id_categoria" => $idCategoria,
            "respuestas" => $respuestas,
            "correcta" => $correcta
        ];

        if (!empty($errores)) {
            $this->mostrarFormulario($datos + ["errores" => $errores]);
            return;
        }

        if (!$this->model->actualizar($idPregunta, $enunciado, $idCategoria, $respuestas, $correcta)) {
            $this->mostrarFormulario($datos + ["error_general" => "No se pudo guardar los cambios. Probá de nuevo."]);
            return;
        }

        Redirect::toController("editor", "activas");
    }

    private const MOTIVOS_REPORTE = [
        "La respuesta correcta es otra",
        "La pregunta está mal escrita",
        "Contenido inapropiado",
        "Pregunta repetida o confusa",
        "Otro"
    ];

    public function reportar()
    {
        Auth::requiereRol(["jugador"]);

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("lobby", "show");
        }

        $idPregunta = $_SESSION['partida_activa']['pregunta_actual']['id'] ?? null;
        $motivo = Request::post("motivo", "");


        if ($idPregunta && in_array($motivo, self::MOTIVOS_REPORTE, true)) {
            $this->model->reportar($_SESSION['usuario_id'] ?? 0, $idPregunta, $motivo);
        }

        Redirect::toController("partida", "jugar");
    }

}