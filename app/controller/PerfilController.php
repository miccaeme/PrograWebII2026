<?php

namespace app\controller;

use Request;
use Redirect;
use UrlHelper;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

class PerfilController
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
        $this->requiereLogin();
        $this->mostrarPerfil([]);
    }

    public function compartido()
    {
        $this->requiereLogin();

        $token = Request::get("token", "");
        $usuario = $this->model->buscarPerfilCompartidoPorToken($token);

        if (!$usuario) {
            http_response_code(404);
        }

        $this->render->renderiza("perfilCompartido", [
            "usuario" => $usuario,
            "noEncontrado" => !$usuario,
            "logueado" => !empty($_SESSION["logueado"]),
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? ""
        ]);
    }

    public function subirFoto()
    {
        $this->requiereLogin();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Redirect::toController("perfil", "show");
        }

        $archivo = Request::file("foto_perfil");

        if (!$archivo || $archivo["error"] === UPLOAD_ERR_NO_FILE) {
            $this->mostrarPerfil(["error" => "Elegí una imagen antes de subirla."]);
            return;
        }

        if ($archivo["error"] === UPLOAD_ERR_INI_SIZE || $archivo["error"] === UPLOAD_ERR_FORM_SIZE) {
            $this->mostrarPerfil(["error" => "La imagen es demasiado pesada."]);
            return;
        }

        if ($archivo["error"] !== UPLOAD_ERR_OK) {
            $this->mostrarPerfil(["error" => "No se pudo subir la imagen. Probá de nuevo."]);
            return;
        }

        $resultado = $this->model->cambiarFoto($_SESSION["usuario_id"], $archivo);

        if (isset($resultado["error"])) {
            $this->mostrarPerfil(["error" => $resultado["error"]]);
            return;
        }
        Redirect::toController("perfil", "show");
    }

    private function mostrarPerfil($extra)
    {
        $usuario = $this->model->buscarPorId($_SESSION["usuario_id"]);
        $tokenPerfil = $usuario["token_perfil"] ?? "";
        $urlCompartida = $this->urlPerfilCompartido($tokenPerfil);
        $codigoQr = Builder::create()
            ->writer(new SvgWriter())
            ->data($urlCompartida)
            ->size(240)
            ->margin(10)
            ->build();

        $this->render->renderiza("perfil", array_merge([
            "usuario" => $usuario,
            "url_perfil_compartido" => $urlCompartida,
            "codigo_qr_data_uri" => $codigoQr->getDataUri(),
            "logueado" => !empty($_SESSION["logueado"]),
            "nombre_usuario" => $_SESSION["nombre_usuario"] ?? ""
        ], $extra));
    }

    private function urlPerfilCompartido($token)
    {
        $host = $_SERVER["HTTP_HOST"] ?? ($_SERVER["SERVER_NAME"] ?? "localhost");
        if (!preg_match('/\\A(?:[a-z0-9.-]+|\\[[a-f0-9:]+\\])(?::[0-9]{1,5})?\\z/i', $host)) {
            $host = $_SERVER["SERVER_NAME"] ?? "localhost";
        }

        $protocolo = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
        return $protocolo . "://" . $host
            . UrlHelper::controllerUrl("perfil", "compartido")
            . "?token=" . rawurlencode($token);
    }

    private function requiereLogin()
    {
        if (empty($_SESSION["usuario_id"])) {
            Redirect::toController("login", "show");
        }
    }
}
