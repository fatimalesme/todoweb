document.addEventListener('DOMContentLoaded', () => {
    // Esperamos a que el HTML esté completamente cargado antes de ejecutar nada.
    // Si no esperamos, el JS intentaría buscar elementos que aún no existen.

    // -----------------------------------------------------------------------
    // REFERENCIAS AL DOM
    // Guardamos en variables los elementos HTML que vamos a usar.
    // Es más eficiente buscarlo una vez y guardarlo, que buscarlo cada vez.
    // -----------------------------------------------------------------------
    const listMenu        = document.getElementById('list-menu');
    const currentTitle    = document.getElementById('current-list-title');
    const todoItems       = document.querySelectorAll('.todo-item');  // devuelve TODOS los li de tareas
    const msgSinTareas    = document.getElementById('msg-sin-tareas');
    const selectCategoria = document.getElementById('select-categoria');

    // -----------------------------------------------------------------------
    // FILTRAR TAREAS POR CATEGORÍA
    // Cuando el usuario hace clic en el sidebar, esta función muestra
    // solo las tareas que pertenecen a esa categoría y oculta el resto.
    // -----------------------------------------------------------------------
    function filtrarTareas(listId) {
        let visibles = 0;

        todoItems.forEach(item => {
            // Si listId es '0' es "Mi Día" y mostramos todas.
            // Si no, comparamos el data-list del <li> con el id seleccionado.
            const pertenece = listId === '0' || item.dataset.list === listId;
            item.style.display = pertenece ? 'flex' : 'none';
            if (pertenece) visibles++;
        });

        // Si no hay ninguna visible, mostramos el mensaje "No hay tareas"
        if (msgSinTareas) {
            msgSinTareas.style.display = visibles === 0 ? 'block' : 'none';
        }
    }

    // Al cargar la página mostramos "Mi Día" (todas las tareas)
    filtrarTareas('0');

    // -----------------------------------------------------------------------
    // CLICK EN EL SIDEBAR
    // Cuando el usuario hace clic en una categoría del sidebar:
    // 1. Cambia el título principal
    // 2. Marca esa categoría como activa (para el estilo CSS)
    // 3. Filtra las tareas
    // 4. Preselecciona esa categoría en el select del formulario
    // -----------------------------------------------------------------------
    if (listMenu) {
        listMenu.addEventListener('click', (e) => {
            if (e.target.tagName !== 'LI') return; // ignoramos clics fuera de un <li>

            const listId   = e.target.dataset.list;
            const listName = e.target.textContent.trim();

            if (currentTitle) currentTitle.textContent = listName;

            // Quitamos 'active' a todos y se lo ponemos solo al clicado
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

    // -----------------------------------------------------------------------
    // FUNCIÓN AUXILIAR AJAX
    // Todas las acciones (completar, eliminar, editar, postergar) van por aquí.
    // Usamos fetch() para mandar datos al servidor SIN recargar la página.
    // El CSRF_TOKEN lo define PHP en index.php y lo necesitamos para seguridad.
    // -----------------------------------------------------------------------
    function enviarAccion(datos) {
        const params = new URLSearchParams(datos);
        params.append('csrf_token', CSRF_TOKEN);

        return fetch('controllers/tareasController.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    params.toString()
        }).then(res => res.json()); // convertimos la respuesta a objeto JS
    }

    // -----------------------------------------------------------------------
    // DELEGACIÓN DE EVENTOS
    // En vez de poner un listener en cada botón, ponemos UNO SOLO en el
    // documento y miramos qué elemento fue el que se clicó.
    // Esto es más eficiente y funciona aunque se añadan tareas dinámicamente.
    // -----------------------------------------------------------------------
    document.addEventListener('click', (e) => {

        // COMPLETAR tarea
        // Antes de recargar, mostramos una animación de +20 XP flotando
        // justo encima del botón que pulsó el usuario. Así da feedback visual
        // inmediato sin esperar a que la página se recargue.
        const btnCompletar = e.target.closest('.completar-tarea');
        if (btnCompletar) {
            const id = btnCompletar.dataset.id;
            enviarAccion({ completar_id: id }).then(data => {
                if (data.success) {

                    // Buscamos el div de la animación, si no existe lo creamos
                    let popup = document.getElementById('gami-xp-popup');
                    if (!popup) {
                        popup = document.createElement('div');
                        popup.id = 'gami-xp-popup';
                        popup.className = 'gami-xp-popup';
                        document.body.appendChild(popup);
                    }

                    // Lo colocamos encima del botón que se pulsó
                    const rect = btnCompletar.getBoundingClientRect();
                    popup.style.left = rect.left + 'px';
                    popup.style.top  = (rect.top + window.scrollY) + 'px';
                    popup.textContent = '+20 XP';

                    // Reiniciamos la animación por si ya estaba en marcha
                    // void popup.offsetWidth fuerza al navegador a "leer" el DOM
                    // y así el CSS se reinicia correctamente
                    popup.classList.remove('animando');
                    void popup.offsetWidth;
                    popup.classList.add('animando');

                    // Esperamos 900ms a que termine la animación y recargamos
                    setTimeout(() => location.reload(), 900);
                }
            });
        }
        // --- ELIMINAR tarea ---
        // En vez de un confirm() feo del navegador, creamos una mini confirmación
        // inline (¿Eliminar? Sí / No) directamente en la fila de botones.
        const btnEliminar = e.target.closest('.eliminar-tarea');
        if (btnEliminar) {
            const item = btnEliminar.closest('.todo-item');
            if (!item) return;

            // Si hay una confirmación abierta en OTRO item la cerramos
            document.querySelectorAll('.confirmar-eliminar.visible').forEach(c => {
                if (c.closest('.todo-item') !== item) c.classList.remove('visible');
            });

            // La primera vez creamos el HTML de confirmación, las siguientes lo reutilizamos
            let confirmar = item.querySelector('.confirmar-eliminar');
            if (!confirmar) {
                confirmar = document.createElement('div');
                confirmar.className = 'confirmar-eliminar';
                confirmar.innerHTML = `
                    <span>¿Eliminar?</span>
                    <button class="btn-confirmar-si">Sí</button>
                    <button class="btn-confirmar-no">No</button>
                `;
                item.querySelector('.actions').appendChild(confirmar);

                confirmar.querySelector('.btn-confirmar-si').addEventListener('click', () => {
                    const id = btnEliminar.dataset.id;
                    enviarAccion({ eliminar_id: id }).then(data => {
                        if (data.success) {
                            item.remove(); // quitamos el <li> del DOM sin recargar
                            const listId = document.querySelector('.list-menu li.active')?.dataset.list ?? '0';
                            filtrarTareas(listId); // recalculamos por si queda vacío
                        }
                    });
                });

                confirmar.querySelector('.btn-confirmar-no').addEventListener('click', () => {
                    confirmar.classList.remove('visible'); // simplemente lo ocultamos
                });
            }

            confirmar.classList.toggle('visible'); // alterna mostrar/ocultar
        }

        // --- EDITAR tarea ---
        // Al pulsar editar ocultamos la vista normal y mostramos el formulario inline.
        const btnEditar = e.target.closest('.editar-tarea');
        if (btnEditar) {
            const item     = btnEditar.closest('.todo-item');
            if (!item) return;
            const viewDiv  = item.querySelector('.todo-view');  // la vista normal
            const editForm = item.querySelector('.todo-edit');  // el formulario oculto
            if (!editForm || !viewDiv) return;

            // Rellenamos el formulario con los datos actuales de la tarea
            editForm.texto.value        = item.dataset.texto || '';
            editForm.descripcion.value  = item.dataset.descripcion || '';
            editForm.fecha_limite.value = '';
            if (editForm.id_lista) editForm.id_lista.value = item.dataset.list || '0';

            // Intercambiamos: ocultamos la vista y mostramos el formulario
            viewDiv.style.display  = 'none';
            editForm.style.display = 'block';

            // Limpiamos errores de ediciones anteriores
            const msgPrev   = editForm.querySelector('.msg-inline');
            const inputPrev = editForm.querySelector('.campo-error');
            if (msgPrev)   msgPrev.classList.remove('visible');
            if (inputPrev) inputPrev.classList.remove('campo-error');

            // Cuando el usuario pulsa Guardar
            editForm.onsubmit = function(ev) {
                ev.preventDefault(); // evitamos que el formulario recargue la página

                const nuevoTexto = editForm.texto.value.trim();
                const nuevaDesc  = editForm.descripcion.value.trim();
                const nuevaFecha = editForm.fecha_limite.value;
                const nuevaCat   = editForm.id_lista.value;

                // Validación: si el texto está vacío mostramos error inline, sin alert()
                if (nuevoTexto === '') {
                    const inputTexto = editForm.querySelector('input[name="texto"]');
                    inputTexto.classList.add('campo-error'); // borde rojo via CSS

                    // Creamos el mensaje de error si no existe ya
                    let msg = editForm.querySelector('.msg-inline');
                    if (!msg) {
                        msg = document.createElement('p');
                        msg.className = 'msg-inline msg-inline--error';
                        inputTexto.insertAdjacentElement('afterend', msg);
                    }
                    msg.textContent = 'El texto no puede estar vacío.';
                    msg.classList.add('visible');
                    return; // cortamos aquí, no enviamos nada
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

            // Cuando el usuario pulsa Cancelar: deshacemos el intercambio
            editForm.querySelector('.btn-cancelar').onclick = function() {
                editForm.style.display = 'none';
                viewDiv.style.display  = '';
            };
        }

        // --- POSTERGAR tarea ---
        const btnPostergar = e.target.closest('.postergar-tarea');
        if (btnPostergar) {
            const id = btnPostergar.dataset.id;
            enviarAccion({ postergar_id: id }).then(data => {
                if (data.success) location.reload();
            });
        }
    });

});