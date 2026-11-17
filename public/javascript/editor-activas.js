(() => {
    const filtros = document.querySelector('.activas__filtros');
    if (!filtros) return;

    const busqueda = document.getElementById('activas-busqueda');
    const selector = document.getElementById('activas-categoria');
    const sinCoincidencias = document.getElementById('activas-sin-coincidencias');
    const normalizar = (texto) => texto.trim().toLocaleLowerCase('es')
        .normalize('NFD').replace(/\p{M}/gu, '');

    const preguntas = Array.from(document.querySelectorAll('.activas__pregunta'), (tarjeta) => ({
        tarjeta,
        enunciado: normalizar(tarjeta.querySelector('.revision__enunciado').textContent),
        categoria: tarjeta.dataset.categoria
    }));

    const categorias = new Set(preguntas.map(({ categoria }) => categoria).filter(Boolean));
    for (const categoria of [...categorias].sort((a, b) => a.localeCompare(b, 'es'))) {
        selector.add(new Option(categoria, categoria));
    }

    function filtrar() {
        const texto = normalizar(busqueda.value);
        const categoriaElegida = selector.value;
        let hayCoincidencias = false;

        for (const { tarjeta, enunciado, categoria } of preguntas) {
            const coincide = enunciado.includes(texto)
                && (!categoriaElegida || categoria === categoriaElegida);
            tarjeta.hidden = !coincide;
            hayCoincidencias ||= coincide;
        }

        sinCoincidencias.hidden = hayCoincidencias;
    }

    busqueda.addEventListener('input', filtrar);
    selector.addEventListener('change', filtrar);
    window.addEventListener('pageshow', filtrar);
    filtrar();
    filtros.hidden = false;
})();
