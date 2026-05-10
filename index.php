<?php
include_once 'includes/db.php';
include_once 'includes/session.php';   // Protege la ruta: redirige si no hay sesion
include_once 'includes/functions.php';
include_once 'includes/notifications.php';

// Obtener datos segun el tipo de usuario
if ($_SESSION['rol'] === 'guest') {
    $listas = [];
    $tareas = obtenerTareasInvitado();
    $avisos = [];
} else {
    $listas = obtenerListasUsuario($_SESSION['usuario_id']);
    $tareas = obtenerTareasUsuario($_SESSION['usuario_id']);
    $avisos = obtenerAvisosUsuario($_SESSION['usuario_id']);
}

// Generar token CSRF para los formularios de esta pagina
$csrf = generarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDoWeb - Mis Listas</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .done { text-decoration: line-through; opacity: 0.5; }
        .actions { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }
        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            padding: 4px 6px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .btn-icon:hover { background: rgba(0,0,0,0.07); }
        .sidebar-form { padding: 10px 15px; margin-top: 10px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-form input {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
            color: #fff;
            font-size: 0.9rem;
            outline: none;
        }
        .sidebar-form input::placeholder { color: rgba(255,255,255,0.6); }
        .sidebar-link {
            display: block;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 10px;
            margin-top: 8px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .sidebar-link:hover { background: rgba(255,255,255,0.15); color: #fff; }
        .aviso {
            padding: 10px 18px;
            border-radius: 10px;
            margin-bottom: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            max-width: 700px;
            width: 100%;
        }
        .aviso.proxima { background: rgba(246,173,85,0.25); color: #7b4f00; border: 1px solid rgba(246,173,85,0.5); }
        .aviso.vencida { background: rgba(252,129,129,0.25); color: #7b1a1a; border: 1px solid rgba(252,129,129,0.5); }
        .todo-text { flex-grow: 1; word-break: break-word; }
        .todo-meta {
            display: block;
            margin-top: 4px;
            font-size: 11px;
            color: #3182ce;
            background: #ebf4ff;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 700;
            text-transform: uppercase;
            width: fit-content;
        }
        .badge-vencida { background: #fff5f5; color: #c53030; }
    </style>
</head>
<body>
  <div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <h2>Hola, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></h2>

      <ul class="list-menu" id="list-menu">
        <li data-list="0" class="active">Mi Dia</li>
        <?php foreach ($listas as $L): ?>
          <li data-list="<?= (int) $L['id'] ?>">
            <?= htmlspecialchars($L['nombre'], ENT_QUOTES, 'UTF-8') ?>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php if ($_SESSION['rol'] !== 'guest'): ?>
      <!-- Formulario para crear nueva lista (solo usuarios registrados) -->
      <form action="controllers/tareasController.php" method="POST" class="sidebar-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input
          type="text"
          name="nombre_lista"
          placeholder="+ Nueva lista"
          required
          maxlength="60"
          autocomplete="off"
          onkeydown="if(event.key==='Enter'){this.closest('form').submit();}"
        >
        <button type="submit" name="add_list" style="display:none;"></button>
      </form>
      <?php endif; ?>

      <a href="progreso.php" class="sidebar-link">Progreso</a>
      <a href="pomodoro.php" class="sidebar-link">Pomodoro</a>
      <a href="logout.php" class="sidebar-link" style="color:#fc8181;font-weight:600;">Cerrar sesión</a>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main">
      <header class="main-header">
        <h1 id="current-list-title">Mi Dia</h1>
      </header>

      <!-- Zona de avisos (solo usuarios registrados) -->
      <?php if (!empty($avisos)): ?>
        <section style="width:100%; max-width:700px; margin-bottom: 20px;">
          <?php foreach ($avisos as $aviso): ?>
            <div class="aviso <?= htmlspecialchars($aviso['tipo'], ENT_QUOTES, 'UTF-8') ?>">
              <?= $aviso['mensaje'] /* Ya escapado en notifications.php */ ?>
            </div>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <!-- Formulario para anadir nueva tarea -->
      <form class="todo-form" action="controllers/tareasController.php" method="POST" id="form-nueva-tarea">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="text"   name="texto"       placeholder="Escribe una tarea..." required maxlength="300" autocomplete="off">
        <input type="date"   name="fecha_limite" required min="<?= date('Y-m-d') ?>">
        <input type="hidden" name="id_lista"     id="category-id" value="0">
        <button type="submit" name="add">Anadir</button>
      </form>

      <!-- Lista de tareas -->
      <ul id="todo-list">
        <?php foreach ($tareas as $t): ?>
          <?php
            $hoy     = date('Y-m-d');
            $vencida = $t['fecha_limite'] && $t['fecha_limite'] < $hoy && !$t['completada'];
          ?>
          <li class="todo-item" data-list="<?= (int) ($t['id_lista'] ?? 0) ?>">
            <span class="todo-text <?= $t['completada'] ? 'done' : '' ?>">
              <?= htmlspecialchars($t['texto'], ENT_QUOTES, 'UTF-8') ?>
              <small class="todo-meta <?= $vencida ? 'badge-vencida' : '' ?>">
                <?php
                  function formatear_fecha($fecha) {
                    if (!$fecha || $fecha === '-') return '-';
                    $f = date_create($fecha);
                    if (!$f) return htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8');
                    return date_format($f, 'd/m/Y H:i');
                  }
                ?>
                Alta: <?= formatear_fecha($t['fecha_alta'] ?? '-') ?> |
                Limite: <?= formatear_fecha($t['fecha_limite'] ?? '-') ?>
                <?php if (!empty($t['fecha_finalizacion'])): ?>
                  | Fin: <?= formatear_fecha($t['fecha_finalizacion']) ?>
                <?php endif; ?>
              </small>
            </span>

            <div class="actions">
              <button class="btn-icon completar-tarea"  data-id="<?= (int) $t['id'] ?>" title="Completar">
                <?= $t['completada'] ? '↩️' : '✔️' ?>
              </button>
              <button class="btn-icon editar-tarea"
                data-id="<?= (int) $t['id'] ?>"
                data-texto="<?= htmlspecialchars($t['texto'], ENT_QUOTES, 'UTF-8') ?>"
                title="Editar">
                📝
              </button>
              <button class="btn-icon eliminar-tarea" data-id="<?= (int) $t['id'] ?>" title="Eliminar" style="color:red;">
                🗑️
              </button>
              <?php if ((int)($t['postergaciones'] ?? 0) < (int)($t['max_postergaciones'] ?? 3)): ?>
                <button class="btn-icon postergar-tarea" data-id="<?= (int) $t['id'] ?>" title="Postergar 1 dia">
                  Postergar
                </button>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>

    </main>
  </div><!-- fin .app -->

  <script>
    // Pasar el token CSRF a JavaScript para que las peticiones AJAX lo incluyan
    const CSRF_TOKEN = <?= json_encode($csrf) ?>;
  </script>
  <script src="assets/js/app.js"></script>
</body>
</html>
