<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya hay sesion no tiene sentido mostrar el login
if (isset($_SESSION['usuario_id']) || (isset($_SESSION['rol']) && $_SESSION['rol'] === 'guest')) {
    header('Location: index.php');
    exit();
}

include_once 'includes/functions.php';
$csrf = generarTokenCSRF();

// Leer mensajes de error o exito que vienen por la URL
$errores = [
    'credenciales'       => 'Usuario o contrasena incorrectos.',
    'campos_vacios'      => 'Por favor rellena todos los campos.',
    'demasiados_intentos'=> 'Demasiados intentos fallidos. Recarga la pagina.'
];
$exitos = [
    'cuenta_creada' => 'Cuenta creada correctamente. Ya puedes iniciar sesion.'
];

$error  = $errores[$_GET['error']  ?? ''] ?? null;
$exito  = $exitos[$_GET['exito']   ?? ''] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDoWeb - Iniciar Sesion</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .mensaje-error {
            background: rgba(252,129,129,0.2);
            color: #c53030;
            border: 1px solid rgba(252,129,129,0.5);
            padding: 10px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .mensaje-exito {
            background: rgba(72,187,120,0.2);
            color: #276749;
            border: 1px solid rgba(72,187,120,0.5);
            padding: 10px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body class="login-body">
    <div class="login-container">
        <h2>Bienvenido a ToDoWeb</h2>

        <?php if ($error): ?>
          <div class="mensaje-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($exito): ?>
          <div class="mensaje-exito"><?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="controllers/authController.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="text"     name="user" placeholder="Usuario"     maxlength="30" autocomplete="username" id="user-field">
            <input type="password" name="pass" placeholder="Contrasena"  maxlength="100" autocomplete="current-password" id="pass-field">
            <button type="submit" name="login">Entrar</button>
            <hr style="margin: 15px 0; border: 0.5px solid #e2e8f0;">
            <button type="submit" name="invitado" style="background:rgba(255,255,255,0.8);color:#4a5568;border:1px solid #e2e8f0;" onclick="document.getElementById('user-field').removeAttribute('required');document.getElementById('pass-field').removeAttribute('required');">
                Entrar como invitado
            </button>
            <p style="margin-top: 15px; font-size: 0.9em;">
                No tienes cuenta? <a href="registro.php" style="color: #fc8181; font-weight:600;">Registrate aqui</a>
            </p>
        </form>
    </div>
</body>
</html>
