<?php
include_once 'db.php';

// Devuelve avisos de tareas proximas a vencer y tareas ya vencidas
// Solo funciona para usuarios registrados, los invitados no tienen persistencia en BD
function obtenerAvisosUsuario($usuario_id) {
    global $conexion;
    $avisos = [];
    if (!$usuario_id) return $avisos;

    $hoy = date('Y-m-d');

    // Tareas que vencen hoy o en los proximos 2 dias y aun no estan completadas
    $stmt = $conexion->prepare(
        'SELECT texto, fecha_limite FROM tareas
         WHERE id_usuario = ?
           AND completada = 0
           AND fecha_limite IS NOT NULL
           AND fecha_limite >= ?
           AND fecha_limite <= DATE_ADD(?, INTERVAL 2 DAY)'
    );
    $stmt->bind_param('iss', $usuario_id, $hoy, $hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $avisos[] = [
            'tipo'    => 'proxima',
            'mensaje' => 'La tarea "' . htmlspecialchars($row['texto'], ENT_QUOTES, 'UTF-8') . '" vence pronto (' . $row['fecha_limite'] . ')'
        ];
    }
    $stmt->close();

    // Tareas cuyo plazo ya paso y no estan completadas
    $stmt2 = $conexion->prepare(
        'SELECT texto, fecha_limite FROM tareas
         WHERE id_usuario = ?
           AND completada = 0
           AND fecha_limite IS NOT NULL
           AND fecha_limite < ?'
    );
    $stmt2->bind_param('is', $usuario_id, $hoy);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $avisos[] = [
            'tipo'    => 'vencida',
            'mensaje' => 'La tarea "' . htmlspecialchars($row['texto'], ENT_QUOTES, 'UTF-8') . '" esta vencida (limite: ' . $row['fecha_limite'] . ')'
        ];
    }
    $stmt2->close();

    return $avisos;
}
