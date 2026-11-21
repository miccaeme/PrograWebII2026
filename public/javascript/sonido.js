(() => {
    const CLAVE = 'cerebrito_silenciado';
    let contexto = null;

    function estaSilenciado() {

        try {
            return localStorage.getItem(CLAVE) === '1';
        } catch {
            return false;
        }
    }

    function guardarSilenciado(valor) {
        try {
            localStorage.setItem(CLAVE, valor ? '1' : '0');
        } catch {

        }
    }

    function obtenerContexto() {
        if (!contexto) {
            const Contexto = window.AudioContext || window.webkitAudioContext;

            if(!Contexto) return null;

            contexto = new Contexto();
        }

        if(contexto.state === 'suspended'){
            contexto.resume();
        }
        return contexto;
    }

    function nota(ctx, frecuencia, inicio, duracion, tipo = 'sine', volumen = 0.2) {
        const oscilador = ctx.createOscillator();
        const ganancia = ctx.createGain();
        const t = ctx.currentTime + inicio;
        oscilador.type = tipo;
        oscilador.frequency.value = frecuencia;
        ganancia.gain.setValueAtTime(volumen, t);
        ganancia.gain.exponentialRampToValueAtTime(0.001, t + duracion);
        oscilador.connect(ganancia).connect(ctx.destination);
        oscilador.start(t);
        oscilador.stop(t + duracion);
    }

    const SONIDOS = {
        tic: ctx => nota(ctx, 900, 0, 0.04, 'square', 0.05),
        correcto: ctx => {
            nota(ctx, 523, 0, 0.15); // esto hac la nota do
            nota(ctx, 659, 0.12, 0.15); // mi
            nota(ctx, 784, 0.24, 0.3); //sol
        },
        incorrecto: ctx => {
            nota(ctx, 330, 0, 0.25, 'sawtooth', 0.12);
            nota(ctx, 220, 0.22, 0.45, 'sawtooth', 0.12);
        }
    };

    function reproducir(nombre){
        if(estaSilenciado() || !SONIDOS[nombre]) return;
        const ctx = obtenerContexto();
        if(ctx){
            SONIDOS[nombre](ctx);
        }
    }

    function actualizarBoton(boton){
        const silenciado = estaSilenciado();
        const texto = silenciado ? 'Activar sonido' : 'Silenciar sonido';
        boton.setAttribute('aria-pressed', String(silenciado));
        boton.setAttribute('aria-label', texto);
        boton.title = texto;
    }

    window.Sonido = {
        reproducir
    };

    document.addEventListener('pointerdown', obtenerContexto, { once: true });

    document.addEventListener('DOMContentLoaded', () => {
        const boton = document.getElementById('boton-sonido');

        if(boton) {
            actualizarBoton(boton);
            boton.addEventListener('click', () => {
                guardarSilenciado(!estaSilenciado());
                actualizarBoton(boton);
                reproducir('tic');
            });
        }

        const pagina = document.querySelector('[data-sonido]');
        if(pagina){
            reproducir(pagina.dataset.sonido);
        }
    });
})();