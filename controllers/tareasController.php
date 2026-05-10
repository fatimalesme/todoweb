<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que hay sesion activa antes de hacer cualquier cosa
$tiene_sesion_usuario  = isset($_SESSION['usuario_id']);
$tiene_sesion_invitado = isset($_SESSION['rol']) && $_SESSION['rol'] === 'guest';

if (!$tiene_sesion_usuario && !$tiene_sesion_invitado) {
    header('Location: ../login.php');
    exit();
}

include_once '../includes/db.php';
include_once '../includes/functions.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;
$rol        = $_SESSION['rol'] ?? 'user';

// Todas las acciones POST deben validar el CSRF token
// Las acciones AJAX (completar, eliminar, editar, postergar) tambien lo incluyen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarTokenCSRF($_POST['csrf_token'] ?? '');
}

// -----------------------------------------------------------------------
// ANADIR NUEVA LISTA
// -----------------------------------------------------------------------
if (isset($_POST['add_list'])) {
    $nombre = trim($_POST['nombre_lista'] ?? '');

    if ($nombre !== '' && $usuario_id) {
        $stmt = $conexion->prepare('INSERT INTO listas (id_usuario, nombre) VALUES (?, ?)');
        $stmt->bind_param('is', $usuario_id, $nombre);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: ../index.php');
    exit();
}

// -----------------------------------------------------------------------
// ANADIR NUEVA TAREA
// -----------------------------------------------------------------------
if (isset($_POST['add'])) {
    $texto       = trim($_POST['texto'] ?? '');
    $id_lista    = (int) ($_POST['id_lista'] ?? 0);
    $fecha_limite = $_POST['fecha_limite'] ?? null;

    // Validar que la fecha tenga formato correcto
    if ($fecha_limite && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_limite)) {
        $fecha_limite = null;
    }

    if ($texto === '') {
        header('Location: ../index.php');
        exit();
    }

    if ($rol === 'guest') {
        if (!isset($_SESSION['tareas_invitado'])) {
            $_SESSION['tareas_invitado'] = [];
        }
        $_SESSION['tareas_invitado'][] = [
            'texto'            => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'),
            'id_lista'         => $id_lista,
            'completada'       => false,
            'fecha_limite'     => $fecha_limite,
            'fecha_alta'       => date('Y-m-d'),
            'fecha_finalizacion' => null,
            'postergaciones'   => 0,
            'max_postergaciones' => 3
        ];
    } else {
        $stmt = $conexion->prepare(
            'INSERT INTO tareas (id_usuario, id_lista, texto, fecha_alta, fecha_limite, postergaciones, max_postergaciones)
             VALUES (?, ?, ?, CURDATE(), ?, 0, 3)'
        );
        $stmt->bind_param('iiss', $usuario_id, $id_lista, $texto, $fecha_limite);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: ../index.php');
    exit();
}

// -----------------------------------------------------------------------
// COMPLETAR TAREA (llamada AJAX)
// -----------------------------------------------------------------------
if (isset($_POST['completar_id'])) {
    $id = (int) $_POST['completar_id'];

    if ($rol === 'guest') {
        if (isset($_SESSION['tareas_invitado'][$id])) {
            $estado_actual = $_SESSION['tareas_invitado'][$id]['completada'];
            $_SESSION['tareas_invitado'][$id]['completada']        = !$estado_actual;
            $_SESSION['tareas_invitado'][$id]['fecha_finalizacion'] = !$estado_actual ? date('Y-m-d') : null;
        }
    } else {
        // IF(completada, NULL, NOW()) pone la fecha si se completa y la quita si se desmarca
        $stmt = $conexion->prepare(
            'UPDATE tareas
             SET completada = NOT completada,
                 fecha_finalizacion = IF(completada = 0, NOW(), NULL)
             WHERE id = ? AND id_usuario = ?'
        );
        $stmt->bind_param('ii', $id, $usuario_id);
        $stmt->execute();
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

// -----------------------------------------------------------------------
// ELIMINAR TAREA (llamada AJAX)
// -----------------------------------------------------------------------
if (isset($_POST['eliminar_id'])) {
    $id = (int) $_POST['eliminar_id'];

    if ($rol === 'guest') {
        if (isset($_SESSION['tareas_invitado'][$id])) {
            unset($_SESSION['tareas_invitado'][$id]);
            // Reindexar el array para que los indices sean consecutivos
            $_SESSION['tareas_invitado'] = array_values($_SESSION['tareas_invitado']);
        }
    } else {
        // La condicion id_usuario evita que un usuario borre tareas de otro
        $stmt = $conexion->prepare('DELETE FROM tareas WHERE id = ? AND id_usuario = ?');
        $stmt->bind_param('ii', $id, $usuario_id);
        $stmt->execute();
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

// -----------------------------------------------------------------------
// EDITAR TAREA (llamada AJAX)
// -----------------------------------------------------------------------
if (isset($_POST['editar_id']) && isset($_POST['nuevo_texto'])) {
    $id    = (int) $_POST['editar_id'];
    $nuevo = trim($_POST['nuevo_texto']);

    if ($nuevo === '') {
        echo json_encode(['success' => false, 'error' => 'El texto no puede estar vacio']);
        exit();
    }

    if ($rol === 'guest') {
        if (isset($_SESSION['tareas_invitado'][$id])) {
            $_SESSION['tareas_invitado'][$id]['texto'] = htmlspecialchars($nuevo, ENT_QUOTES, 'UTF-8');
        }
    } else {
        $stmt = $conexion->prepare('UPDATE tareas SET texto = ? WHERE id = ? AND id_usuario = ?');
        $stmt->bind_param('sii', $nuevo, $id, $usuario_id);
        $stmt->execute();
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

// -----------------------------------------------------------------------
// POSTERGAR TAREA (llamada AJAX)
// -----------------------------------------------------------------------
if (isset($_POST['postergar_id'])) {
    $id   = (int) $_POST['postergar_id'];
    $dias = 1;

    if ($rol === 'guest') {
        if (isset($_SESSION['tareas_invitado'][$id])) {
            $tarea = &$_SESSION['tareas_invitado'][$id];
            if ($tarea['postergaciones'] < $tarea['max_postergaciones']) {
                $tarea['fecha_limite']  = date('Y-m-d', strtotime($tarea['fecha_limite'] . " +$dias day"));
                $tarea['postergaciones']++;
            }
        }
    } else {
        // Solo actualiza si no se ha llegado al limite de postergaciones
        $stmt = $conexion->prepare(
            'UPDATE tareas
             SET fecha_limite   = DATE_ADD(fecha_limite, INTERVAL ? DAY),
                 postergaciones = postergaciones + 1
             WHERE id = ? AND id_usuario = ? AND postergaciones < max_postergaciones'
        );
        $stmt->bind_param('iii', $dias, $id, $usuario_id);
        $stmt->execute();
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

// Si llega aqui sin hacer match de ninguna accion, redirigir al inicio
header('Location: ../index.php');
exit();
