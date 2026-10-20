(() => {
    const boton = document.querySelector('.header__menu-toggle');
    const menu = document.getElementById('header-menu');

    if (!boton || !menu) return;

    const cerrarMenu = () => {
        menu.classList.remove('is-open');
        boton.setAttribute('aria-expanded', 'false');
        boton.setAttribute('aria-label', 'Abrir menú');
    };

    boton.addEventListener('click', () => {
        const abierto = boton.getAttribute('aria-expanded') === 'true';
        menu.classList.toggle('is-open', !abierto);
        boton.setAttribute('aria-expanded', String(!abierto));
        boton.setAttribute('aria-label', abierto ? 'Abrir menú' : 'Cerrar menú');
    });

    menu.addEventListener('click', (evento) => {
        if (evento.target.closest('a')) cerrarMenu();
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') {
            cerrarMenu();
            boton.focus();
        }
    });
})();
