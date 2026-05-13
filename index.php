<?php
include_once 'includes/db.php';
include_once 'includes/session.php';
include_once 'includes/functions.php';
include_once 'includes/notifications.php';

// Cargamos datos según si es usuario registrado o invitado
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

$csrf         = generarTokenCSRF();
$gamificacion = ($_SESSION['rol'] !== 'guest')
    ? obtenerDatosGamificacion($_SESSION['usuario_id'])
    : ['xp' => 0, 'racha' => 0, 'logros' => [], 'nivel' => obtenerNivel(0)];

// Datos del calendario — mes actual y qué días tienen tareas
$hoy       = new DateTime();
$anio      = (int) $hoy->format('Y');
$mes       = (int) $hoy->format('n');
$diasMes   = (int) $hoy->format('t');
$diaHoy    = (int) $hoy->format('j');
$iniciaSem = (int) (new DateTime("$anio-$mes-01"))->format('N') - 1; // 0=lunes

$diasConTarea = [];
$diasUrgentes = [];
$manana       = new DateTime('tomorrow');
foreach ($tareas as $t) {
    if (empty($t['fecha_limite']) || $t['completada']) continue;
    $ft = new DateTime($t['fecha_limite']);
    if ((int)$ft->format('n') !== $mes || (int)$ft->format('Y') !== $anio) continue;
    $dia = (int) $ft->format('j');
    $diasConTarea[$dia] = ($diasConTarea[$dia] ?? 0) + 1;
    if ($ft <= $manana) $diasUrgentes[$dia] = true;
}
$nombresMes = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
               'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDoWeb</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">

  <!-- ── SIDEBAR ──────────────────────────────────────────── -->
  <aside class="sidebar">
    <h2>Hola, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></h2>

    <!-- Categorías -->
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

    <!-- Calendario del mes -->
    <p class="sidebar-seccion-titulo">Este mes</p>
    <div class="sidebar-cal">
      <div class="sidebar-cal-header">
        <span class="sidebar-cal-mes"><?= $nombresMes[$mes] ?> <?= $anio ?></span>
      </div>
      <div class="sidebar-cal-body">
        <div class="sidebar-cal-labels">
          <?php foreach (['L','M','X','J','V','S','D'] as $l): ?>
            <div class="sidebar-cal-label"><?= $l ?></div>
          <?php endforeach; ?>
        </div>
        <div class="sidebar-cal-grid">
          <?php
          // Celdas vacías antes del primer día del mes
          for ($i = 0; $i < $iniciaSem; $i++):
          ?><div class="cal-d cal-vacio"></div><?php
          endfor;
          // Un div por cada día del mes con su clase correspondiente
          for ($d = 1; $d <= $diasMes; $d++):
            $c = 'cal-d';
            if ($d < $diaHoy)                 $c .= ' cal-pasado';
            if ($d === $diaHoy)               $c .= ' cal-hoy';
            if (isset($diasUrgentes[$d]))      $c .= ' cal-urgente';
            elseif (isset($diasConTarea[$d]) && $diasConTarea[$d] > 1) $c .= ' cal-varios';
            elseif (isset($diasConTarea[$d])) $c .= ' cal-t1';
          ?>
            <div class="<?= $c ?>"><?= $d ?></div>
          <?php endfor; ?>
        </div>
      </div>
      <div class="sidebar-cal-leyenda">
        <div class="cal-ley"><div class="cal-ley-dot" style="background:#c084fc;"></div>Tareas</div>
        <div class="cal-ley"><div class="cal-ley-dot" style="background:#f87171;"></div>Urgente</div>
        <div class="cal-ley"><div class="cal-ley-dot" style="background:#9333ea;"></div>Hoy</div>
      </div>
    </div>

    <!-- Próximas a vencer -->
    <?php if (!empty($proximas)): ?>
      <div class="sidebar-proximas">
        <p class="sidebar-proximas-titulo">⏰ Próximas a vencer</p>
        <?php foreach ($proximas as $p):
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

    <!-- Herramientas -->
    <p class="sidebar-seccion-titulo">Herramientas</p>
    <a href="progreso.php" class="nav-card">
      <span class="nav-card-icon nav-card-icon--progreso">📊</span>
      <span class="nav-card-texto">
        <span class="nav-card-nombre">Progreso</span>
        <span class="nav-card-sub">Ver mis estadísticas</span>
      </span>
    </a>
    <a href="pomodoro.php" class="nav-card">
      <span class="nav-card-icon nav-card-icon--pomodoro">🍅</span>
      <span class="nav-card-texto">
        <span class="nav-card-nombre">Pomodoro</span>
        <span class="nav-card-sub">Temporizador de enfoque</span>
      </span>
    </a>
    <?php endif; ?>

    <a href="logout.php" class="sidebar-logout">↩ Cerrar sesión</a>
  </aside>

  <!-- ── CONTENIDO PRINCIPAL ───────────────────────────────── -->
  <main class="main">
    <header class="main-header">
      <h1 id="current-list-title">Mi Día</h1>
    </header>

    <div class="main-inner">

      <!-- Columna izquierda: formulario + lista de tareas -->
      <div class="main-content">

        <!-- Formulario nueva tarea -->
        <form class="todo-form" action="controllers/tareasController.php" method="POST" id="form-nueva-tarea">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

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

          <div class="form-row-desc">
            <textarea
              name="descripcion"
              placeholder="Descripción de la tarea (opcional)"
              maxlength="1000"
              rows="2"
            ></textarea>
          </div>

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
                <option value="0">— Mi Día —</option>
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
          <?php foreach ($tareas as $t):
            $ahora   = new DateTime();
            $vencida = $t['fecha_limite']
                       && new DateTime($t['fecha_limite']) < $ahora
                       && !$t['completada'];
            $desc    = $t['descripcion'] ?? '';
          ?>
            <li class="todo-item <?= $t['completada'] ? 'todo-item--done' : '' ?>"
                data-list="<?= (int) ($t['id_lista'] ?? 0) ?>"
                data-id="<?= (int) $t['id'] ?>"
                data-texto="<?= htmlspecialchars($t['texto'], ENT_QUOTES, 'UTF-8') ?>"
                data-descripcion="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>">

              <div class="todo-view">
                <span class="todo-text <?= $t['completada'] ? 'done' : '' ?>">
                  <?= htmlspecialchars($t['texto'], ENT_QUOTES, 'UTF-8') ?>
                </span>

                <?php if (!empty($desc)): ?>
                  <div class="todo-desc">
                    <?= nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8')) ?>
                  </div>
                <?php endif; ?>

                <span class="todo-badges">
                  <?php if (!empty($t['fecha_limite'])): ?>
                    <span class="todo-meta <?= $vencida ? 'badge-vencida' : '' ?>">
                      ⏰ Vence: <?= formatear_fecha($t['fecha_limite']) ?>
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($t['fecha_finalizacion'])): ?>
                    <span class="done-fecha">
                      ✓ Completada el <?= date('d/m/Y \a \l\a\s H:i', strtotime($t['fecha_finalizacion'])) ?>
                    </span>
                  <?php endif; ?>
                </span>

                <div class="actions">
                  <?php if ($t['completada']): ?>
                    <button class="btn-icon completar-tarea" data-id="<?= (int) $t['id'] ?>" title="Deshacer">↩️</button>
                    <button class="btn-icon eliminar-tarea"  data-id="<?= (int) $t['id'] ?>" title="Eliminar">🗑️</button>
                  <?php else: ?>
                    <button class="btn-icon completar-tarea" data-id="<?= (int) $t['id'] ?>" title="Completar">✔️</button>
                    <button class="btn-icon editar-tarea"
                      data-id="<?= (int) $t['id'] ?>"
                      data-texto="<?= htmlspecialchars($t['texto'], ENT_QUOTES, 'UTF-8') ?>"
                      data-descripcion="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>"
                      title="Editar">📝</button>
                    <button class="btn-icon eliminar-tarea" data-id="<?= (int) $t['id'] ?>" title="Eliminar">🗑️</button>
                    <?php if ((int)($t['postergaciones'] ?? 0) < (int)($t['max_postergaciones'] ?? 3)): ?>
                      <button class="btn-icon postergar-tarea" data-id="<?= (int) $t['id'] ?>" title="Postergar 1 día">+1 día</button>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Formulario de edición inline (oculto por defecto) -->
              <form class="todo-edit">
                <input type="text" name="texto" maxlength="300" required>
                <textarea name="descripcion" maxlength="1000" rows="2"></textarea>
                <input type="datetime-local" name="fecha_limite" min="<?= date('Y-m-d\TH:i') ?>">
                <select name="id_lista">
                  <option value="0">— Mi Día —</option>
                  <?php foreach ($listas as $L): ?>
                    <option value="<?= (int) $L['id'] ?>">
                      <?= htmlspecialchars($L['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="todo-edit-actions">
                  <button type="submit" class="btn-guardar">Guardar</button>
                  <button type="button" class="btn-cancelar">Cancelar</button>
                </div>
              </form>

            </li>
          <?php endforeach; ?>
        </ul>

        <p id="msg-sin-tareas" class="msg-sin-tareas">
          No hay tareas en esta categoría todavía.
        </p>

      </div><!-- fin .main-content -->

      <!-- Columna derecha: panel de gamificación -->
      <?php if ($_SESSION['rol'] !== 'guest'): ?>
      <aside class="gami-panel">

        <div class="gami-card">
          <p class="gami-card-titulo">Tu nivel</p>
          <div class="gami-nivel-icono"><?= $gamificacion['nivel']['actual']['icono'] ?></div>
          <p class="gami-nivel-nombre"><?= $gamificacion['nivel']['actual']['nombre'] ?></p>
          <p class="gami-nivel-sub"><?= $gamificacion['nivel']['actual']['sub'] ?></p>
          <div class="gami-xp-fila">
            <span><?= $gamificacion['xp'] ?> XP</span>
            <?php if ($gamificacion['nivel']['siguiente']): ?>
              <span>/ <?= $gamificacion['nivel']['siguiente']['min'] ?> XP</span>
            <?php else: ?>
              <span>Nivel máximo 🌌</span>
            <?php endif; ?>
          </div>
          <div class="gami-barra-fondo">
            <div class="gami-barra-relleno" style="width:<?= $gamificacion['nivel']['porcentaje'] ?>%"></div>
          </div>
        </div>

        <div class="gami-card">
          <p class="gami-card-titulo">Racha diaria</p>
          <div class="gami-racha-fila">
            <div>
              <span class="gami-racha-num"><?= $gamificacion['racha'] ?></span>
              <p class="gami-racha-label">días seguidos</p>
            </div>
            <span class="gami-racha-fuego">🔥</span>
          </div>
        </div>

        <div class="gami-card">
          <p class="gami-card-titulo">Logros</p>
          <div class="gami-logros-grid">
            <?php
            $todos_logros = [
                'primera_chispa'    => ['icono' => '⚡', 'nombre' => 'Primera chispa'],
                'orbita_estable'    => ['icono' => '🪐', 'nombre' => 'Órbita estable'],
                'campo_asteroides'  => ['icono' => '☄️',  'nombre' => 'Asteroides'],
                'tormenta_solar'    => ['icono' => '🌞', 'nombre' => 'Tormenta solar'],
                'gravedad_propia'   => ['icono' => '🌌', 'nombre' => 'Gravedad propia'],
                'explosion_estelar' => ['icono' => '💥', 'nombre' => 'Explosión estelar'],
            ];
            foreach ($todos_logros as $clave => $info):
                $desbloqueado = in_array($clave, $gamificacion['logros']);
            ?>
              <div class="gami-logro <?= $desbloqueado ? 'gami-logro--on' : 'gami-logro--off' ?>"
                   title="<?= $info['nombre'] ?>">
                <span class="gami-logro-icono"><?= $info['icono'] ?></span>
                <span class="gami-logro-nombre"><?= $info['nombre'] ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      </aside>
      <?php endif; ?>

    </div><!-- fin .main-inner -->
  </main>

</div>

<script>
    const CSRF_TOKEN = <?= json_encode($csrf) ?>;
</script>
<script src="assets/js/app.js"></script>
</body>
</html>