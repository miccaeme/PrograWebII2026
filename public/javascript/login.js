(() => {
    const formulario = document.getElementById('login-form');
    const campoUsuario = document.getElementById('login-usuario');
    const campoContrasena = document.getElementById('login-password');
    const errorUsuario = document.getElementById('login-usuario-error');
    const errorContrasena = document.getElementById('login-password-error');

    if (!formulario || !campoUsuario || !campoContrasena || !errorUsuario || !errorContrasena) {
        return;
    }

    let intentoEnvio = false;

    function validarCampo(campo, mensaje, contenedorError) {
        const estaVacio = campo.value.trim() === '';
        contenedorError.textContent = estaVacio ? mensaje : '';
        campo.setAttribute('aria-invalid', estaVacio ? 'true' : 'false');
        return !estaVacio;
    }

    function validarFormulario() {
        const usuarioValido = validarCampo(
            campoUsuario,
            'Ingresá tu nombre de usuario o email.',
            errorUsuario
        );
        const contrasenaValida = validarCampo(
            campoContrasena,
            'Ingresá tu contraseña.',
            errorContrasena
        );

        if (!usuarioValido) {
            campoUsuario.focus();
        } else if (!contrasenaValida) {
            campoContrasena.focus();
        }

        return usuarioValido && contrasenaValida;
    }

    formulario.addEventListener('submit', (evento) => {
        intentoEnvio = true;
        if (!validarFormulario()) {
            evento.preventDefault();
        }
    });

    campoUsuario.addEventListener('input', () => {
        if (intentoEnvio) {
            validarCampo(campoUsuario, 'Ingresá tu nombre de usuario o email.', errorUsuario);
        }
    });

    campoContrasena.addEventListener('input', () => {
        if (intentoEnvio) {
            validarCampo(campoContrasena, 'Ingresá tu contraseña.', errorContrasena);
        }
    });
})();
