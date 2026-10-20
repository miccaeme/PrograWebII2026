(() => {
    const form = document.getElementById('registro-form');
    const password = document.getElementById('password');
    const confirmation = document.getElementById('password-repetida');
    const message = document.getElementById('password-repetida-error');

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
