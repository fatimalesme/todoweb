<?php
// El session_start lo gestiona session.php, pero aqui lo necesitamos antes de incluirlo
// porque authController es el punto de entrada (no esta protegido, es el login mismo)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya hay sesion activa no tiene sentido estar aqui, redirigir al dashboard
if (isset($_SESSION['usuario_id']) || (isset($_SESSION['rol']) && $_SESSION['rol'] === 'guest')) {
    header('Location: ../index.php');
    exit();
}

include_once '../includes/db.php';
include_once '../includes/functions.php';

// -----------------------------------------------------------------------
// ENTRAR COMO INVITADO
// -----------------------------------------------------------------------
if (isset($_POST['invitado'])) {
    // No hace falta validar CSRF aqui porque no hay datos sensibles
    $_SESSION['usuario_id'] = null;
    $_SESSION['username']   = 'Invitado';
    $_SESSION['rol']        = 'guest';
    $_SESSION['tareas_invitado'] = [];
    header('Location: ../index.php');
    exit();
}

// -----------------------------------------------------------------------
// LOGIN
// -----------------------------------------------------------------------
if (isset($_POST['login'])) {
    // Validar token CSRF antes de procesar cualquier formulario POST
    validarTokenCSRF($_POST['csrf_token'] ?? '');

    $user = trim($_POST['user'] ?? '');
    $pass = $_POST['pass'] ?? '';

    // Validacion minima en servidor
    if (empty($user) || empty($pass)) {
        header('Location: ../login.php?error=campos_vacios');
        exit();
    }

    // Limite de intentos para evitar fuerza bruta
    if (!isset($_SESSION['intentos_login'])) {
        $_SESSION['intentos_login'] = 0;
    }
    if ($_SESSION['intentos_login'] >= 5) {
        header('Location: ../login.php?error=demasiados_intentos');
        exit();
    }

    // Buscar el usuario solo por nombre (nunca comparar password en SQL)
    $stmt = $conexion->prepare('SELECT id, username, password, rol FROM usuarios WHERE username = ?');
    $stmt->bind_param('s', $user);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $u = $resultado->fetch_assoc();
    $stmt->close();

    // password_verify compara el texto plano con el hash guardado en la BD
    if ($u && password_verify($pass, $u['password'])) {
        // Login correcto: resetear intentos y guardar sesion
        $_SESSION['intentos_login'] = 0;
        $_SESSION['usuario_id']     = $u['id'];
        $_SESSION['username']       = $u['username'];
        $_SESSION['rol']            = $u['rol'];

        // Regenerar el ID de sesion para evitar session fixation
        session_regenerate_id(true);

        header('Location: ../index.php');
        exit();
    } else {
        $_SESSION['intentos_login']++;
        header('Location: ../login.php?error=credenciales');
        exit();
    }
}

// -----------------------------------------------------------------------
// REGISTRO
// -----------------------------------------------------------------------
if (isset($_POST['register'])) {
    validarTokenCSRF($_POST['csrf_token'] ?? '');

    $user = trim($_POST['new_user'] ?? '');
    $pass = $_POST['new_pass'] ?? '';

    // Validaciones basicas
    if (empty($user) || empty($pass)) {
        header('Location: ../registro.php?error=campos_vacios');
        exit();
    }
    if (strlen($user) < 3 || strlen($user) > 30) {
        header('Location: ../registro.php?error=usuario_longitud');
        exit();
    }
    if (strlen($pass) < 6) {
        header('Location: ../registro.php?error=pass_corta');
        exit();
    }

    // Comprobar si el nombre de usuario ya existe
    $check = $conexion->prepare('SELECT id FROM usuarios WHERE username = ?');
    $check->bind_param('s', $user);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        header('Location: ../registro.php?error=usuario_existe');
        exit();
    }
    $check->close();

    // Encriptar la contrasena antes de guardarla
    // PASSWORD_DEFAULT usa bcrypt actualmente y se actualiza automaticamente
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    $insert = $conexion->prepare('INSERT INTO usuarios (username, password, rol) VALUES (?, ?, "user")');
    $insert->bind_param('ss', $user, $hash);

    if ($insert->execute()) {
        $insert->close();
        header('Location: ../login.php?exito=cuenta_creada');
        exit();
    } else {
        error_log('Error al registrar usuario: ' . $conexion->error);
        header('Location: ../registro.php?error=error_servidor');
        exit();
    }
}

// -----------------------------------------------------------------------
// LOGOUT
// -----------------------------------------------------------------------
if (isset($_GET['logout'])) {
    // Destruir completamente la sesion
    session_unset();
    session_destroy();

    // Borrar la cookie de sesion del navegador del usuario
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    header('Location: ../login.php');
    exit();
}
