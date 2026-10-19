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
    form.addEventListener('submit', (event) => {
        if (!validarCoincidencia()) {
            event.preventDefault();
            confirmation.reportValidity();
        }
    });
})();
