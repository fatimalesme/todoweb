// Gestión visual de los avisos en el dashboard
// Los avisos ya se renderizan desde PHP; este script añade animación y la opción de cerrarlos

document.addEventListener('DOMContentLoaded', () => {

    const avisos = document.querySelectorAll('.aviso');
    if (!avisos.length) return;

    avisos.forEach((aviso) => {
        
        const btnCerrar = document.createElement('button');
        btnCerrar.textContent = '×';
        btnCerrar.title       = 'Cerrar aviso';
        btnCerrar.className   = 'aviso-cerrar';
        aviso.prepend(btnCerrar);

        // Animación de entrada usando clases CSS 
        setTimeout(() => {
            aviso.classList.add('visible');
        }, 50);

        // Función auxiliar para ocultar con animación
        function ocultarAviso() {
            aviso.classList.remove('visible');
            aviso.classList.add('oculto');
            setTimeout(() => aviso.remove(), 400);
        }

        // Cerrar al hacer clic
        btnCerrar.addEventListener('click', ocultarAviso);

        // Cerrar automáticamente después de 8 segundos
        setTimeout(() => {
            if (aviso.isConnected) ocultarAviso();
        }, 8000);
    });

});
