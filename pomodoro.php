<?php
include_once 'includes/db.php';
include_once 'includes/session.php';
include_once 'includes/functions.php';

// Puedes agregar lógica específica del Pomodoro aquí
$csrf = generarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pomodoro | ToDoWeb</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h1>Pomodoro</h1>
    <div id="pomodoro-display">25:00</div>
    <div>
        <button id="btn-start">Iniciar</button>
        <button id="btn-pause">Pausar</button>
        <button id="btn-reset">Reiniciar</button>
    </div>
    <div>
        <span id="pomodoro-modo">Trabajo</span> |
        Sesiones completadas: <span id="pomodoro-sesiones">0</span>
    </div>
    <br>
    <a href="index.php" class="sidebar-link">&larr; Volver al menú principal</a>
    <script src="assets/js/pomodoro.js"></script>
</body>
</html>
