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
    <link rel="stylesheet" href="assets/css/pomodoro.css">
</head>
<body>
    <div class="pomodoro-box">
    <h1>Pomodoro</h1>
    <form id="pomodoro-tarea-form" class="pomodoro-tarea-form">
        <label for="pomodoro-tarea-select">Selecciona una tarea:</label>
        <select id="pomodoro-tarea-select" name="tarea_id" required>
            <option value="">-- Elige una tarea --</option>
            <?php
            $tareasPom = ($_SESSION['rol'] === 'guest') ? obtenerTareasInvitado() : obtenerTareasUsuario($_SESSION['usuario_id']);
            foreach ($tareasPom as $t) {
                if (!empty($t['completada'])) continue;
                $texto = htmlspecialchars($t['texto'], ENT_QUOTES, 'UTF-8');
                $id = (int) ($t['id'] ?? 0);
                echo "<option value=\"$id\">$texto</option>";
            }
            ?>
        </select>
    </form>
    <div id="pomodoro-display">25:00</div>
    <div class="pomodoro-buttons">
        <button id="btn-start">Iniciar</button>
        <button id="btn-pause">Pausar</button>
        <button id="btn-reset">Reiniciar</button>
        <button id="btn-stop">Parar</button>
    </div>
    <div>
        <span id="pomodoro-modo">Trabajo</span> |
        Sesiones completadas: <span id="pomodoro-sesiones">0</span>
    </div>
    <div id="pomodoro-tarea-tiempo"></div>
    <br>
    <a href="index.php" class="sidebar-link">&larr; Volver al menú principal</a>
    <a href="progreso.php" class="sidebar-link">Ver progreso</a>
    </div>
    <script src="assets/js/pomodoro.js"></script>
</body>
</html>