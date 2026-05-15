// pomodoro.js — Timer Pomodoro con anillo SVG y puntos de sesión

document.addEventListener('DOMContentLoaded', () => {

    const display          = document.getElementById('pomodoro-display');
    const btnStart         = document.getElementById('btn-start');
    const btnPause         = document.getElementById('btn-pause');
    const btnReset         = document.getElementById('btn-reset');
    const btnStop          = document.getElementById('btn-stop');
    const labelModo        = document.getElementById('pomodoro-modo');
    const contadorSesiones = document.getElementById('pomodoro-sesiones');
    const tareaSelect      = document.getElementById('pomodoro-tarea-select');
    const tareaTiempoDiv   = document.getElementById('pomodoro-tarea-tiempo');
    const ring             = document.getElementById('pom-ring');
    const msgError         = document.getElementById('pomodoro-msg-error');

    if (!display) return;

    const TRABAJO   = 25 * 60;
    const DESCANSO  =  5 * 60;
    const CIRC      = 2 * Math.PI * 96; // 603

    let segundosRestantes  = TRABAJO;
    let intervalo          = null;
    let enPausa            = true;
    let modoTrabajo        = true;
    let sesionesCompletadas = 0;
    let tareaIdActual      = null;

    // Anillo 
    function actualizarAnillo() {
        if (!ring) return;
        const total = modoTrabajo ? TRABAJO : DESCANSO;
        const pct   = segundosRestantes / total;
        ring.style.strokeDashoffset = CIRC * (1 - pct);
    }

    //  Display 
    function fmt(seg) {
        return String(Math.floor(seg / 60)).padStart(2, '0') + ':' + String(seg % 60).padStart(2, '0');
    }

    function actualizarDisplay() {
        display.textContent = fmt(segundosRestantes);
        document.title = `${fmt(segundosRestantes)} · ${modoTrabajo ? 'trabajo' : 'descanso'} | ToDoWeb`;
        actualizarAnillo();
    }

    //  Puntos de sesión 
    function actualizarPuntos() {
        const dots = document.querySelectorAll('.pom-dot');
        dots.forEach((d, i) => {
            d.classList.toggle('activo', i < sesionesCompletadas % 4 || (sesionesCompletadas > 0 && sesionesCompletadas % 4 === 0));
        });
        if (contadorSesiones) contadorSesiones.textContent = sesionesCompletadas;
    }

    //  Error 
    function mostrarError(msg) {
        if (!msgError) return;
        msgError.textContent = msg;
        msgError.classList.add('visible');
        setTimeout(() => msgError.classList.remove('visible'), 3000);
    }

    //  Tiempo acumulado en tarea 
    function fmtTotal(seg) {
        const h = Math.floor(seg / 3600);
        const m = Math.floor((seg % 3600) / 60);
        const s = seg % 60;
        return `${h > 0 ? h + 'h ' : ''}${m}m ${s}s`;
    }

    function mostrarTiempoTarea(id) {
        if (!id || !tareaTiempoDiv) return;
        fetch(`controllers/pomodoroController.php?tarea_id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (data && typeof data.segundos !== 'undefined' && data.segundos > 0) {
                    tareaTiempoDiv.textContent = `Tiempo dedicado: ${fmtTotal(data.segundos)}`;
                } else {
                    tareaTiempoDiv.textContent = '';
                }
            });
    }

    if (tareaSelect) {
        tareaSelect.addEventListener('change', function() {
            tareaIdActual = this.value || null;
            mostrarTiempoTarea(tareaIdActual);
        });
        if (tareaSelect.value) {
            tareaIdActual = tareaSelect.value;
            mostrarTiempoTarea(tareaIdActual);
        }
    }

    //  Guardar segundos en BD 
    function guardarSegundos(seg) {
        if (!tareaIdActual || seg <= 0) return;
        const csrf = document.getElementById('csrf_token')?.value || '';
        fetch('controllers/pomodoroController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `tarea_id=${encodeURIComponent(tareaIdActual)}&segundos=${seg}&csrf_token=${encodeURIComponent(csrf)}`
        })
        .then(r => r.json())
        .then(() => mostrarTiempoTarea(tareaIdActual));
    }

    //  Cambio de modo trabajo/descanso 
    function cambiarModo() {
        modoTrabajo = !modoTrabajo;
        segundosRestantes = modoTrabajo ? TRABAJO : DESCANSO;
        if (labelModo) labelModo.textContent = modoTrabajo ? 'trabajo' : 'descanso';
        if (!modoTrabajo) {
            sesionesCompletadas++;
            actualizarPuntos();
            guardarSegundos(TRABAJO);
        }
        actualizarDisplay();
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(modoTrabajo ? 'Descansaste bien. A por ello.' : 'Pomodoro completado. Descansa 5 minutos.');
        }
    }

    //  Tick 
    function tick() {
        if (segundosRestantes <= 0) { cambiarModo(); return; }
        segundosRestantes--;
        actualizarDisplay();
    }

    //  Botones 
    if (btnPause) btnPause.disabled = true;
    if (btnStop)  btnStop.disabled  = true;

    btnStart?.addEventListener('click', () => {
        if (!enPausa) return;
        if (!tareaIdActual) { mostrarError('Selecciona una tarea antes de iniciar.'); return; }
        enPausa = false;
        if ('Notification' in window && Notification.permission === 'default') Notification.requestPermission();
        intervalo = setInterval(tick, 1000);
        btnStart.disabled = true;
        if (btnPause) btnPause.disabled = false;
        if (btnStop)  btnStop.disabled  = false;
    });

    btnPause?.addEventListener('click', () => {
        if (enPausa) return;
        enPausa = true;
        clearInterval(intervalo);
        btnStart.disabled = false;
        btnPause.disabled = true;
    });

    btnStop?.addEventListener('click', () => {
        if (enPausa) return;
        const trabajado = TRABAJO - segundosRestantes;
        enPausa = true;
        clearInterval(intervalo);
        if (modoTrabajo) guardarSegundos(trabajado);
        segundosRestantes = TRABAJO;
        modoTrabajo = true;
        if (labelModo) labelModo.textContent = 'trabajo';
        if (btnStart) btnStart.disabled = false;
        if (btnPause) btnPause.disabled = true;
        btnStop.disabled = true;
        actualizarDisplay();
    });

    btnReset?.addEventListener('click', () => {
        clearInterval(intervalo);
        enPausa = true;
        modoTrabajo = true;
        segundosRestantes = TRABAJO;
        if (btnStart) btnStart.disabled = false;
        if (btnPause) btnPause.disabled = true;
        if (btnStop)  btnStop.disabled  = true;
        if (labelModo) labelModo.textContent = 'trabajo';
        actualizarDisplay();
    });

    actualizarDisplay();
});