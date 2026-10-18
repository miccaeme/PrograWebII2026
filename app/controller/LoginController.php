<?php

namespace app\controller;
class LoginController {
    private $usuarioModel;
    private $renderer;

    public function __construct($usuarioModel, $renderer) {
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
    }

    public function show() {
        $this->renderer->renderiza("login");
    }
}