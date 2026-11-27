(() => {
    const dialogo = document.getElementById('pregunta-baja');
    const id = document.getElementById('pregunta-baja-id');
    const enunciado = document.getElementById('pregunta-baja-enunciado');
    const mensaje = document.getElementById('pregunta-baja-mensaje');
    const cancelar = document.getElementById('pregunta-baja-cancelar');

    for (const boton of document.querySelectorAll('[data-pregunta-baja-id]')) {
        boton.addEventListener('click', () => {
            id.value = boton.dataset.preguntaBajaId;
            enunciado.textContent = boton.dataset.preguntaBajaEnunciado;

            if (typeof dialogo.showModal === 'function') {
                dialogo.showModal();
            } else if (window.confirm(enunciado.textContent + '\n\n' + mensaje.textContent)) {
                id.form.submit();
            }
        });
    }

    cancelar.addEventListener('click', () => dialogo.close());
})();
