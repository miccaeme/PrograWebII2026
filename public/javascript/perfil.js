(() => {
    const entrada = document.getElementById('perfil-foto');
    const nombre = document.getElementById('perfil-foto-nombre');
    const marco = document.querySelector('.perfil__marco');

    if (!entrada || !nombre || !marco) {
        return;
    }

    const contenidoOriginal = marco.innerHTML;
    let urlVistaPrevia = '';

    entrada.addEventListener('change', () => {
        const archivo = entrada.files[0];

        if (urlVistaPrevia) {
            URL.revokeObjectURL(urlVistaPrevia);
            urlVistaPrevia = '';
        }

        if (!archivo) {
            nombre.textContent = 'Ningún archivo elegido';
            marco.innerHTML = contenidoOriginal;
            return;
        }

        nombre.textContent = archivo.name;
        urlVistaPrevia = URL.createObjectURL(archivo);

        const vistaPrevia = document.createElement('img');
        vistaPrevia.className = 'perfil__foto';
        vistaPrevia.alt = 'Vista previa de la nueva foto de perfil';
        vistaPrevia.src = urlVistaPrevia;
        marco.replaceChildren(vistaPrevia);
    });
})();