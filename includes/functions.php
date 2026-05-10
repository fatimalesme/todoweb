<?php
include_once 'db.php';

// Devuelve todas las listas del usuario
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

// Devuelve todas las tareas del usuario ordenadas por fecha limite
function obtenerTareasUsuario($usuario_id) {
    global $conexion;
    $tareas = [];
    if (!$usuario_id) return $tareas;

    $stmt = $conexion->prepare('SELECT * FROM tareas WHERE id_usuario = ? ORDER BY fecha_limite ASC');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tareas[] = $row;
    }
    $stmt->close();
    return $tareas;
}

// Devuelve las tareas de un invitado guardadas en sesion
function obtenerTareasInvitado() {
    if (!isset($_SESSION['tareas_invitado'])) {
        $_SESSION['tareas_invitado'] = [];
    }
    // Se añade un id de array para que los botones puedan referenciarlo
    $tareas = [];
    foreach ($_SESSION['tareas_invitado'] as $i => $t) {
        $t['id'] = $i;
        $tareas[] = $t;
    }
    return $tareas;
}

// Cuenta cuantas tareas completadas tiene un usuario (para la pagina de progreso)
function contarTareasCompletadas($usuario_id) {
    global $conexion;
    if (!$usuario_id) return 0;

    $stmt = $conexion->prepare('SELECT COUNT(*) AS total FROM tareas WHERE id_usuario = ? AND completada = 1');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) $row['total'];
}

// Cuenta cuantas tareas pendientes tiene un usuario
function contarTareasPendientes($usuario_id) {
    global $conexion;
    if (!$usuario_id) return 0;

    $stmt = $conexion->prepare('SELECT COUNT(*) AS total FROM tareas WHERE id_usuario = ? AND completada = 0');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) $row['total'];
}

// Genera un token CSRF y lo guarda en sesion para proteger formularios
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Valida que el token CSRF enviado en el formulario coincida con el de la sesion
function validarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Peticion no valida. Recarga la pagina e intentalo de nuevo.');
    }
}
