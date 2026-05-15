<?php
include_once 'db.php';

/**
 * Devuelve avisos de tareas próximas a vencer y tareas ya vencidas.
 * Solo para usuarios registrados (los invitados usan sesión, no BD).
 *
 */
function obtenerAvisosUsuario($usuario_id) {
    global $conexion;
    $avisos = [];
    if (!$usuario_id) return $avisos;

    // Tareas que vencen en las próximas 48 h y aún no están completadas
    $stmt = $conexion->prepare(
        'SELECT texto, fecha_limite FROM tareas
         WHERE id_usuario   = ?
           AND completada   = 0
           AND fecha_limite IS NOT NULL
           AND fecha_limite >= NOW()
           AND fecha_limite <= DATE_ADD(NOW(), INTERVAL 48 HOUR)
         ORDER BY fecha_limite ASC'
    );
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $fecha_formateada = formatear_fecha_aviso($row['fecha_limite']);
        $avisos[] = [
            'tipo'    => 'proxima',
            'mensaje' => '⏰ La tarea "' . htmlspecialchars($row['texto'], ENT_QUOTES, 'UTF-8')
                         . '" vence el ' . $fecha_formateada
        ];
    }
    $stmt->close();

    // Tareas cuyo plazo ya pasó y no están completadas
    $stmt2 = $conexion->prepare(
        'SELECT texto, fecha_limite FROM tareas
         WHERE id_usuario   = ?
           AND completada   = 0
           AND fecha_limite IS NOT NULL
           AND fecha_limite < NOW()
         ORDER BY fecha_limite ASC'
    );
    $stmt2->bind_param('i', $usuario_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $fecha_formateada = formatear_fecha_aviso($row['fecha_limite']);
        $avisos[] = [
            'tipo'    => 'vencida',
            'mensaje' => '🔴 La tarea "' . htmlspecialchars($row['texto'], ENT_QUOTES, 'UTF-8')
                         . '" está vencida (límite: ' . $fecha_formateada . ')'
        ];
    }
    $stmt2->close();

    return $avisos;
}

/**
 * Formatea un DATETIME para usarlo dentro de un mensaje de aviso.
 * Siempre muestra la hora si la hay, para que el aviso sea preciso.
 * Es privada de este archivo, por eso no está en functions.php.
 */
function formatear_fecha_aviso($fecha) {
    if (!$fecha) return '-';
    $f = date_create($fecha);
    if (!$f) return htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8');
    if (date_format($f, 'H:i') !== '00:00') {
        return date_format($f, 'd/m/Y \a \l\a\s H:i');
    }
    return date_format($f, 'd/m/Y');
}
