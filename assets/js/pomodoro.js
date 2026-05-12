document.addEventListener('DOMContentLoaded', () => {
    // Igual que en app.js, esperamos a que el HTML esté listo.

    // Recogemos todos los elementos que vamos a necesitar
    const tareaSelect      = document.getElementById('pomodoro-tarea-select');
    const tareaTiempoDiv   = document.getElementById('pomodoro-tarea-tiempo');
    const display          = document.getElementById('pomodoro-display');
    const btnStart         = document.getElementById('btn-start');
    const btnPause         = document.getElementById('btn-pause');
    const btnReset         = document.getElementById('btn-reset');
    const btnStop          = document.getElementById('btn-stop');
    const labelModo        = document.getElementById('pomodoro-modo');
    const contadorSesiones = document.getElementById('pomodoro-sesiones');

    // Si no existe el display, no estamos en la página del pomodoro: salimos
    if (!display) return;

    let tareaIdActual = null; // guardamos qué tarea está seleccionada

    // -----------------------------------------------------------------------
    // MENSAJE DE ERROR INLINE
    // En vez de alert(), creamos un <p> de error debajo del selector.
    // Lo mostramos 3 segundos y luego desaparece solo.
    // -----------------------------------------------------------------------
    function mostrarMsgError(mensaje) {
        let msg = document.getElementById('pomodoro-msg-error');

        // Lo creamos solo la primera vez, las siguientes lo reutilizamos
        if (!msg) {
            msg = document.createElement('p');
            msg.id        = 'pomodoro-msg-error';
            msg.className = 'msg-inline msg-inline--error';
            tareaSelect.insertAdjacentElement('afterend', msg);
        }

        msg.textContent = mensaje;
        msg.classList.add('visible');

        // Desaparece automáticamente a los 3 segundos
        setTimeout(() => msg.classList.remove('visible'), 3000);
    }

    // -----------------------------------------------------------------------
    // TIEMPO DEDICADO A LA TAREA
    // Cuando el usuario elige una tarea en el selector, consultamos al servidor
    // cuántos segundos de pomodoro tiene ya dedicados y los mostramos.
    // -----------------------------------------------------------------------
    function formatearTiempoTotal(seg) {
        const h = Math.floor(seg / 3600);
        const m = Math.floor((seg % 3600) / 60);
        const s = seg % 60;
        return `${h > 0 ? h + 'h ' : ''}${m}m ${s}s`;
    }

    function mostrarTiempoTarea(tareaId) {
        if (!tareaId) { tareaTiempoDiv.textContent = ''; return; }

        fetch(`controllers/pomodoroController.php?tarea_id=${tareaId}`)
            .then(res => res.json())
            .then(data => {
                if (data && typeof data.segundos !== 'undefined') {
                    tareaTiempoDiv.textContent = `Tiempo dedicado: ${formatearTiempoTotal(data.segundos)}`;
                } else {
                    tareaTiempoDiv.textContent = '';
                }
            });
    }

    if (tareaSelect) {
        tareaSelect.addEventListener('change', function() {
            tareaIdActual = this.value;
            mostrarTiempoTarea(tareaIdActual);
        });
        // Si ya hay una tarea preseleccionada al cargar, mostramos su tiempo
        if (tareaSelect.value) {
            tareaIdActual = tareaSelect.value;
            mostrarTiempoTarea(tareaIdActual);
        }
    }

    // -----------------------------------------------------------------------
    // VARIABLES DEL TEMPORIZADOR
    // -----------------------------------------------------------------------
    const TRABAJO  = 25 * 60; // 25 minutos en segundos
    const DESCANSO =  5 * 60; //  5 minutos en segundos

    let segundosRestantes   = TRABAJO;
    let intervalo           = null;   // guardará el setInterval activo
    let enPausa             = true;
    let modoTrabajo         = true;
    let sesionesCompletadas = 0;

    function formatearTiempo(seg) {
        // Convertimos segundos a formato MM:SS con padStart para siempre tener 2 dígitos
        const m = Math.floor(seg / 60).toString().padStart(2, '0');
        const s = (seg % 60).toString().padStart(2, '0');
        return `${m}:${s}`;
    }

    function actualizarDisplay() {
        display.textContent = formatearTiempo(segundosRestantes);
        // También actualizamos el título de la pestaña del navegador
        document.title = `${formatearTiempo(segundosRestantes)} - ${modoTrabajo ? 'Trabajo' : 'Descanso'} | ToDoWeb`;
    }

    // -----------------------------------------------------------------------
    // GUARDAR SEGUNDOS EN EL SERVIDOR
    // Mandamos al servidor cuántos segundos ha trabajado el usuario en la tarea.
    // Incluimos el CSRF_TOKEN porque es un POST (seguridad).
    // -----------------------------------------------------------------------
    function guardarSegundos(segundos) {
        if (!tareaIdActual || segundos <= 0) return;

        fetch('controllers/pomodoroController.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    `tarea_id=${encodeURIComponent(tareaIdActual)}&segundos=${segundos}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
        })
        .then(res => res.json())
        .then(() => mostrarTiempoTarea(tareaIdActual)); // actualizamos el contador
    }

    // -----------------------------------------------------------------------
    // CAMBIAR DE MODO (trabajo → descanso o viceversa)
    // Se llama automáticamente cuando el contador llega a 0.
    // -----------------------------------------------------------------------
    function cambiarModo() {
        modoTrabajo = !modoTrabajo;
        segundosRestantes = modoTrabajo ? TRABAJO : DESCANSO;
        if (labelModo) labelModo.textContent = modoTrabajo ? 'Trabajo' : 'Descanso';

        // Solo contamos sesión y guardamos tiempo cuando acabamos un bloque de TRABAJO
        if (!modoTrabajo) {
            sesionesCompletadas++;
            if (contadorSesiones) contadorSesiones.textContent = sesionesCompletadas;
            guardarSegundos(TRABAJO);
        }

        actualizarDisplay();

        // Notificación del navegador si el usuario la concedió
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(modoTrabajo ? '¡Descanso terminado! A trabajar.' : '¡Pomodoro completado! Descansa.');
        }
    }

    // tick() se ejecuta cada segundo gracias al setInterval
    function tick() {
        if (segundosRestantes <= 0) { cambiarModo(); return; }
        segundosRestantes--;
        actualizarDisplay();
    }

    // -----------------------------------------------------------------------
    // BOTÓN INICIAR
    // -----------------------------------------------------------------------
    if (btnStart) {
        btnStart.addEventListener('click', () => {
            if (!enPausa) return; // si ya está corriendo no hacemos nada

            // Si no hay tarea seleccionada mostramos error inline, sin alert()
            if (!tareaSelect || !tareaSelect.value) {
                mostrarMsgError('Selecciona una tarea antes de iniciar.');
                return;
            }

            tareaIdActual = tareaSelect.value;
            enPausa = false;

            // Pedimos permiso para notificaciones si no lo hemos pedido aún
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }

            // setInterval llama a tick() cada 1000ms (1 segundo)
            intervalo = setInterval(tick, 1000);
            btnStart.disabled = true;
            if (btnPause) btnPause.disabled = false;
            if (btnStop)  btnStop.disabled  = false;
        });
    }

    // -----------------------------------------------------------------------
    // BOTÓN PAUSAR
    // -----------------------------------------------------------------------
    if (btnPause) {
        btnPause.disabled = true; // empieza desactivado
        btnPause.addEventListener('click', () => {
            if (enPausa) return;
            enPausa = true;
            clearInterval(intervalo); // paramos el contador
            btnStart.disabled = false;
            btnPause.disabled = true;
        });
    }

    // -----------------------------------------------------------------------
    // BOTÓN PARAR
    // Para el timer y guarda el tiempo parcial trabajado hasta ese momento.
    // -----------------------------------------------------------------------
    if (btnStop) {
        btnStop.disabled = true;
        btnStop.addEventListener('click', () => {
            if (enPausa) return;
            enPausa = true;
            clearInterval(intervalo);
            btnStart.disabled = false;
            if (btnPause) btnPause.disabled = true;
            btnStop.disabled = true;

            // Guardamos solo el tiempo que llevaba trabajado (no el total de 25min)
            if (modoTrabajo) {
                guardarSegundos(TRABAJO - segundosRestantes);
            }

            // Reiniciamos visualmente a 25:00
            segundosRestantes = TRABAJO;
            modoTrabajo       = true;
            if (labelModo) labelModo.textContent = 'Trabajo';
            actualizarDisplay();
        });
    }

    // -----------------------------------------------------------------------
    // BOTÓN REINICIAR
    // Solo resetea el reloj, no guarda tiempo ni cuenta sesión.
    // -----------------------------------------------------------------------
    if (btnReset) {
        btnReset.addEventListener('click', () => {
            clearInterval(intervalo);
            enPausa           = true;
            modoTrabajo       = true;
            segundosRestantes = TRABAJO;
            if (btnStart) btnStart.disabled = false;
            if (btnPause) btnPause.disabled = true;
            if (btnStop)  btnStop.disabled  = true;
            if (labelModo) labelModo.textContent = 'Trabajo';
            actualizarDisplay();
        });
    }

    // Mostramos el tiempo inicial al cargar la página
    actualizarDisplay();

});