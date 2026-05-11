<?php
// Este controlador gestiona la página de progreso
// Los datos se obtienen directamente en progreso.php usando functions.php
// Feature pendiente: exportar historial de tareas a CSV

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Location: ../progreso.php');
exit();