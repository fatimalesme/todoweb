<?php
// Este controlador gestiona el Pomodoro
// Actualmente el timer funciona completamente en el cliente (JavaScript)
// Aquí se podría guardar en BD el historial de sesiones Pomodoro del usuario
// Feature pendiente: guardar sesiones completadas para mostrarlas en progreso.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Location: ../pomodoro.php');
exit();