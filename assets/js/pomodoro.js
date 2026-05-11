// Timer Pomodoro: 25 minutos de trabajo + 5 minutos de descanso
// Se activa cuando existe el elemento #pomodoro-display en la pagina

document.addEventListener('DOMContentLoaded', () => {
    const btnStop   = document.getElementById('btn-stop');

    // --- Pomodoro y tareas ---
    const tareaSelect = document.getElementById('pomodoro-tarea-select');
    const tareaTiempoDiv = document.getElementById('pomodoro-tarea-tiempo');
    let tareaIdActual = null;

    function formatearTiempoTotal(seg) {
        const h = Math.floor(seg / 3600);
        const m = Math.floor((seg % 3600) / 60);
        const s = seg % 60;
        return `${h > 0 ? h + 'h ' : ''}${m}m ${s}s`;
    }

    function mostrarTiempoTarea(tareaId) {
        if (!tareaId) {
            tareaTiempoDiv.textContent = '';
            return;
        }
        fetch(`controllers/pomodoroController.php?tarea_id=${tareaId}`)
            .then(res => res.json())
            .then(data => {
                if (data && typeof data.segundos !== 'undefined') {
                    tareaTiempoDiv.textContent = `Tiempo Pomodoro dedicado: ${formatearTiempoTotal(data.segundos)}`;
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
        // Mostrar tiempo al cargar si hay tarea seleccionada
        if (tareaSelect.value) {
            tareaIdActual = tareaSelect.value;
            mostrarTiempoTarea(tareaIdActual);
        }
    }

    const display    = document.getElementById('pomodoro-display');
    const btnStart   = document.getElementById('btn-start');
    const btnPause   = document.getElementById('btn-pause');
    const btnReset   = document.getElementById('btn-reset');
    const labelModo  = document.getElementById('pomodoro-modo');
    const contadorSesiones = document.getElementById('pomodoro-sesiones');

    if (!display) return; // No estamos en la pagina del pomodoro

    const TRABAJO   = 25 * 60; // segundos
    const DESCANSO  =  5 * 60;

    let segundosRestantes = TRABAJO;
    let intervalo         = null;
    let enPausa           = true;
    let modoTrabajo       = true;
    let sesionesCompletadas = 0;

    function formatearTiempo(seg) {
        const m = Math.floor(seg / 60).toString().padStart(2, '0');
        const s = (seg % 60).toString().padStart(2, '0');
        return `${m}:${s}`;
    }

    function actualizarDisplay() {
        display.textContent = formatearTiempo(segundosRestantes);
        // Cambiar el titulo de la pestaña del navegador para ver el tiempo sin tener la pestana abierta
        document.title = `${formatearTiempo(segundosRestantes)} - ${modoTrabajo ? 'Trabajo' : 'Descanso'} | ToDoWeb`;
    }

    function cambiarModo() {
        modoTrabajo = !modoTrabajo;
        segundosRestantes = modoTrabajo ? TRABAJO : DESCANSO;
        if (labelModo) labelModo.textContent = modoTrabajo ? 'Trabajo' : 'Descanso';
        if (!modoTrabajo) {
            sesionesCompletadas++;
            if (contadorSesiones) contadorSesiones.textContent = sesionesCompletadas;
            // Al terminar un Pomodoro de trabajo, sumar tiempo a la tarea seleccionada
            if (tareaIdActual) {
                fetch('controllers/pomodoroController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `tarea_id=${encodeURIComponent(tareaIdActual)}&segundos=${TRABAJO}`
                })
                .then(res => res.json())
                .then(() => mostrarTiempoTarea(tareaIdActual));
            }
        }
        actualizarDisplay();
        // Notificacion del navegador si el usuario la concedio
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(modoTrabajo ? 'Descanso terminado. A trabajar!' : 'Pomodoro completado. Descansa!');
        }
    }

    function tick() {
        if (segundosRestantes <= 0) {
            cambiarModo();
            return;
        }
        segundosRestantes--;
        actualizarDisplay();
    }

    if (btnStart) {
        btnStart.addEventListener('click', () => {
            if (!enPausa) return;
            if (!tareaSelect || !tareaSelect.value) {
                alert('Selecciona una tarea antes de iniciar el Pomodoro.');
                return;
            }
            tareaIdActual = tareaSelect.value;
            enPausa = false;

            // Pedir permiso para notificaciones del navegador
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }

            intervalo = setInterval(tick, 1000);
            btnStart.disabled = true;
            if (btnPause) btnPause.disabled = false;
            if (btnStop) btnStop.disabled = false;
        });
    }

    if (btnStop) {
        btnStop.disabled = true;
        btnStop.addEventListener('click', () => {
            if (enPausa) return;
            enPausa = true;
            clearInterval(intervalo);
            btnStart.disabled = false;
            if (btnPause) btnPause.disabled = true;
            btnStop.disabled = true;
            // Guardar el tiempo trabajado en la tarea seleccionada
            if (tareaIdActual && modoTrabajo) {
                const tiempoTrabajado = TRABAJO - segundosRestantes;
                if (tiempoTrabajado > 0) {
                    fetch('controllers/pomodoroController.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `tarea_id=${encodeURIComponent(tareaIdActual)}&segundos=${tiempoTrabajado}`
                    })
                    .then(res => res.json())
                    .then(() => mostrarTiempoTarea(tareaIdActual));
                }
            }
            // Reset visual pero no suma sesión
            segundosRestantes = TRABAJO;
            modoTrabajo = true;
            if (labelModo) labelModo.textContent = 'Trabajo';
            actualizarDisplay();
        });
    }

    if (btnPause) {
        btnPause.disabled = true;
        btnPause.addEventListener('click', () => {
            if (enPausa) return;
            enPausa = true;
            clearInterval(intervalo);
            btnStart.disabled = false;
            btnPause.disabled = true;
        });
    }

    if (btnReset) {
        btnReset.addEventListener('click', () => {
            clearInterval(intervalo);
            enPausa           = true;
            modoTrabajo       = true;
            segundosRestantes = TRABAJO;
            if (btnStart) btnStart.disabled = false;
            if (btnPause) btnPause.disabled = true;
            if (labelModo) labelModo.textContent = 'Trabajo';
            actualizarDisplay();
        });
    }

    // Estado inicial
    actualizarDisplay();

});
