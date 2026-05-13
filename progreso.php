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

// Tareas por día esta semana (lun=0 ... dom=6)
$inicioSemana = new DateTime('monday this week');
$datosSemana  = array_fill(0, 7, 0);
foreach ($tareas as $t) {
    if (!$t['completada'] || empty($t['fecha_finalizacion'])) continue;
    $ft   = new DateTime($t['fecha_finalizacion']);
    $diff = (int) $inicioSemana->diff($ft)->days;
    if ($diff >= 0 && $diff <= 6 && !$inicioSemana->diff($ft)->invert) {
        $datosSemana[$diff]++;
    }
}

// Día con más tareas completadas (para mostrar insight)
$maxDia    = max($datosSemana);
$diasNombre = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
$mejorDia  = $maxDia > 0 ? $diasNombre[array_search($maxDia, $datosSemana)] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progreso | ToDoWeb</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <h2>Hola, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></h2>
    <a href="index.php"    class="sidebar-link">← Mis listas</a>
    <a href="pomodoro.php" class="sidebar-link">🍅 Pomodoro</a>
    <a href="logout.php"   class="sidebar-link sidebar-link--danger">Cerrar sesión</a>
  </aside>

  <!-- MAIN -->
  <main class="main prog-main">

    <!-- CABECERA -->
    <div class="prog-header">
      <div>
        <p class="prog-header-sub">Panel de estadísticas</p>
        <h1 class="prog-header-titulo">Mi progreso</h1>
      </div>
      <!-- Barra de progreso global en la cabecera -->
      <div class="prog-header-right">
        <div class="prog-header-barra-info">
          <span class="prog-header-pct-num"><?= $porcentaje ?>%</span>
          <span class="prog-header-pct-label">completado hoy</span>
        </div>
        <div class="prog-header-barra-bg">
          <div class="prog-header-barra-fill" style="width:<?= $porcentaje ?>%"></div>
        </div>
      </div>
    </div>

    <!-- TARJETAS RESUMEN -->
    <div class="prog-resumen">

      <div class="prog-stat prog-stat--verde">
        <span class="prog-stat-icono">✔</span>
        <div class="prog-stat-datos">
          <span class="prog-stat-num"><?= $tareas_completadas ?></span>
          <span class="prog-stat-label">Completadas</span>
        </div>
        <div class="prog-stat-barra-bg">
          <div class="prog-stat-barra" style="width:<?= $tareas_totales > 0 ? round(($tareas_completadas/$tareas_totales)*100) : 0 ?>%"></div>
        </div>
      </div>

      <div class="prog-stat prog-stat--rojo">
        <span class="prog-stat-icono">⏳</span>
        <div class="prog-stat-datos">
          <span class="prog-stat-num"><?= $tareas_pendientes ?></span>
          <span class="prog-stat-label">Pendientes</span>
        </div>
        <div class="prog-stat-barra-bg">
          <div class="prog-stat-barra" style="width:<?= $tareas_totales > 0 ? round(($tareas_pendientes/$tareas_totales)*100) : 0 ?>%"></div>
        </div>
      </div>

      <div class="prog-stat prog-stat--lila">
        <span class="prog-stat-icono">📋</span>
        <div class="prog-stat-datos">
          <span class="prog-stat-num"><?= $tareas_totales ?></span>
          <span class="prog-stat-label">Total</span>
        </div>
      </div>

      <!-- Insight: mejor día de la semana -->
      <?php if ($mejorDia): ?>
      <div class="prog-stat prog-stat--dorado">
        <span class="prog-stat-icono">⚡</span>
        <div class="prog-stat-datos">
          <span class="prog-stat-num" style="font-size:1.1rem;"><?= $mejorDia ?></span>
          <span class="prog-stat-label">Tu mejor día</span>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- GRÁFICAS -->
    <div class="prog-graficas">

      <div class="prog-grafica-card">
        <p class="prog-grafica-titulo">Distribución</p>
        <div class="prog-grafica-canvas">
          <canvas id="grafica-dona"></canvas>
        </div>
      </div>

      <div class="prog-grafica-card">
        <p class="prog-grafica-titulo">Completadas esta semana</p>
        <div class="prog-grafica-canvas">
          <canvas id="grafica-barras"></canvas>
        </div>
      </div>

    </div>

    <!-- HISTORIAL -->
    <?php if (!empty($tareas)): ?>
    <div class="prog-historial">
      <div class="prog-historial-cabecera">
        <h2 class="prog-historial-titulo">Historial</h2>
        <span class="prog-historial-badge"><?= count($tareas) ?> tareas</span>
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
                <td><?= !empty($t['fecha_limite']) ? formatear_fecha($t['fecha_limite']) : '<span class="sin-fecha">—</span>' ?></td>
                <td><?= !empty($t['fecha_finalizacion']) ? date('d/m/Y H:i', strtotime($t['fecha_finalizacion'])) : '<span class="sin-fecha">—</span>' ?></td>
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

