<?php
include_once 'includes/db.php';
include_once 'includes/session.php';
include_once 'includes/functions.php';

$usuario_id         = $_SESSION['usuario_id'] ?? null;
$tareas_completadas = contarTareasCompletadas($usuario_id);
$tareas_pendientes  = contarTareasPendientes($usuario_id);
$tareas_totales     = $tareas_completadas + $tareas_pendientes;
// Solo pedimos la lista completa para el historial (no para contar)
$tareas             = $usuario_id ? obtenerTareasUsuario($usuario_id) : [];
$csrf               = generarTokenCSRF();
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
    <aside class="sidebar">
      <h2>Hola, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></h2>
      <a href="index.php"  class="sidebar-link">← Mis listas</a>
      <a href="pomodoro.php" class="sidebar-link">Pomodoro</a>
      <a href="logout.php" class="sidebar-link" style="color:#fc8181;font-weight:600;">Cerrar sesión</a>
    </aside>

    <main class="main">
      <header class="main-header">
        <h1>Mi progreso</h1>
      </header>

      <!-- Tarjetas de resumen -->
      <div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
        <div style="background:#f0fff4;border:1px solid #9ae6b4;border-radius:12px;padding:16px 24px;min-width:140px;">
          <div style="font-size:2rem;font-weight:700;color:#276749;"><?= $tareas_completadas ?></div>
          <div style="color:#2f855a;font-size:0.9rem;">Completadas</div>
        </div>
        <div style="background:#fff5f5;border:1px solid #feb2b2;border-radius:12px;padding:16px 24px;min-width:140px;">
          <div style="font-size:2rem;font-weight:700;color:#c53030;"><?= $tareas_pendientes ?></div>
          <div style="color:#c53030;font-size:0.9rem;">Pendientes</div>
        </div>
        <div style="background:#ebf4ff;border:1px solid #90cdf4;border-radius:12px;padding:16px 24px;min-width:140px;">
          <div style="font-size:2rem;font-weight:700;color:#2b6cb0;"><?= $tareas_totales ?></div>
          <div style="color:#2b6cb0;font-size:0.9rem;">Total</div>
        </div>
      </div>

      <!-- Gráfica -->
      <div style="max-width:300px;margin-bottom:32px;">
        <canvas id="grafica-progreso"></canvas>
      </div>

      <!-- Historial -->
      <?php if (empty($tareas)): ?>
        <p style="color:#718096;">Aún no tienes tareas registradas.</p>
      <?php else: ?>
        <h2 style="margin-bottom:12px;">Historial de tareas</h2>
        <div style="overflow-x:auto;">
          <table style="width:100%;max-width:720px;border-collapse:collapse;font-size:0.9rem;">
            <thead>
              <tr style="background:#f7fafc;text-align:left;">
                <th style="padding:10px 12px;border-bottom:2px solid #e2e8f0;">Tarea</th>
                <th style="padding:10px 12px;border-bottom:2px solid #e2e8f0;">Alta</th>
                <th style="padding:10px 12px;border-bottom:2px solid #e2e8f0;">Límite</th>
                <th style="padding:10px 12px;border-bottom:2px solid #e2e8f0;">Finalización</th>
                <th style="padding:10px 12px;border-bottom:2px solid #e2e8f0;">Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tareas as $t): ?>
                <tr style="border-bottom:1px solid #edf2f7;">
                  <td style="padding:10px 12px;"><?= htmlspecialchars($t['texto'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:10px 12px;"><?= formatear_fecha($t['fecha_alta'] ?? '-') ?></td>
                  <td style="padding:10px 12px;"><?= formatear_fecha($t['fecha_limite'] ?? '-') ?></td>
                  <td style="padding:10px 12px;"><?= !empty($t['fecha_finalizacion']) ? formatear_fecha($t['fecha_finalizacion']) : '-' ?></td>
                  <td style="padding:10px 12px;">
                    <?php if ($t['completada']): ?>
                      <span style="background:#f0fff4;color:#276749;padding:3px 10px;border-radius:20px;font-size:0.8rem;font-weight:600;">✔ Completada</span>
                    <?php else: ?>
                      <span style="background:#fff5f5;color:#c53030;padding:3px 10px;border-radius:20px;font-size:0.8rem;font-weight:600;">⏳ Pendiente</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <script src="assets/js/graficas.js"></script>
  <script>
    const completadas = <?= $tareas_completadas ?>;
    const pendientes  = <?= $tareas_pendientes ?>;
    window.datosProgreso = { completadas, pendientes };
  </script>
</body>
</html>
