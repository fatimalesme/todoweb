<?php
// Arranca la sesion solo si no estaba ya arrancada
// Evita el error "session already started" cuando otros archivos ya llamaron session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Comprobacion de acceso: el usuario debe tener una sesion activa como usuario registrado
// o haber entrado como invitado. Si no cumple ninguna condicion, se redirige al login.
$tiene_sesion_usuario   = isset($_SESSION['usuario_id']);
$tiene_sesion_invitado  = isset($_SESSION['rol']) && $_SESSION['rol'] === 'guest';

if (!$tiene_sesion_usuario && !$tiene_sesion_invitado) {
    // Permitir acceso como invitado si viene de login con POST['invitado']
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invitado'])) {
        $_SESSION['usuario_id'] = null;
        $_SESSION['username']   = 'Invitado';
        $_SESSION['rol']        = 'guest';
        $_SESSION['tareas_invitado'] = [];
        header('Location: /index.php');
        exit();
    }
    header('Location: /login.php');
    exit();
}
