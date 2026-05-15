// Validaciones del formulario de nueva tarea en el cliente
// Esto no reemplaza la validacion del servidor, solo mejora la experiencia de usuario

document.addEventListener('DOMContentLoaded', () => {

    const formTarea = document.getElementById('form-nueva-tarea');
    if (!formTarea) return;

    const inputTexto = formTarea.querySelector('input[name="texto"]');
    const inputFecha = formTarea.querySelector('input[name="fecha_limite"]');

    formTarea.addEventListener('submit', (e) => {
        let valido = true;

        // Validar que el texto no esté vacío ni sea solo espacios
        if (!inputTexto || inputTexto.value.trim() === '') {
            mostrarError(inputTexto, 'Escribe el nombre de la tarea.');
            valido = false;
        } else {
            quitarError(inputTexto);
        }

        // Validar que la fecha+hora no sea anterior al momento actual 
        if (inputFecha && inputFecha.value) {
            const ahora = new Date();
            const fecha = new Date(inputFecha.value); // datetime-local ya incluye hora
            if (fecha < ahora) {
                mostrarError(inputFecha, 'La fecha y hora límite no pueden ser anteriores a ahora.');
                valido = false;
            } else {
                quitarError(inputFecha);
            }
        }

        if (!valido) e.preventDefault();
    });

    // Usa clases CSS en vez de style directo 
    function mostrarError(campo, mensaje) {
        if (!campo) return;
        campo.classList.add('campo-error');

        // Evitar duplicar el mensaje si ya existe
        let msg = campo.parentElement.querySelector('.error-msg');
        if (!msg) {
            msg = document.createElement('small');
            msg.className = 'error-msg';
            campo.insertAdjacentElement('afterend', msg);
        }
        msg.textContent = mensaje;
    }

    function quitarError(campo) {
        if (!campo) return;
        campo.classList.remove('campo-error');
        const msg = campo.parentElement.querySelector('.error-msg');
        if (msg) msg.remove();
    }

    // Botón de acceso como invitado: sin required para que no bloquee el submit
    const btnInvitado = document.querySelector('button[name="invitado"]');
    if (btnInvitado) {
        btnInvitado.addEventListener('click', () => {
            const userField = document.getElementById('user-field');
            const passField = document.getElementById('pass-field');
            if (userField) userField.removeAttribute('required');
            if (passField) passField.removeAttribute('required');
        });
    }

});