<!-- Chart.js desde CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Pasamos los datos de PHP a JS como variables globales
// Los definimos DESPUÉS de cargar Chart.js para que todo esté disponible
const DATOS_DONA   = { completadas: <?= $tareas_completadas ?>, pendientes: <?= $tareas_pendientes ?> };
const DATOS_SEMANA = <?= json_encode(array_values($datosSemana)) ?>;

// Día de hoy en formato 0=lun ... 6=dom
const HOY_IDX = (new Date().getDay() + 6) % 7;

// ── DONA ──────────────────────────────────────────────────────────────
const ctxDona = document.getElementById('grafica-dona').getContext('2d');
new Chart(ctxDona, {
    type: 'doughnut',
    data: {
        labels: ['Completadas', 'Pendientes'],
        datasets: [{
            data: [DATOS_DONA.completadas, DATOS_DONA.pendientes],
            backgroundColor: ['rgba(34,197,94,0.75)', 'rgba(239,68,68,0.6)'],
            borderColor:     ['rgba(34,197,94,1)',    'rgba(239,68,68,1)'],
            borderWidth: 2,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: 'rgba(216,180,254,0.65)', font: { size: 11 }, boxWidth: 12, padding: 14 }
            },
            tooltip: {
                backgroundColor: 'rgba(26,10,46,0.95)',
                titleColor: '#e9d5ff',
                bodyColor: 'rgba(216,180,254,0.7)',
                borderColor: 'rgba(192,132,252,0.2)',
                borderWidth: 1
            }
        }
    }
});

// ── BARRAS ────────────────────────────────────────────────────────────
const ctxBarras = document.getElementById('grafica-barras').getContext('2d');
new Chart(ctxBarras, {
    type: 'bar',
    data: {
        labels: ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'],
        datasets: [{
            label: 'Completadas',
            data: DATOS_SEMANA,
            backgroundColor: DATOS_SEMANA.map((_, i) =>
                i === HOY_IDX ? 'rgba(192,132,252,0.85)' : 'rgba(192,132,252,0.25)'
            ),
            borderColor: DATOS_SEMANA.map((_, i) =>
                i === HOY_IDX ? 'rgba(192,132,252,1)' : 'rgba(192,132,252,0.4)'
            ),
            borderWidth: 1.5,
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: { label: c => ` ${c.raw} tarea${c.raw !== 1 ? 's' : ''}` },
                backgroundColor: 'rgba(26,10,46,0.95)',
                titleColor: '#e9d5ff',
                bodyColor: 'rgba(216,180,254,0.7)',
                borderColor: 'rgba(192,132,252,0.2)',
                borderWidth: 1
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: 'rgba(216,180,254,0.5)', font: { size: 11 } },
                border: { display: false }
            },
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, color: 'rgba(216,180,254,0.35)', font: { size: 10 } },
                grid: { color: 'rgba(192,132,252,0.07)' },
                border: { display: false }
            }
        }
    }
});
</script>
</body>
</html>