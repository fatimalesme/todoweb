// Gestion visual de los avisos en el dashboard
// Los avisos ya se renderizan desde PHP; este script añade animacion y la opcion de cerrarlos

document.addEventListener('DOMContentLoaded', () => {

    const avisos = document.querySelectorAll('.aviso');
    if (!avisos.length) return;

    avisos.forEach((aviso) => {
        // Añadir boton de cerrar a cada aviso
        const btnCerrar = document.createElement('button');
        btnCerrar.textContent = 'x';
        btnCerrar.title       = 'Cerrar aviso';
        btnCerrar.style.cssText = `
            float: right;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            padding: 0 0 0 10px;
            color: inherit;
            width: auto;
            margin-bottom: 0;
            line-height: 1;
        `;
        aviso.prepend(btnCerrar);

        // Animacion de entrada
        aviso.style.opacity   = '0';
        aviso.style.transform = 'translateY(-8px)';
        aviso.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        setTimeout(() => {
            aviso.style.opacity   = '1';
            aviso.style.transform = 'translateY(0)';
        }, 50);

        // Cerrar con animacion al hacer click
        btnCerrar.addEventListener('click', () => {
            aviso.style.opacity   = '0';
            aviso.style.transform = 'translateY(-8px)';
            setTimeout(() => aviso.remove(), 400);
        });

        // Cerrar automaticamente despues de 8 segundos
        setTimeout(() => {
            if (aviso.isConnected) {
                aviso.style.opacity   = '0';
                aviso.style.transform = 'translateY(-8px)';
                setTimeout(() => aviso.remove(), 400);
            }
        }, 8000);
    });

});
