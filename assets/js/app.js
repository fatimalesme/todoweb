document.addEventListener('DOMContentLoaded', () => {

    // Referencias al DOM — guardamos los elementos que vamos a usar
    // para no buscarlos cada vez que los necesitemos
    const listMenu        = document.getElementById('list-menu');
    const currentTitle    = document.getElementById('current-list-title');
    const todoItems       = document.querySelectorAll('.todo-item');
    const msgSinTareas    = document.getElementById('msg-sin-tareas');
    const selectCategoria = document.getElementById('select-categoria');

    // ── FILTRADO POR CATEGORÍA ────────────────────────────────────────────
    // Muestra solo las tareas de la categoría activa.
    // listId === '0' significa "Mi Día" → se muestran todas.
    function filtrarTareas(listId) {
        let visibles = 0;
        todoItems.forEach(item => {
            const pertenece = listId === '0' || item.dataset.list === listId;
            item.style.display = pertenece ? 'flex' : 'none';
            if (pertenece) visibles++;
        });
        if (msgSinTareas) {
            msgSinTareas.style.display = visibles === 0 ? 'block' : 'none';
        }
    }

    filtrarTareas('0');

    // ── CLICK EN EL SIDEBAR ───────────────────────────────────────────────
    if (listMenu) {
        listMenu.addEventListener('click', (e) => {
            if (e.target.tagName !== 'LI') return;

            const listId   = e.target.dataset.list;
            const listName = e.target.textContent.trim();

            if (currentTitle) currentTitle.textContent = listName;

            document.querySelectorAll('.list-menu li').forEach(li => li.classList.remove('active'));
            e.target.classList.add('active');

            filtrarTareas(listId);

            // Sincronizamos el select del formulario con la categoría activa
            if (selectCategoria) {
                const opcion = selectCategoria.querySelector(`option[value="${listId}"]`);
                if (opcion) opcion.selected = true;
            }
        });
    }

    // ── PETICIONES AL SERVIDOR (AJAX) ─────────────────────────────────────
    // Usamos fetch() para mandar datos al servidor sin recargar la página.
    // CSRF_TOKEN lo define PHP en index.php para proteger contra ataques.
    function enviarAccion(datos) {
        const params = new URLSearchParams(datos);
        params.append('csrf_token', CSRF_TOKEN);

        return fetch('controllers/tareasController.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    params.toString()
        }).then(res => res.json());
    }

    // ── DELEGACIÓN DE EVENTOS ─────────────────────────────────────────────
    // Un solo listener en el documento detecta todos los clicks.
    // Esto es más eficiente que poner un listener en cada botón.
    document.addEventListener('click', (e) => {

        // COMPLETAR / DESHACER tarea
        const btnCompletar = e.target.closest('.completar-tarea');
        if (btnCompletar) {
            const id = btnCompletar.dataset.id;
            enviarAccion({ completar_id: id }).then(data => {
                if (data.success) location.reload();
            });
        }

        // ELIMINAR tarea — sin confirm() feo, directamente con animación
        const btnEliminar = e.target.closest('.eliminar-tarea');
        if (btnEliminar) {
            const item = btnEliminar.closest('.todo-item');
            if (!item) return;
            const id = btnEliminar.dataset.id;

            // Primero borramos en la base de datos
            enviarAccion({ eliminar_id: id }).then(data => {
                if (data.success) {
                    // Si el servidor confirmó el borrado, lanzamos la animación
                    polvoEstrellas(item);
                }
            });
        }

        // EDITAR tarea
        const btnEditar = e.target.closest('.editar-tarea');
        if (btnEditar) {
            const item     = btnEditar.closest('.todo-item');
            if (!item) return;
            const viewDiv  = item.querySelector('.todo-view');
            const editForm = item.querySelector('.todo-edit');
            if (!editForm || !viewDiv) return;

            // Rellenamos el formulario con los valores actuales de la tarea
            editForm.texto.value        = item.dataset.texto || '';
            editForm.descripcion.value  = item.dataset.descripcion || '';
            editForm.fecha_limite.value = '';
            if (editForm.id_lista) editForm.id_lista.value = item.dataset.list || '0';

            // Ocultamos la vista normal y mostramos el formulario de edición
            viewDiv.style.display  = 'none';
            editForm.style.display = 'block';

            // Limpiar errores anteriores
            const msgPrev   = editForm.querySelector('.msg-inline');
            const inputPrev = editForm.querySelector('.campo-error');
            if (msgPrev)   msgPrev.classList.remove('visible');
            if (inputPrev) inputPrev.classList.remove('campo-error');

            // Al pulsar Guardar
            editForm.onsubmit = function(ev) {
                ev.preventDefault();
                const nuevoTexto = editForm.texto.value.trim();
                const nuevaDesc  = editForm.descripcion.value.trim();
                const nuevaFecha = editForm.fecha_limite.value;
                const nuevaCat   = editForm.id_lista.value;

                // Validación sin alert() — error inline debajo del campo
                if (nuevoTexto === '') {
                    const inputTexto = editForm.querySelector('input[name="texto"]');
                    inputTexto.classList.add('campo-error');
                    let msg = editForm.querySelector('.msg-inline');
                    if (!msg) {
                        msg = document.createElement('p');
                        msg.className = 'msg-inline msg-inline--error';
                        inputTexto.insertAdjacentElement('afterend', msg);
                    }
                    msg.textContent = 'El texto no puede estar vacío.';
                    msg.classList.add('visible');
                    return;
                }

                enviarAccion({
                    editar_id:         item.dataset.id,
                    nuevo_texto:       nuevoTexto,
                    nueva_descripcion: nuevaDesc,
                    nueva_fecha:       nuevaFecha,
                    nueva_categoria:   nuevaCat
                }).then(data => {
                    if (data.success) location.reload();
                });
            };

            // Al pulsar Cancelar
            editForm.querySelector('.btn-cancelar').onclick = function() {
                editForm.style.display = 'none';
                viewDiv.style.display  = '';
            };
        }

        // POSTERGAR tarea (+1 día)
        const btnPostergar = e.target.closest('.postergar-tarea');
        if (btnPostergar) {
            const id = btnPostergar.dataset.id;
            enviarAccion({ postergar_id: id }).then(data => {
                if (data.success) location.reload();
            });
        }

    });

    // ── POLVO DE ESTRELLAS ────────────────────────────────────────────────
    // Animación al eliminar una tarea. Creamos partículas SVG que salen
    // disparadas en todas direcciones mientras la tarjeta se desvanece.
    //
    // Usamos requestAnimationFrame en vez de setInterval porque es la forma
    // que tiene el navegador de sincronizar animaciones con la pantalla
    // (60fps suaves, sin saltos).
    function polvoEstrellas(el) {
        const W = el.offsetWidth;
        const H = el.offsetHeight;

        // Creamos un SVG transparente encima de la tarjeta
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.style.cssText = `
            position: absolute;
            inset: 0;
            width: ${W}px;
            height: ${H}px;
            overflow: visible;
            pointer-events: none;
            z-index: 999;
        `;
        el.style.position = 'relative';
        el.style.overflow = 'visible';
        el.appendChild(svg);

        // Paleta de colores del universo de la app
        const colores = ['#c084fc','#f87171','#fbbf24','#ffffff','#a78bfa','#fca5a5','#e9d5ff','#dc2626'];
        // Formas de estrella variadas para que no parezca todo igual
        const formas  = ['✦', '·', '⋆', '★', '✧', '•', '*'];
        const N = 55;
        const particulas = [];

        // Creamos cada partícula en posición aleatoria dentro de la tarjeta
        for (let i = 0; i < N; i++) {
            // Posición de salida — repartida por toda la tarjeta
            const x0  = W * 0.1 + Math.random() * W * 0.8;
            const y0  = H * 0.1 + Math.random() * H * 0.8;

            // Dirección y velocidad aleatorias
            const ang = Math.random() * Math.PI * 2;
            const vel = 40 + Math.random() * 120;
            const dx  = Math.cos(ang) * vel;
            const dy  = Math.sin(ang) * vel;

            const dur   = 600 + Math.random() * 600;
            const delay = Math.random() * 150;
            const size  = 8  + Math.random() * 14;
            const color = colores[Math.floor(Math.random() * colores.length)];
            const forma = formas[Math.floor(Math.random() * formas.length)];

            // Creamos el elemento SVG de texto (la partícula)
            const txt = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            txt.setAttribute('x', x0);
            txt.setAttribute('y', y0);
            txt.setAttribute('font-size', size);
            txt.setAttribute('fill', color);
            txt.setAttribute('text-anchor', 'middle');
            txt.setAttribute('dominant-baseline', 'middle');
            txt.textContent = forma;
            txt.style.opacity = '0';
            svg.appendChild(txt);

            particulas.push({ el: txt, x0, y0, dx, dy, dur, delay, size });
        }

        // Desvanecemos el contenido de la tarjeta con blur
        el.querySelectorAll('.todo-view, .actions').forEach(t => {
            t.style.transition = 'opacity 0.3s ease, filter 0.3s ease';
            t.style.filter     = 'blur(4px)';
            t.style.opacity    = '0';
        });

        // Lanzamos la animación de partículas
        const t0 = performance.now();

        function animar(now) {
            let todasTerminadas = true;

            particulas.forEach(p => {
                const elapsed = now - t0 - p.delay;
                if (elapsed < 0) { todasTerminadas = false; return; }

                const prog = Math.min(elapsed / p.dur, 1);
                if (prog < 1) todasTerminadas = false;

                // Ease out cúbica: empieza rápido y frena al final
                const ease = 1 - Math.pow(1 - prog, 3);
                const x    = p.x0 + p.dx * ease;
                const y    = p.y0 + p.dy * ease;

                // Opacidad: sube en el primer 20%, baja en el 80% restante
                const op = prog < 0.2
                    ? prog / 0.2
                    : 1 - ((prog - 0.2) / 0.8);

                p.el.setAttribute('x', x);
                p.el.setAttribute('y', y);
                p.el.style.opacity = Math.max(0, op);
            });

            if (!todasTerminadas) {
                requestAnimationFrame(animar);
            } else {
                // Partículas terminadas — colapsamos el espacio de la tarjeta
                // para que las demás suban sin salto brusco
                el.style.transition  = 'max-height 0.35s ease, margin 0.35s ease, padding 0.35s ease, opacity 0.2s ease';
                el.style.maxHeight   = el.offsetHeight + 'px';
                el.style.overflow    = 'hidden';

                requestAnimationFrame(() => {
                    el.style.maxHeight     = '0';
                    el.style.marginBottom  = '0';
                    el.style.paddingTop    = '0';
                    el.style.paddingBottom = '0';
                    el.style.opacity       = '0';
                    el.style.borderWidth   = '0';
                });

                // Eliminamos el nodo del DOM cuando termina la transición
                setTimeout(() => {
                    el.remove();
                    const listId = document.querySelector('.list-menu li.active')?.dataset.list ?? '0';
                    filtrarTareas(listId);
                }, 400);
            }
        }

        setTimeout(() => requestAnimationFrame(animar), 50);
    }

});