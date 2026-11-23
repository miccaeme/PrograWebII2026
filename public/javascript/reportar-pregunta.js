document.querySelectorAll('.partida-reportar__form').forEach((formulario) => {
    const boton = formulario.querySelector('button[type="submit"]');
    const mensaje = formulario.querySelector('[role="status"]');
    const detalle = formulario.querySelector('.partida-reportar__detalle');
    let enviando = false;
    let reportada = false;

    formulario.elements.motivo.addEventListener('change', () => {
        if (!detalle) return;
        const esOtro = formulario.elements.motivo.value === 'Otro';
        detalle.hidden = !esOtro;
        if (!esOtro) detalle.value = '';
    });

    formulario.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (enviando || reportada) return;

        enviando = true;
        boton.disabled = true;
        mensaje.textContent = 'Enviando reporte…';

        try {
            const response = await fetch(formulario.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    motivo: formulario.elements.motivo.value,
                    detalle: detalle ? detalle.value : '',
                    contexto: formulario.dataset.contexto
                })
            });
            const resultado = await response.json();
            if (!response.ok || resultado.exito !== true) {
                throw new Error(resultado.mensaje || 'No se pudo registrar el reporte.');
            }

            reportada = true;
            mensaje.textContent = resultado.mensaje;
        } catch (error) {
            mensaje.textContent = error instanceof SyntaxError || error instanceof TypeError
                ? 'No se pudo comunicar con el servidor. Intentá nuevamente.'
                : error.message;
        } finally {
            enviando = false;
            boton.disabled = reportada;
        }
    });
});