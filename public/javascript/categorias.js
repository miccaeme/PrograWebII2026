(() => {
    const dialogo = document.getElementById('categorias-baja');
    if (typeof dialogo.showModal !== 'function') return;

    const id = document.getElementById('categorias-baja-id');
    const nombre = document.getElementById('categorias-baja-nombre');
    const cancelar = document.getElementById('categorias-baja-cancelar');

    for (const enlace of document.querySelectorAll('[data-baja-id]')) {
        enlace.setAttribute('aria-haspopup', 'dialog');
        enlace.addEventListener('click', (evento) => {
            if (evento.defaultPrevented || evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.altKey) return;

            id.value = enlace.dataset.bajaId;
            nombre.textContent = enlace.dataset.bajaNombre;
            dialogo.showModal();
            evento.preventDefault();
        });
    }

    cancelar.addEventListener('click', () => dialogo.close());
})();
