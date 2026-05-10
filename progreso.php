<?php
include_once 'includes/db.php';
include_once 'includes/session.php';
include_once 'includes/functions.php';

// Obtener datos de progreso
$usuario_id = $_SESSION['usuario_id'] ?? null;
$tareas_completadas = $usuario_id ? contarTareasCompletadas($usuario_id) : 0;
$tareas_totales = $usuario_id ? count(obtenerTareasUsuario($usuario_id)) : 0;
$pendientes = $tareas_totales - $tareas_completadas;
$tareas = $usuario_id ? obtenerTareasUsuario($usuario_id) : [];
$csrf = generarTokenCSRF();
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
        <h1>Progreso</h1>
        <div style="max-width:350px;margin:0 auto 20px auto;padding:10px;box-sizing:border-box;">
            <canvas id="grafica-progreso"></canvas>
        </div>
        <br>
        <h2 style="margin-top:30px;">Historial de tareas</h2>
        <table style="width:100%;max-width:600px;margin:20px 0;border-collapse:collapse;">
            <thead>
                <tr style="background:#f7fafc;">
                    <th style="padding:8px;border:1px solid #e2e8f0;">Tarea</th>
                    <th style="padding:8px;border:1px solid #e2e8f0;">Alta</th>
                    <th style="padding:8px;border:1px solid #e2e8f0;">Límite</th>
                    <th style="padding:8px;border:1px solid #e2e8f0;">Finalización</th>
                    <th style="padding:8px;border:1px solid #e2e8f0;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                function formatear_fecha($fecha) {
                    if (!$fecha || $fecha === '-') return '-';
                    $f = date_create($fecha);
                    if (!$f) return htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8');
                    return date_format($f, 'd/m/Y H:i');
                }
                foreach ($tareas as $t): ?>
                    <tr>
                        <td style="padding:8px;border:1px solid #e2e8f0;">
                            <?= htmlspecialchars($t['texto'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td style="padding:8px;border:1px solid #e2e8f0;">
                            <?= formatear_fecha($t['fecha_alta'] ?? '-') ?>
                        </td>
                        <td style="padding:8px;border:1px solid #e2e8f0;">
                            <?= formatear_fecha($t['fecha_limite'] ?? '-') ?>
                        </td>
                        <td style="padding:8px;border:1px solid #e2e8f0;">
                            <?= !empty($t['fecha_finalizacion']) ? formatear_fecha($t['fecha_finalizacion']) : '-' ?>
                        </td>
                        <td style="padding:8px;border:1px solid #e2e8f0;">
                            <?= $t['completada'] ? 'Completada' : 'Pendiente' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="index.php" class="sidebar-link">&larr; Volver al menú principal</a>
        <script src="assets/js/graficas.js"></script>
        <script>
                // Datos para la gráfica
                const completadas = <?= $tareas_completadas ?>;
                const pendientes = <?= $pendientes ?>;
                window.datosProgreso = { completadas, pendientes };
        </script>
</body>
</html>
