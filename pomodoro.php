<?php
include_once 'includes/db.php';
include_once 'includes/session.php';
include_once 'includes/functions.php';

$csrf = generarTokenCSRF();
$tareasPom = ($_SESSION['rol'] === 'guest') ? obtenerTareasInvitado() : obtenerTareasUsuario($_SESSION['usuario_id']);
$tareasPendientes = array_filter($tareasPom, fn($t) => empty($t['completada']));
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
<div class="pom-wrap">

    <div class="pom-card">

        <!-- Cabecera -->
        <div class="pom-header">
            <span class="pom-subtitulo">modo concentración</span>
            <h1 class="pom-titulo">Pomodoro</h1>
        </div>

        <!-- Selector de tarea -->
        <div class="pom-selector">
            <select id="pomodoro-tarea-select" name="tarea_id">
                <option value="">— elige una tarea —</option>
                <?php foreach ($tareasPendientes as $t): ?>
                    <option value="<?= (int)$t['id'] ?>">
                        <?= htmlspecialchars($t['texto'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Mensaje error -->
        <div id="pomodoro-msg-error"></div>

        <!-- Anillo + timer -->
        <div class="pom-ring-wrap">
            <svg class="pom-ring-svg" viewBox="0 0 220 220" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="rg" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#9333ea"/>
                        <stop offset="100%" stop-color="#c084fc"/>
                    </linearGradient>
                </defs>
                <circle class="pom-ring-bg" cx="110" cy="110" r="96"/>
                <circle class="pom-ring-fg" id="pom-ring" cx="110" cy="110" r="96"/>
            </svg>
            <div class="pom-ring-inner">
                <div class="pom-display" id="pomodoro-display">25:00</div>
                <div class="pom-modo" id="pomodoro-modo">Trabajo</div>
            </div>
        </div>

        <!-- Botones -->
        <div class="pom-btns">
            <button class="pom-btn-main" id="btn-start">Iniciar</button>
            <button class="pom-btn-sec" id="btn-pause">Pausar</button>
            <button class="pom-btn-sec" id="btn-reset">Reiniciar</button>
            <button class="pom-btn-sec pom-btn-stop" id="btn-stop">Parar</button>
        </div>

        <!-- Sesiones como puntos -->
        <div class="pom-sesiones-wrap">
            <div class="pom-dots" id="pom-dots">
                <div class="pom-dot"></div>
                <div class="pom-dot"></div>
                <div class="pom-dot"></div>
                <div class="pom-dot"></div>
            </div>
            <span class="pom-sesiones-label">
                <span id="pomodoro-sesiones">0</span> de 4 sesiones
            </span>
        </div>

        <!-- Tiempo en tarea -->
        <div id="pomodoro-tarea-tiempo" class="pom-tarea-tiempo"></div>

        <!-- Links -->
        <div class="pom-links">
            <a href="index.php">← Volver</a>
            <a href="progreso.php">Ver progreso</a>
        </div>

    </div>
</div>
<input type="hidden" id="csrf_token" value="<?= $csrf ?>">
<script src="assets/js/pomodoro.js"></script>
</body>
</html>