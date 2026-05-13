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
                      ? round(($tareas_completadas / $tareas_totales) * 100) : 0;

// ── Datos por día esta semana ─────────────────────────────────
$inicioSemana = new DateTime('monday this week');
$datosSemana  = array_fill(0, 7, 0);
foreach ($tareas as $t) {
    if (!$t['completada'] || empty($t['fecha_finalizacion'])) continue;
    $ft   = new DateTime($t['fecha_finalizacion']);
    $diff = (int) $inicioSemana->diff($ft)->days;
    if ($diff >= 0 && $diff <= 6 && !$inicioSemana->diff($ft)->invert)
        $datosSemana[$diff]++;
}

// Mejor día de la semana
$maxDia     = max($datosSemana);
$diasNombre = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
$mejorDia   = $maxDia > 0 ? $diasNombre[array_search($maxDia, $datosSemana)] : null;

// ── Datos de gamificación (XP, racha, logros) ─────────────────
$xp    = 0;
$racha = 0;
$logros = [];
if ($usuario_id && $conexion) {
    $stmt = $conexion->prepare('SELECT xp, racha, logros FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $stmt->bind_result($xp, $racha, $logros_json);
    $stmt->fetch();
    $stmt->close();
    $logros = $logros_json ? json_decode($logros_json, true) : [];
    $xp     = (int) $xp;
    $racha  = (int) $racha;
}

// Siguiente nivel
$niveles = [
    ['nombre'=>'Nebulosa',  'min'=>0],
    ['nombre'=>'Destello',  'min'=>100],
    ['nombre'=>'Fulgor',    'min'=>250],
    ['nombre'=>'Supernova', 'min'=>500],
];
$nivel_actual  = $niveles[0];
$nivel_sig     = null;
foreach ($niveles as $n) {
    if ($xp >= $n['min']) $nivel_actual = $n;
}
foreach ($niveles as $n) {
    if ($n['min'] > $xp) { $nivel_sig = $n; break; }
}
$xp_falta = $nivel_sig ? ($nivel_sig['min'] - $xp) : 0;

// Logros desbloqueados
$logros_total  = 6;
$logros_hechos = count($logros);
$logros_faltan = $logros_total - $logros_hechos;

// Días para "Órbita estable" (racha de 3)
$dias_racha_falta = max(0, 3 - $racha);

// Frase motivadora dinámica según datos reales
if ($porcentaje === 100)
    $frase = "¡Todo completado! Eres una Supernova de productividad 🌟";
elseif ($porcentaje >= 75)
    $frase = "Casi lo tienes, solo quedan {$tareas_pendientes} tareas. ¡No pares ahora!";
elseif ($porcentaje >= 50)
    $frase = "Vas por la mitad. Cada tarea que terminas te acerca a tu mejor versión.";
elseif ($tareas_completadas > 0)
    $frase = "Buen comienzo. La constancia es lo que diferencia a los que lo logran.";
else
    $frase = "Hoy es el mejor día para empezar. Tu primera tarea vale 20 XP ⚡";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progreso | ToDoWeb</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/progreso.css">
</head>
<body>
<div class="app">

  <aside class="sidebar">
    <h2>Hola, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></h2>
    <a href="index.php"    class="sidebar-link">← Mis listas</a>
    <a href="pomodoro.php" class="sidebar-link">🍅 Pomodoro</a>
    <a href="logout.php"   class="sidebar-link sidebar-link--danger">Cerrar sesión</a>
  </aside>

  <main class="main prog-main">

    <!-- CABECERA -->
    <div class="prog-header">
      <div>
        <p class="prog-header-sub">Panel de estadísticas</p>
        <h1 class="prog-header-titulo">Mi progreso</h1>
      </div>
      <div class="prog-header-right">
        <div class="prog-header-barra-info">
          <span class="prog-header-pct-num"><?= $porcentaje ?>%</span>
          <span class="prog-header-pct-label">completado</span>
        </div>
        <div class="prog-header-barra-bg">
          <div class="prog-header-barra-fill" style="width:<?= $porcentaje ?>%"></div>
        </div>
      </div>
    </div>

    <!-- TARJETAS RESUMEN -->
    <div class="prog-resumen">
      <div class="prog-stat prog-stat--verde">
        <div class="prog-stat-icono">✔</div>
        <div class="prog-stat-datos">
          <span class="prog-stat-num"><?= $tareas_completadas ?></span>
          <span class="prog-stat-label">Completadas</span>
          <div class="prog-stat-barra-bg">
            <div class="prog-stat-barra" style="width:<?= $tareas_totales > 0 ? round(($tareas_completadas/$tareas_totales)*100) : 0 ?>%"></div>
          </div>
        </div>
      </div>
      <div class="prog-stat prog-stat--rojo">
        <div class="prog-stat-icono">⏳</div>
        <div class="prog-stat-datos">
          <span class="prog-stat-num"><?= $tareas_pendientes ?></span>
          <span class="prog-stat-label">Pendientes</span>
          <div class="prog-stat-barra-bg">
            <div class="prog-stat-barra" style="width:<?= $tareas_totales > 0 ? round(($tareas_pendientes/$tareas_totales)*100) : 0 ?>%"></div>
          </div>
        </div>
      </div>
      <div class="prog-stat prog-stat--lila">
        <div class="prog-stat-icono">📋</div>
        <div class="prog-stat-datos">
          <span class="prog-stat-num"><?= $tareas_totales ?></span>
          <span class="prog-stat-label">Total</span>
        </div>
      </div>
      <?php if ($mejorDia): ?>
      <div class="prog-stat prog-stat--dorado">
        <div class="prog-stat-icono">⚡</div>
        <div class="prog-stat-datos">
          <span class="prog-stat-num"><?= $mejorDia ?></span>
          <span class="prog-stat-label">Tu mejor día</span>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- GRÁFICAS + MOTIVACIÓN -->
    <div class="prog-mid">

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

      <!-- Panel de motivación con datos reales -->
      <div class="prog-motiv">
        <p class="prog-motiv-titulo">✦ Datos y motivación</p>

        <div class="prog-motiv-item prog-motiv-item--verde">
          <span class="prog-motiv-icono">🔥</span>
          <div class="prog-motiv-texto">
            <strong><?= $racha ?> día<?= $racha !== 1 ? 's' : '' ?> de racha</strong>
            <?php if ($dias_racha_falta > 0): ?>
              <span>A <?= $dias_racha_falta ?> día<?= $dias_racha_falta !== 1 ? 's' : '' ?> del logro "Órbita estable"</span>
            <?php else: ?>
              <span>¡Logro "Órbita estable" desbloqueado! 🪐</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="prog-motiv-item prog-motiv-item--dorado">
          <span class="prog-motiv-icono">⚡</span>
          <div class="prog-motiv-texto">
            <strong><?= $xp ?> XP acumulados</strong>
            <?php if ($nivel_sig): ?>
              <span>Te faltan <?= $xp_falta ?> XP para nivel <?= $nivel_sig['nombre'] ?></span>
            <?php else: ?>
              <span>¡Nivel máximo alcanzado! Eres una Supernova 🌟</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="prog-motiv-item">
          <span class="prog-motiv-icono">🏆</span>
          <div class="prog-motiv-texto">
            <strong><?= $logros_hechos ?> de <?= $logros_total ?> logros</strong>
            <?php if ($logros_faltan > 0): ?>
              <span>Aún te quedan <?= $logros_faltan ?> logros por descubrir</span>
            <?php else: ?>
              <span>¡Todos los logros desbloqueados! Increíble 💥</span>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>

    <!-- FRASE MOTIVADORA DINÁMICA -->
    <div class="prog-frase">
      <span class="prog-frase-icono">💫</span>
      <div>
        <p class="prog-frase-texto"><?= htmlspecialchars($frase, ENT_QUOTES, 'UTF-8') ?></p>
        <span class="prog-frase-sub">
          <?= $tareas_completadas ?> tarea<?= $tareas_completadas !== 1 ? 's' : '' ?> completada<?= $tareas_completadas !== 1 ? 's' : '' ?> en total
        </span>
      </div>
    </div>

    <!-- HISTORIAL -->
    <?php if (!empty($tareas)): ?>
    <div class="prog-historial">
      <div class="prog-historial-cabecera">
        <h2 class="prog-historial-titulo">Historial</h2>
        <span class="prog-historial-badge"><?= count($tareas) ?> tareas</span>
      </div>
      <div class="tabla-wrapper prog-tabla">
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const DATOS_DONA   = { completadas: <?= $tareas_completadas ?>, pendientes: <?= $tareas_pendientes ?> };
const DATOS_SEMANA = <?= json_encode(array_values($datosSemana)) ?>;
const HOY_IDX      = (new Date().getDay() + 6) % 7;

new Chart(document.getElementById('grafica-dona').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Completadas','Pendientes'],
        datasets: [{
            data: [DATOS_DONA.completadas, DATOS_DONA.pendientes],
            backgroundColor: ['rgba(34,197,94,0.75)','rgba(239,68,68,0.6)'],
            borderColor:     ['rgba(34,197,94,1)',   'rgba(239,68,68,1)'],
            borderWidth: 2, hoverOffset: 4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '68%',
        plugins: {
            legend: { position:'bottom', labels:{ color:'rgba(216,180,254,0.6)', font:{size:10}, boxWidth:10, padding:10 }},
            tooltip: { backgroundColor:'rgba(26,10,46,0.95)', titleColor:'#e9d5ff', bodyColor:'rgba(216,180,254,0.7)', borderColor:'rgba(192,132,252,0.2)', borderWidth:1 }
        }
    }
});

new Chart(document.getElementById('grafica-barras').getContext('2d'), {
    type: 'bar',
    data: {
        labels: ['L','M','X','J','V','S','D'],
        datasets: [{
            data: DATOS_SEMANA,
            backgroundColor: DATOS_SEMANA.map((_,i) => i===HOY_IDX ? 'rgba(192,132,252,0.85)' : 'rgba(192,132,252,0.25)'),
            borderColor:     DATOS_SEMANA.map((_,i) => i===HOY_IDX ? 'rgba(192,132,252,1)'    : 'rgba(192,132,252,0.4)'),
            borderWidth: 1.5, borderRadius: 5, borderSkipped: false
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend:{ display:false }, tooltip:{ backgroundColor:'rgba(26,10,46,0.95)', titleColor:'#e9d5ff', bodyColor:'rgba(216,180,254,0.7)', borderColor:'rgba(192,132,252,0.2)', borderWidth:1 }},
        scales: {
            x: { grid:{ display:false }, ticks:{ color:'rgba(216,180,254,0.4)', font:{size:9} }, border:{ display:false }},
            y: { beginAtZero:true, ticks:{ stepSize:1, color:'rgba(216,180,254,0.3)', font:{size:9} }, grid:{ color:'rgba(192,132,252,0.05)' }, border:{ display:false }}
        }
    }
});
</script>
</body>
</html>