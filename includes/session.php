<?php
// Arranca la sesion solo si no estaba ya arrancada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Comprobación de acceso
$tiene_sesion_usuario  = isset($_SESSION['usuario_id']);
$tiene_sesion_invitado = isset($_SESSION['rol']) && $_SESSION['rol'] === 'guest';

if (!$tiene_sesion_usuario && !$tiene_sesion_invitado) {
    header('Location: login.php');
    exit();
}