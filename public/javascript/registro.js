(() => {
    const form = document.getElementById('registro-form');
    const password = document.getElementById('password');
    const confirmation = document.getElementById('password-repetida');
    const message = document.getElementById('password-repetida-error');
    const entradaFoto = document.getElementById('foto-perfil');
    const vistaPrevia = document.getElementById('foto-perfil-vista-previa');
    const avatarPredeterminado = document.getElementById('avatar-predeterminado');
    const errorFoto = document.getElementById('foto-perfil-error');
    const claveFotoPendiente = 'cerebrito_foto_perfil_registro';
    let urlFotoActual = '';
    let fotoGuardadaParaEnvio = false;

    if (entradaFoto && vistaPrevia && avatarPredeterminado && errorFoto) {
        function restaurarAvatarPredeterminado() {
            if (urlFotoActual) {
                URL.revokeObjectURL(urlFotoActual);
                urlFotoActual = '';
            }
            vistaPrevia.removeAttribute('src');
            vistaPrevia.hidden = true;
            avatarPredeterminado.hidden = false;
        }

        function mostrarFoto(archivo) {
            if (urlFotoActual) {
                URL.revokeObjectURL(urlFotoActual);
            }
            urlFotoActual = URL.createObjectURL(archivo);
            vistaPrevia.src = urlFotoActual;
            vistaPrevia.hidden = false;
            avatarPredeterminado.hidden = true;
        }

        const erroresRegistro = Array.from(form.querySelectorAll('.registro__error'));
        const hayErroresRegistro = erroresRegistro.some((error) => error.textContent.trim() !== '');

        if (hayErroresRegistro) {
            try {
                const fotoGuardada = JSON.parse(sessionStorage.getItem(claveFotoPendiente) || 'null');
                if (fotoGuardada && fotoGuardada.datos && fotoGuardada.nombre && fotoGuardada.tipo) {
                    fetch(fotoGuardada.datos)
                        .then((respuesta) => respuesta.blob())
                        .then((blob) => {
                            const archivoRestaurado = new File([blob], fotoGuardada.nombre, { type: fotoGuardada.tipo });
                            const transferencia = new DataTransfer();
                            transferencia.items.add(archivoRestaurado);
                            entradaFoto.files = transferencia.files;
                            mostrarFoto(archivoRestaurado);
                            fotoGuardadaParaEnvio = true;
                        })
                        .catch(() => sessionStorage.removeItem(claveFotoPendiente));
                }
            } catch (error) {
                sessionStorage.removeItem(claveFotoPendiente);
            }
        } else {
            sessionStorage.removeItem(claveFotoPendiente);
        }

        entradaFoto.addEventListener('change', () => {
            const archivo = entradaFoto.files[0];

            if (!archivo) {
                restaurarAvatarPredeterminado();
                return;
            }

            const extensionesPermitidas = /\.(jpe?g|png|webp)$/i;
            const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
            const extensionValida = extensionesPermitidas.test(archivo.name);
            const tipoValido = tiposPermitidos.includes(archivo.type.toLowerCase());
            const formatoValido = extensionValida && (tipoValido || archivo.type === '');
            const tamanoMaximo = 2 * 1024 * 1024;

            if (!formatoValido) {
                errorFoto.textContent = 'Elegí una imagen JPG, PNG o WebP.';
                entradaFoto.value = '';
                restaurarAvatarPredeterminado();
                return;
            }

            if (archivo.size > tamanoMaximo) {
                errorFoto.textContent = 'La imagen debe pesar como máximo 2 MB.';
                entradaFoto.value = '';
                restaurarAvatarPredeterminado();
                return;
            }

            errorFoto.textContent = '';
            mostrarFoto(archivo);
            fotoGuardadaParaEnvio = false;
        });

        form.addEventListener('submit', (event) => {
            const archivo = entradaFoto.files[0];
            if (!archivo || fotoGuardadaParaEnvio) {
                return;
            }

            event.preventDefault();
            const lector = new FileReader();
            lector.onload = () => {
                try {
                    sessionStorage.setItem(claveFotoPendiente, JSON.stringify({
                        datos: lector.result,
                        nombre: archivo.name,
                        tipo: archivo.type || 'image/jpeg'
                    }));
                    fotoGuardadaParaEnvio = true;
                    form.requestSubmit(event.submitter || undefined);
                } catch (error) {
                    errorFoto.textContent = 'No se pudo conservar la foto. Probá seleccionarla nuevamente.';
                    fotoGuardadaParaEnvio = true;
                    form.requestSubmit(event.submitter || undefined);
                }
            };
            lector.onerror = () => {
                errorFoto.textContent = 'No se pudo conservar la foto. Probá seleccionarla nuevamente.';
                fotoGuardadaParaEnvio = true;
                form.requestSubmit(event.submitter || undefined);
            };
            lector.readAsDataURL(archivo);
        }, true);
    }

    if (!form || !password || !confirmation || !message) {
        return;
    }

    function validarCoincidencia() {
        const noCoinciden = confirmation.value !== '' && password.value !== confirmation.value;
        confirmation.setCustomValidity(noCoinciden ? 'Las contraseñas no coinciden.' : '');
        message.textContent = noCoinciden ? 'Las contraseñas no coinciden.' : '';
        return !noCoinciden;
    }

    password.addEventListener('input', validarCoincidencia);
    confirmation.addEventListener('input', validarCoincidencia);
    form.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || !(event.target instanceof HTMLInputElement)) {
            return;
        }

        const campos = Array.from(form.querySelectorAll('input:not([type="hidden"]), select'))
            .filter((campo) => !campo.disabled);
        const siguiente = campos[campos.indexOf(event.target) + 1];

        if (siguiente) {
            event.preventDefault();
            siguiente.focus();
        }
    });

    form.addEventListener('submit', (event) => {
        if (!validarCoincidencia()) {
            event.preventDefault();
            confirmation.reportValidity();
        }
    });
})();
