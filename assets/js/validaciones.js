// Validaciones del formulario de nueva tarea en el cliente
// Esto no reemplaza la validacion del servidor, solo mejora la experiencia de usuario

document.addEventListener('DOMContentLoaded', () => {

    const formTarea = document.getElementById('form-nueva-tarea');
    if (!formTarea) return;

    const inputTexto = formTarea.querySelector('input[name="texto"]');
    const inputFecha = formTarea.querySelector('input[name="fecha_limite"]');

    formTarea.addEventListener('submit', (e) => {
        let valido = true;

        // Validar que el texto no este vacio ni sea solo espacios
        if (!inputTexto || inputTexto.value.trim() === '') {
            mostrarError(inputTexto, 'Escribe el nombre de la tarea.');
            valido = false;
        } else {
            quitarError(inputTexto);
        }

        // Validar que la fecha no sea anterior a hoy
        if (inputFecha && inputFecha.value) {
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            const fecha = new Date(inputFecha.value + 'T00:00:00');
            if (fecha < hoy) {
                mostrarError(inputFecha, 'La fecha limite no puede ser anterior a hoy.');
                valido = false;
            } else {
                quitarError(inputFecha);
            }
        }

        if (!valido) e.preventDefault();
    });

    function mostrarError(campo, mensaje) {
        if (!campo) return;
        campo.style.borderColor = '#fc8181';
        campo.style.boxShadow = '0 0 0 3px rgba(252,129,129,0.2)';

        // Evitar duplicar el mensaje si ya existe
        let msg = campo.parentElement.querySelector('.error-msg');
        if (!msg) {
            msg = document.createElement('small');
            msg.className = 'error-msg';
            msg.style.cssText = 'color:#c53030; font-size:0.8rem; display:block; margin-top:-12px; margin-bottom:8px;';
            campo.insertAdjacentElement('afterend', msg);
        }
        msg.textContent = mensaje;
    }

    function quitarError(campo) {
        if (!campo) return;
        campo.style.borderColor = '';
        campo.style.boxShadow = '';
        const msg = campo.parentElement.querySelector('.error-msg');
        if (msg) msg.remove();
    }

    // Botón de acceso como invitado
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
