<?php
include_once 'db.php';

// ============================================================
// FUNCIONES DE CONSULTA DE DATOS
// ============================================================

// Devuelve todas las listas del usuario ordenadas por id
function obtenerListasUsuario($usuario_id) {
    global $conexion;
    $listas = [];
    if (!$usuario_id) return $listas;

    $stmt = $conexion->prepare('SELECT * FROM listas WHERE id_usuario = ? ORDER BY id ASC');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $listas[] = $row;
    }
    $stmt->close();
    return $listas;
}

// Devuelve todas las tareas del usuario ordenadas por fecha límite ascendente.
// Las tareas sin fecha límite van al final (ORDER BY ISNULL pone los NULL al final).
function obtenerTareasUsuario($usuario_id) {
    global $conexion;
    $tareas = [];
    if (!$usuario_id) return $tareas;

    $stmt = $conexion->prepare(
        'SELECT * FROM tareas
         WHERE id_usuario = ?
         ORDER BY (fecha_limite IS NULL) ASC, fecha_limite ASC'
    );
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tareas[] = $row;
    }
    $stmt->close();
    return $tareas;
}

// Devuelve las tareas de un invitado guardadas en sesión
function obtenerTareasInvitado() {
    if (!isset($_SESSION['tareas_invitado'])) {
        $_SESSION['tareas_invitado'] = [];
    }
    $tareas = [];
    foreach ($_SESSION['tareas_invitado'] as $i => $t) {
        $t['id'] = $i;
        $tareas[] = $t;
    }
    return $tareas;
}

// Devuelve las tareas pendientes próximas a vencer (en las próximas 48 h)
// ordenadas por fecha_limite ASC, para mostrarlas en el sidebar bajo "Mi Día".
function obtenerTareasProximas($usuario_id) {
    global $conexion;
    $tareas = [];
    if (!$usuario_id) return $tareas;

    $stmt = $conexion->prepare(
        'SELECT id, texto, fecha_limite FROM tareas
         WHERE id_usuario = ?
           AND completada  = 0
           AND fecha_limite IS NOT NULL
           AND fecha_limite >= NOW()
           AND fecha_limite <= DATE_ADD(NOW(), INTERVAL 48 HOUR)
         ORDER BY fecha_limite ASC
         LIMIT 10'
    );
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tareas[] = $row;
    }
    $stmt->close();
    return $tareas;
}

// Cuenta cuántas tareas completadas tiene un usuario (para progreso)
function contarTareasCompletadas($usuario_id) {
    global $conexion;
    if (!$usuario_id) return 0;

    $stmt = $conexion->prepare(
        'SELECT COUNT(*) AS total FROM tareas WHERE id_usuario = ? AND completada = 1'
    );
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) $row['total'];
}

// Cuenta cuántas tareas pendientes tiene un usuario (para progreso)
function contarTareasPendientes($usuario_id) {
    global $conexion;
    if (!$usuario_id) return 0;

    $stmt = $conexion->prepare(
        'SELECT COUNT(*) AS total FROM tareas WHERE id_usuario = ? AND completada = 0'
    );
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) $row['total'];
}

// ============================================================
// SEGURIDAD: CSRF
// ============================================================

// Genera un token CSRF y lo guarda en sesión para proteger formularios
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Valida que el token CSRF enviado coincida con el de la sesión
function validarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Petición no válida. Recarga la página e inténtalo de nuevo.');
    }
}

// ============================================================
// UTILIDADES DE FORMATO
// ============================================================

/**
 * Formatea una fecha (DATE o DATETIME) para mostrarla al usuario.
 *
 * - Si recibe un DATETIME con hora real (no 00:00:00) muestra día y hora.
 * - Si la hora es medianoche exacta (o es un DATE) muestra solo el día.
 *
 * Centralizada aquí para no repetirla en index.php y progreso.php.
 */
function formatear_fecha($fecha) {
    if (!$fecha || $fecha === '-') return '-';
    $f = date_create($fecha);
    if (!$f) return htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8');

    // Si la hora no es 00:00 mostramos también la hora
    if (date_format($f, 'H:i') !== '00:00') {
        return date_format($f, 'd/m/Y H:i');
    }
    return date_format($f, 'd/m/Y');
}

/**
 * Devuelve cuánto tiempo queda hasta una fecha límite DATETIME.
 * Útil para los avisos del sidebar.
 * Ejemplos: "Vence en 2 h", "Vence hoy", "Vence mañana"
 */
function tiempo_restante($fecha_limite) {
    if (!$fecha_limite) return '';
    $ahora   = new DateTime();
    $limite  = new DateTime($fecha_limite);
    $diff    = $ahora->diff($limite);

    if ($diff->invert) return 'Vencida'; // ya pasó

    $horas_total = $diff->days * 24 + $diff->h;

    if ($horas_total < 1)  return 'Vence en menos de 1 h';
    if ($horas_total < 24) return "Vence en {$horas_total} h";
    if ($diff->days === 1) return 'Vence mañana';
    return "Vence en {$diff->days} días";
}
