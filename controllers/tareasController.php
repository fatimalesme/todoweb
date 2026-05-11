<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que hay sesión activa antes de hacer cualquier cosa
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

// Todas las acciones POST validan el CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarTokenCSRF($_POST['csrf_token'] ?? '');
}

// -----------------------------------------------------------------------
// AÑADIR NUEVA LISTA / CATEGORÍA
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
// AÑADIR NUEVA TAREA
// Cambio principal: ahora recibimos 'fecha_limite' como datetime-local
// (formato: "YYYY-MM-DDTHH:MM") en lugar de un DATE simple.
// -----------------------------------------------------------------------
if (isset($_POST['add'])) {
    $texto        = trim($_POST['texto'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $id_lista     = (int) ($_POST['id_lista'] ?? 0);
    $fecha_limite = $_POST['fecha_limite'] ?? null;  // viene como "2025-06-15T14:30"

    // Validar y normalizar el DATETIME recibido del input datetime-local.
    // El navegador envía "YYYY-MM-DDTHH:MM", MySQL espera "YYYY-MM-DD HH:MM:SS".
    if ($fecha_limite) {
        // Reemplazar la T por espacio y añadir segundos
        $fecha_limite = str_replace('T', ' ', $fecha_limite) . ':00';

        // Comprobar que el formato resultante es un datetime válido
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha_limite);
        if (!$dt) {
            $fecha_limite = null; // si algo falla, guardamos sin fecha
        }
    }

    if ($texto === '') {
        header('Location: ../index.php');
        exit();
    }

    if ($rol === 'guest') {
        if (!isset($_SESSION['tareas_invitado'])) {
            $_SESSION['tareas_invitado'] = [];
        }
        // Los invitados guardan la tarea en sesión (sin BD)
        $_SESSION['tareas_invitado'][] = [
            'texto'              => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'),
            'descripcion'        => htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'),
            'id_lista'           => $id_lista,
            'completada'         => false,
            'fecha_limite'       => $fecha_limite,
            'fecha_alta'         => date('Y-m-d H:i:s'),
            'fecha_finalizacion' => null,
            'postergaciones'     => 0,
            'max_postergaciones' => 3
        ];
    } else {
        // Guardamos el DATETIME completo en BD
        $stmt = $conexion->prepare(
            'INSERT INTO tareas
                (id_usuario, id_lista, texto, descripcion, fecha_alta, fecha_limite, postergaciones, max_postergaciones)
             VALUES
                (?, ?, ?, ?, NOW(), ?, 0, 3)'
        );
        $stmt->bind_param('iisss', $usuario_id, $id_lista, $texto, $descripcion, $fecha_limite);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: ../index.php');
    exit();
}

// -----------------------------------------------------------------------
// COMPLETAR TAREA (llamada AJAX)
// NOW() ya devuelve DATETIME, así que fecha_finalizacion guarda hora exacta.
// -----------------------------------------------------------------------
if (isset($_POST['completar_id'])) {
    $id = (int) $_POST['completar_id'];

    if ($rol === 'guest') {
        if (isset($_SESSION['tareas_invitado'][$id])) {
            $estado_actual = $_SESSION['tareas_invitado'][$id]['completada'];
            $_SESSION['tareas_invitado'][$id]['completada']        = !$estado_actual;
            $_SESSION['tareas_invitado'][$id]['fecha_finalizacion'] = !$estado_actual
                ? date('Y-m-d H:i:s')  // guarda fecha Y hora exacta
                : null;
        }
    } else {
        // IF(completada = 0, NOW(), NULL):
        // - Si la tarea estaba pendiente → la marca como completada y guarda NOW() con hora
        // - Si ya estaba completada → la desmarca y borra la fecha de finalización
        $stmt = $conexion->prepare(
            'UPDATE tareas
             SET completada         = NOT completada,
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
            $_SESSION['tareas_invitado'] = array_values($_SESSION['tareas_invitado']);
        }
    } else {
        // id_usuario en el WHERE evita que un usuario borre tareas de otro
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
    $nuevaDesc = isset($_POST['nueva_descripcion']) ? trim($_POST['nueva_descripcion']) : '';
    $nuevaFecha = array_key_exists('nueva_fecha', $_POST) ? trim($_POST['nueva_fecha']) : null;
    $nuevaCat = isset($_POST['nueva_categoria']) ? (int) $_POST['nueva_categoria'] : 0;

    // Normalizar fecha si viene en formato datetime-local
    if ($nuevaFecha !== null && $nuevaFecha !== '') {
        $nuevaFecha = str_replace('T', ' ', $nuevaFecha);
        if (strlen($nuevaFecha) === 16) $nuevaFecha .= ':00';
    } else {
        $nuevaFecha = null;
    }

    if ($nuevo === '') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'El texto no puede estar vacío']);
        exit();
    }

    if ($rol === 'guest') {
        if (isset($_SESSION['tareas_invitado'][$id])) {
            $_SESSION['tareas_invitado'][$id]['texto'] = htmlspecialchars($nuevo, ENT_QUOTES, 'UTF-8');
            $_SESSION['tareas_invitado'][$id]['descripcion'] = htmlspecialchars($nuevaDesc, ENT_QUOTES, 'UTF-8');
            if ($nuevaFecha !== null) {
                $_SESSION['tareas_invitado'][$id]['fecha_limite'] = $nuevaFecha;
            }
            $_SESSION['tareas_invitado'][$id]['id_lista'] = $nuevaCat;
        }
    } else {
        // Si no se envía nuevaFecha, conservar la fecha actual
        if ($nuevaFecha !== null) {
            $stmt = $conexion->prepare('UPDATE tareas SET texto = ?, descripcion = ?, fecha_limite = ?, id_lista = ? WHERE id = ? AND id_usuario = ?');
            $stmt->bind_param('sssiii', $nuevo, $nuevaDesc, $nuevaFecha, $nuevaCat, $id, $usuario_id);
        } else {
            $stmt = $conexion->prepare('UPDATE tareas SET texto = ?, descripcion = ?, id_lista = ? WHERE id = ? AND id_usuario = ?');
            $stmt->bind_param('sssii', $nuevo, $nuevaDesc, $nuevaCat, $id, $usuario_id);
        }
        $stmt->execute();
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

// -----------------------------------------------------------------------
// POSTERGAR TAREA (llamada AJAX)
// DATE_ADD funciona igual con DATETIME, así que no hay que cambiar nada aquí.
// -----------------------------------------------------------------------
if (isset($_POST['postergar_id'])) {
    $id   = (int) $_POST['postergar_id'];
    $dias = 1;

    if ($rol === 'guest') {
        if (isset($_SESSION['tareas_invitado'][$id])) {
            $tarea = &$_SESSION['tareas_invitado'][$id];
            if ($tarea['postergaciones'] < $tarea['max_postergaciones']) {
                // strtotime funciona con DATETIME completo
                $tarea['fecha_limite']   = date('Y-m-d H:i:s', strtotime($tarea['fecha_limite'] . " +$dias day"));
                $tarea['postergaciones']++;
            }
        }
    } else {
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

// Si llega aquí sin hacer match de ninguna acción, redirigir al inicio
header('Location: ../index.php');
exit();
