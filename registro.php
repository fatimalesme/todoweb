<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario_id']) || (isset($_SESSION['rol']) && $_SESSION['rol'] === 'guest')) {
    header('Location: index.php');
    exit();
}

include_once 'includes/functions.php';
$csrf = generarTokenCSRF();

$errores = [
    'campos_vacios'   => 'Por favor rellena todos los campos.',
    'usuario_longitud'=> 'El nombre de usuario debe tener entre 3 y 30 caracteres.',
    'pass_corta'      => 'La contrasena debe tener al menos 6 caracteres.',
    'usuario_existe'  => 'Ese nombre de usuario ya esta en uso. Elige otro.',
    'error_servidor'  => 'Error interno. Por favor intentalo de nuevo.'
];

$error = $errores[$_GET['error'] ?? ''] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDoWeb - Crear Cuenta</title>
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
        .requisitos {
            font-size: 0.8rem;
            color: #718096;
            text-align: left;
            margin-bottom: 16px;
            line-height: 1.6;
        }
    </style>
</head>
<body class="login-body">
    <div class="login-card">
        <h1>Crear Cuenta</h1>

        <?php if ($error): ?>
          <div class="mensaje-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="controllers/authController.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input
                type="text"
                name="new_user"
                placeholder="Nombre de usuario"
                required
                minlength="3"
                maxlength="30"
                autocomplete="username"
            >
            <input
                type="password"
                name="new_pass"
                placeholder="Contrasena (min. 6 caracteres)"
                required
                minlength="6"
                maxlength="100"
                autocomplete="new-password"
            >
            <p class="requisitos">
                El usuario debe tener entre 3 y 30 caracteres.<br>
                La contrasena debe tener al menos 6 caracteres.
            </p>
            <button type="submit" name="register" class="btn-main">Registrarse</button>
            <a href="login.php" class="logout-link" style="color:#667eea; background:none; border-color:#667eea;">
                Ya tengo cuenta
            </a>
        </form>
    </div>
</body>
</html>
