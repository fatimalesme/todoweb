<?php
include_once 'includes/db.php';
include_once 'includes/session.php';
include_once 'includes/functions.php';

$usuario_id         = $_SESSION['usuario_id'] ?? null;
$tareas_completadas = contarTareasCompletadas($usuario_id);
$tareas_pendientes  = contarTareasPendientes($usuario_id);
$tareas_totales     = $tareas_completadas + $tareas_pendientes;
$tareas             = $usuario_id ? obtenerTareasUsuario($usuario_id) : [];
$porcentaje         = $tareas_totales > 0
                      ? round(($tareas_completadas / $tareas_totales) * 100)
                      : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progreso | ToDoWeb</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app">

  <!-- ── SIDEBAR ──────────────────────────────────────────── -->
  <aside class="sidebar">
    <h2>Hola, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></h2>
    <a href="index.php"    class="sidebar-link">← Mis listas</a>
    <a href="pomodoro.php" class="sidebar-link">🍅 Pomodoro</a>
    <a href="logout.php"   class="sidebar-link sidebar-link--danger">Cerrar sesión</a>
  </aside>

  <!-- ── MAIN ─────────────────────────────────────────────── -->
  <main class="main prog-main">

    <!-- Cabecera con fondo destacado -->
    <div class="prog-header">
      <div>
        <p class="prog-header-sub">Panel de estadísticas</p>
        <h1 class="prog-header-titulo">Mi progreso</h1>
      </div>
      <div class="prog-header-pct">
        <span class="prog-header-pct-num"><?= $porcentaje ?>%</span>
        <span class="prog-header-pct-label">completado</span>
      </div>
    </div>

    <!-- ── TARJETAS DE RESUMEN ── -->
    <div class="prog-cards">

      <div class="prog-card prog-card--completadas">
        <div class="prog-card-icono">✔</div>
        <div class="prog-card-info">
          <span class="prog-card-num"><?= $tareas_completadas ?></span>
          <span class="prog-card-label">Completadas</span>
        </div>
        <?php if ($tareas_totales > 0): ?>
        <div class="prog-card-barra-wrap">
          <div class="prog-card-barra" style="width:<?= round(($tareas_completadas/$tareas_totales)*100) ?>%"></div>
        </div>
        <?php endif; ?>
      </div>

      <div class="prog-card prog-card--pendientes">
        <div class="prog-card-icono">⏳</div>
        <div class="prog-card-info">
          <span class="prog-card-num"><?= $tareas_pendientes ?></span>
          <span class="prog-card-label">Pendientes</span>
        </div>
        <?php if ($tareas_totales > 0): ?>
        <div class="prog-card-barra-wrap">
          <div class="prog-card-barra" style="width:<?= round(($tareas_pendientes/$tareas_totales)*100) ?>%"></div>
        </div>
        <?php endif; ?>
      </div>

      <div class="prog-card prog-card--total">
        <div class="prog-card-icono">📋</div>
        <div class="prog-card-info">
          <span class="prog-card-num"><?= $tareas_totales ?></span>
          <span class="prog-card-label">Total de tareas</span>
        </div>
      </div>

    </div>

    <!-- ── GRÁFICA ── -->
    <div class="prog-grafica-wrap">
      <div class="prog-grafica-titulo">Distribución de tareas</div>
      <?php if ($tareas_totales > 0): ?>
        <div class="prog-grafica-canvas">
          <canvas id="grafica-progreso"></canvas>
        </div>
      <?php else: ?>
        <p class="progreso-empty">Aún no tienes tareas registradas.</p>
      <?php endif; ?>
    </div>

    <!-- ── HISTORIAL ── -->
    <?php if (!empty($tareas)): ?>
    <div class="prog-historial">
      <div class="prog-historial-header">
        <h2 class="prog-historial-titulo">Historial de tareas</h2>
        <span class="prog-historial-count"><?= count($tareas) ?> tareas</span>
      </div>
      <div class="tabla-wrapper">
        <table class="tabla-historial">
          <thead>
            <tr>
              <th>Tarea</th>
              <th>Creada</th>
              <th>Límite</th>
              <th>Completada el</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tareas as $t): ?>
              <tr>
                <td><?= htmlspecialchars($t['texto'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= formatear_fecha($t['fecha_alta'] ?? '') ?></td>
                <td><?= !empty($t['fecha_limite'])      ? formatear_fecha($t['fecha_limite'])      : '<span class="sin-fecha">—</span>' ?></td>
                <td>
                  <?php if (!empty($t['fecha_finalizacion'])): ?>
                    <!-- Mostramos siempre día y hora exacta de finalización -->
                    <?= date('d/m/Y H:i', strtotime($t['fecha_finalizacion'])) ?>
                  <?php else: ?>
                    <span class="sin-fecha">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($t['completada']): ?>
                    <span class="badge-completada">✔ Completada</span>
                  <?php else: ?>
                    <span class="badge-pendiente">⏳ Pendiente</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>

<script>
  const completadas        = <?= $tareas_completadas ?>;
  const pendientes         = <?= $tareas_pendientes ?>;
  window.datosProgreso     = { completadas, pendientes };
</script>
<script src="assets/js/graficas.js"></script>
</body>
</html>