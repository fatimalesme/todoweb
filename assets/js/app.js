document.addEventListener('DOMContentLoaded', () => {

    // -----------------------------------------------------------------------
    // FILTRADO DE TAREAS POR LISTA
    // -----------------------------------------------------------------------
    const listMenu      = document.getElementById('list-menu');
    const currentTitle  = document.getElementById('current-list-title');
    const categoryInput = document.getElementById('category-id');
    const todoItems     = document.querySelectorAll('.todo-item');

    function filtrarTareas(listId) {
        todoItems.forEach(item => {
            // Si la lista es "0" (Mi Dia) se muestran todas
            if (listId === '0' || item.dataset.list === listId) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Al cargar la pagina mostrar "Mi Dia" (todas las tareas)
    filtrarTareas('0');

    listMenu.addEventListener('click', (e) => {
        if (e.target.tagName === 'LI') {
            const listId = e.target.dataset.list;

            currentTitle.textContent = e.target.textContent.trim();
            categoryInput.value      = listId;

            document.querySelectorAll('.list-menu li').forEach(li => li.classList.remove('active'));
            e.target.classList.add('active');

            filtrarTareas(listId);
        }
    });

    // -----------------------------------------------------------------------
    // FUNCION AUXILIAR PARA PETICIONES AJAX
    // CSRF_TOKEN se define en index.php como variable global antes de cargar este script
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
    // DELEGACION DE EVENTOS: un solo listener para todos los botones de tareas
    // -----------------------------------------------------------------------
    document.addEventListener('click', (e) => {

        // COMPLETAR tarea
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
            if (!confirm('Seguro que quieres eliminar esta tarea?')) return;
            const id = btnEliminar.dataset.id;
            enviarAccion({ eliminar_id: id }).then(data => {
                if (data.success) {
                    // Eliminar el elemento del DOM sin recargar la pagina
                    const item = btnEliminar.closest('.todo-item');
                    if (item) item.remove();
                }
            });
        }

        // EDITAR tarea
        const btnEditar = e.target.closest('.editar-tarea');
        if (btnEditar) {
            const id        = btnEditar.dataset.id;
            const textoActual = btnEditar.dataset.texto;
            const nuevoTexto  = prompt('Editar tarea:', textoActual);

            if (nuevoTexto === null) return; // El usuario cancelo
            if (nuevoTexto.trim() === '') {
                alert('El texto de la tarea no puede estar vacio.');
                return;
            }

            enviarAccion({ editar_id: id, nuevo_texto: nuevoTexto.trim() }).then(data => {
                if (data.success) location.reload();
            });
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
