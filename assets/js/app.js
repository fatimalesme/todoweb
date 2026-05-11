document.addEventListener('DOMContentLoaded', () => {

    // -----------------------------------------------------------------------
    // REFERENCIAS AL DOM
    // -----------------------------------------------------------------------
    const listMenu       = document.getElementById('list-menu');
    const currentTitle   = document.getElementById('current-list-title');
    const todoItems      = document.querySelectorAll('.todo-item');
    const msgSinTareas   = document.getElementById('msg-sin-tareas');
    const selectCategoria = document.getElementById('select-categoria');  // <-- el nuevo select del form

    // -----------------------------------------------------------------------
    // FILTRADO DE TAREAS POR CATEGORÍA
    //
    // Lógica:
    //   - listId === '0' → "Mi Día" → se muestran TODAS las tareas
    //   - cualquier otro id → se muestran solo las que tienen ese data-list
    //
    // Antes el filtro también actualizaba categoryInput (hidden).
    // Ahora eso lo hace el <select> directamente en el form,
    // y el sidebar solo controla la vista.
    // -----------------------------------------------------------------------
    function filtrarTareas(listId) {
        let visibles = 0;
        todoItems.forEach(item => {
            const pertenece = listId === '0' || item.dataset.list === listId;
            item.style.display = pertenece ? 'flex' : 'none';
            if (pertenece) visibles++;
        });

        // Mostramos el mensaje vacío si no hay tareas en esa categoría
        if (msgSinTareas) {
            msgSinTareas.style.display = visibles === 0 ? 'block' : 'none';
        }
    }

    // Al cargar la página mostramos "Mi Día" (todas las tareas)
    filtrarTareas('0');

    // -----------------------------------------------------------------------
    // CLICK EN EL SIDEBAR (cambiar de categoría)
    // -----------------------------------------------------------------------
    if (listMenu) {
        listMenu.addEventListener('click', (e) => {
            if (e.target.tagName !== 'LI') return;

            const listId   = e.target.dataset.list;
            const listName = e.target.textContent.trim();

            // 1. Actualizar el título del área principal
            if (currentTitle) currentTitle.textContent = listName;

            // 2. Marcar el li activo en el sidebar
            document.querySelectorAll('.list-menu li').forEach(li => li.classList.remove('active'));
            e.target.classList.add('active');

            // 3. Filtrar las tareas en pantalla
            filtrarTareas(listId);

            // 4. Sincronizar el <select> del formulario con la categoría activa.
            //    Así si el usuario está viendo "Casa" y añade una tarea,
            //    el select ya tiene "Casa" preseleccionado.
            if (selectCategoria) {
                // Intentamos seleccionar la opción con el mismo value
                const opcion = selectCategoria.querySelector(`option[value="${listId}"]`);
                if (opcion) opcion.selected = true;
            }
        });
    }

    // -----------------------------------------------------------------------
    // FUNCIÓN AUXILIAR PARA PETICIONES AJAX
    // CSRF_TOKEN se define en index.php antes de cargar este script
    // -----------------------------------------------------------------------
    function enviarAccion(datos) {
        const params = new URLSearchParams(datos);
        params.append('csrf_token', CSRF_TOKEN);

        return fetch('controllers/tareasController.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    params.toString()
        }).then(res => res.json());
    }

    // -----------------------------------------------------------------------
    // DELEGACIÓN DE EVENTOS: un solo listener para todos los botones de tareas
    // -----------------------------------------------------------------------
    document.addEventListener('click', (e) => {

        // COMPLETAR tarea
        // Recargamos la página porque necesitamos que PHP pinte la fecha de
        // finalización con hora exacta (viene de la BD tras el UPDATE).
        const btnCompletar = e.target.closest('.completar-tarea');
        if (btnCompletar) {
            const id = btnCompletar.dataset.id;
            enviarAccion({ completar_id: id }).then(data => {
                if (data.success) location.reload();
            });
        }

        // ELIMINAR tarea
        const btnEliminar = e.target.closest('.eliminar-tarea');
        if (btnEliminar) {
            if (!confirm('¿Seguro que quieres eliminar esta tarea?')) return;
            const id = btnEliminar.dataset.id;
            enviarAccion({ eliminar_id: id }).then(data => {
                if (data.success) {
                    // Eliminamos el elemento del DOM sin recargar
                    const item = btnEliminar.closest('.todo-item');
                    if (item) item.remove();

                    // Volvemos a comprobar si quedan tareas visibles
                    const listId = document.querySelector('.list-menu li.active')?.dataset.list ?? '0';
                    filtrarTareas(listId);
                }
            });
        }

        // EDITAR tarea
        const btnEditar = e.target.closest('.editar-tarea');
        if (btnEditar) {
            const item = btnEditar.closest('.todo-item');
            if (!item) return;
            const viewDiv = item.querySelector('.todo-view');
            const editForm = item.querySelector('.todo-edit');
            if (!editForm || !viewDiv) return;

            // Rellenar campos con valores actuales
            editForm.texto.value = item.dataset.texto || '';
            editForm.descripcion.value = item.dataset.descripcion || '';

            // Fecha y categoría actuales
            const fechaLimite = item.querySelector('.todo-meta')?.textContent?.includes('Vence:') ? item.querySelector('.todo-meta').textContent.replace(/[^\d\-T:]/g, '').replace('Vence:', '').trim() : '';
            if (fechaLimite) editForm.fecha_limite.value = fechaLimite;
            else editForm.fecha_limite.value = '';

            // Seleccionar categoría actual
            if (editForm.id_lista) {
                editForm.id_lista.value = item.dataset.list || '0';
            }

            // Mostrar formulario y ocultar vista
            viewDiv.style.display = 'none';
            editForm.style.display = 'block';

            // Al guardar
            editForm.onsubmit = function(ev) {
                ev.preventDefault();
                const nuevoTexto = editForm.texto.value.trim();
                const nuevaDesc = editForm.descripcion.value.trim();
                const nuevaFecha = editForm.fecha_limite.value;
                const nuevaCat = editForm.id_lista.value;
                if (nuevoTexto === '') {
                    alert('El texto de la tarea no puede estar vacío.');
                    return;
                }
                enviarAccion({ editar_id: item.dataset.id, nuevo_texto: nuevoTexto, nueva_descripcion: nuevaDesc, nueva_fecha: nuevaFecha, nueva_categoria: nuevaCat }).then(data => {
                    if (data.success) location.reload();
                });
            };

            // Al cancelar
            editForm.querySelector('.btn-cancelar').onclick = function() {
                editForm.style.display = 'none';
                viewDiv.style.display = '';
            };
        }

        // POSTERGAR tarea
        const btnPostergar = e.target.closest('.postergar-tarea');
        if (btnPostergar) {
            const id = btnPostergar.dataset.id;
            enviarAccion({ postergar_id: id }).then(data => {
                if (data.success) location.reload();
            });
        }
    });

});
