<?php
include_once 'includes/db.php';
include_once 'includes/session.php';
include_once 'includes/functions.php';
include_once 'includes/notifications.php';

// Obtener datos según el tipo de usuario
if ($_SESSION['rol'] === 'guest') {
    $listas   = [];
    $tareas   = obtenerTareasInvitado();
    $avisos   = [];
    $proximas = [];
} else {
    $listas   = obtenerListasUsuario($_SESSION['usuario_id']);
    $tareas   = obtenerTareasUsuario($_SESSION['usuario_id']);
    $avisos   = obtenerAvisosUsuario($_SESSION['usuario_id']);
    $proximas = obtenerTareasProximas($_SESSION['usuario_id']);
}

$csrf = generarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDoWeb - Mis Listas</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="app">

    <!-- =====================================================
         SIDEBAR
         ===================================================== -->
    <aside class="sidebar">
      <h2>Hola, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></h2>

      <!-- Menú de categorías -->
      <ul class="list-menu" id="list-menu">
        <li data-list="0" class="active">Mi Día</li>
        <?php foreach ($listas as $L): ?>
          <li data-list="<?= (int) $L['id'] ?>">
            <?= htmlspecialchars($L['nombre'], ENT_QUOTES, 'UTF-8') ?>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php if ($_SESSION['rol'] !== 'guest'): ?>
      <!-- Formulario nueva categoría -->
      <form action="controllers/tareasController.php" method="POST" class="sidebar-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input
          type="text"
          name="nombre_lista"
          placeholder="+ Nueva categoría"
          required
          maxlength="60"
          autocomplete="off"
          onkeydown="if(event.key==='Enter'){this.closest('form').submit();}"
        >
        <button type="submit" name="add_list" class="btn-hidden"></button>
      </form>
      <?php endif; ?>

      <!-- Próximas a vencer: debajo del menú, ordenadas por fecha -->
      <?php if (!empty($proximas)): ?>
        <div class="sidebar-proximas">
          <p class="sidebar-proximas-titulo">⏰ Próximas a vencer</p>
          <?php foreach ($proximas as $p): ?>
            <?php
              $horas       = (strtotime($p['fecha_limite']) - time()) / 3600;
              $clase_extra = $horas < 24 ? 'vence-hoy' : '';
            ?>
            <div class="proxima-item <?= $clase_extra ?>"
                 title="<?= htmlspecialchars($p['texto'], ENT_QUOTES, 'UTF-8') ?>">
              <span class="proxima-item-texto">
                <?= htmlspecialchars(mb_strimwidth($p['texto'], 0, 30, '…'), ENT_QUOTES, 'UTF-8') ?>
              </span>
              <span class="proxima-item-tiempo">
                <?= tiempo_restante($p['fecha_limite']) ?> — <?= formatear_fecha($p['fecha_limite']) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <a href="progreso.php" class="sidebar-link">📊 Progreso</a>
      <a href="pomodoro.php" class="sidebar-link">🍅 Pomodoro</a>
      <a href="logout.php"   class="sidebar-link sidebar-link--danger">Cerrar sesión</a>
    </aside>

    <!-- =====================================================
         CONTENIDO PRINCIPAL
         ===================================================== -->
    <main class="main">
      <header class="main-header">
        <h1 id="current-list-title">Mi Día</h1>
      </header>

      <!-- Formulario nueva tarea -->
      <form class="todo-form" action="controllers/tareasController.php" method="POST" id="form-nueva-tarea">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <!-- Fila 1: texto + botón -->
        <div class="form-row-top">
          <input
            type="text"
            name="texto"
            placeholder="Escribe una tarea…"
            required
            maxlength="300"
            autocomplete="off"
          >
          <button type="submit" name="add">Añadir</button>
        </div>

        <!-- Fila 2: fecha+hora y categoría -->
        <div class="form-row-bottom">
          <div class="form-label-group">
            <label for="campo-fecha">📅 Vence el</label>
            <input
              type="datetime-local"
              name="fecha_limite"
              id="campo-fecha"
              min="<?= date('Y-m-d\TH:i') ?>"
            >
          </div>

          <div class="form-label-group">
            <label for="select-categoria">🏷 Categoría</label>
            <select name="id_lista" id="select-categoria">
              <option value="0">— Sin categoría (Mi Día) —</option>
              <?php foreach ($listas as $L): ?>
                <option value="<?= (int) $L['id'] ?>">
                  <?= htmlspecialchars($L['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </form>

      <!-- Lista de tareas -->
      <ul id="todo-list">
        <?php foreach ($tareas as $t): ?>
          <?php
            $ahora   = new DateTime();
            $vencida = $t['fecha_limite']
                       && new DateTime($t['fecha_limite']) < $ahora
                       && !$t['completada'];
          ?>
          <li class="todo-item" data-list="<?= (int) ($t['id_lista'] ?? 0) ?>">

            <span class="todo-text <?= $t['completada'] ? 'done' : '' ?>">
              <?= htmlspecialchars($t['texto'], ENT_QUOTES, 'UTF-8') ?>

              <span class="todo-badges">
                <?php if (!empty($t['fecha_limite'])): ?>
                  <span class="todo-meta <?= $vencida ? 'badge-vencida' : '' ?>">
                    ⏰ Vence: <?= formatear_fecha($t['fecha_limite']) ?>
                  </span>
                <?php endif; ?>

                <?php if (!empty($t['fecha_finalizacion'])): ?>
                  <span class="todo-meta badge-fin">
                    ✔ Completada: <?= formatear_fecha($t['fecha_finalizacion']) ?>
                  </span>
                <?php endif; ?>
              </span>
            </span>

            <div class="actions">
              <button class="btn-icon completar-tarea" data-id="<?= (int) $t['id'] ?>" title="Completar/Desmarcar">
                <?= $t['completada'] ? '↩️' : '✔️' ?>
              </button>
              <button class="btn-icon editar-tarea"
                data-id="<?= (int) $t['id'] ?>"
                data-texto="<?= htmlspecialchars($t['texto'], ENT_QUOTES, 'UTF-8') ?>"
                title="Editar">
                📝
              </button>
              <button class="btn-icon eliminar-tarea" data-id="<?= (int) $t['id'] ?>" title="Eliminar">
                🗑️
              </button>
              <?php if ((int)($t['postergaciones'] ?? 0) < (int)($t['max_postergaciones'] ?? 3)): ?>
                <button class="btn-icon postergar-tarea" data-id="<?= (int) $t['id'] ?>" title="Postergar 1 día">
                  +1 día
                </button>
              <?php endif; ?>
            </div>

          </li>
        <?php endforeach; ?>
      </ul>

      <p id="msg-sin-tareas" class="msg-sin-tareas">
        No hay tareas en esta categoría todavía.
      </p>

    </main>
  </div>

  <script>
    const CSRF_TOKEN = <?= json_encode($csrf) ?>;
  </script>
  <script src="assets/js/app.js"></script>
</body>
</html>
