<?php
include_once 'db.php';

// ============================================================
// FUNCIONES DE CONSULTA DE DATOS
// ============================================================

// Devuelve todas las listas del usuario ordenadas por id
function obtenerListasUsuario($usuario_id) {
    global $conexion;
    $listas = [];
    if (!$usuario_id) return $listas;

    $stmt = $conexion->prepare('SELECT * FROM listas WHERE id_usuario = ? ORDER BY id ASC');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $listas[] = $row;
    }
    $stmt->close();
    return $listas;
}

// Devuelve todas las tareas del usuario ordenadas por fecha límite ascendente.
// Las tareas sin fecha límite van al final (ORDER BY ISNULL pone los NULL al final).
function obtenerTareasUsuario($usuario_id) {
    global $conexion;
    $tareas = [];
    if (!$usuario_id) return $tareas;

    $stmt = $conexion->prepare(
        'SELECT * FROM tareas
         WHERE id_usuario = ?
         ORDER BY fecha_alta ASC'
    );
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tareas[] = $row;
    }
    $stmt->close();
    return $tareas;
}

// Devuelve las tareas de un invitado guardadas en sesión
function obtenerTareasInvitado() {
    if (!isset($_SESSION['tareas_invitado'])) {
        $_SESSION['tareas_invitado'] = [];
    }
    $tareas = [];
    foreach ($_SESSION['tareas_invitado'] as $i => $t) {
        $t['id'] = $i;
        $tareas[] = $t;
    }
    return $tareas;
}

// Devuelve las tareas pendientes próximas a vencer (en las próximas 48 h)
// ordenadas por fecha_limite ASC, para mostrarlas en el sidebar bajo "Mi Día".
function obtenerTareasProximas($usuario_id) {
    global $conexion;
    $tareas = [];
    if (!$usuario_id) return $tareas;

    $stmt = $conexion->prepare(
        'SELECT id, texto, fecha_limite FROM tareas
         WHERE id_usuario = ?
           AND completada  = 0
           AND fecha_limite IS NOT NULL
           AND fecha_limite >= NOW()
           AND fecha_limite <= DATE_ADD(NOW(), INTERVAL 48 HOUR)
         ORDER BY fecha_limite ASC
         LIMIT 10'
    );
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tareas[] = $row;
    }
    $stmt->close();
    return $tareas;
}

// Cuenta cuántas tareas completadas tiene un usuario (para progreso)
function contarTareasCompletadas($usuario_id) {
    global $conexion;
    if (!$usuario_id) return 0;

    $stmt = $conexion->prepare(
        'SELECT COUNT(*) AS total FROM tareas WHERE id_usuario = ? AND completada = 1'
    );
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) $row['total'];
}

// Cuenta cuántas tareas pendientes tiene un usuario (para progreso)
function contarTareasPendientes($usuario_id) {
    global $conexion;
    if (!$usuario_id) return 0;

    $stmt = $conexion->prepare(
        'SELECT COUNT(*) AS total FROM tareas WHERE id_usuario = ? AND completada = 0'
    );
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) $row['total'];
}

// ============================================================
// SEGURIDAD: CSRF
// ============================================================

// Genera un token CSRF y lo guarda en sesión para proteger formularios
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Valida que el token CSRF enviado coincida con el de la sesión
function validarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Petición no válida. Recarga la página e inténtalo de nuevo.');
    }
}

// ============================================================
// UTILIDADES DE FORMATO
// ============================================================

/**
 * Formatea una fecha (DATE o DATETIME) para mostrarla al usuario.
 *
 * - Si recibe un DATETIME con hora real (no 00:00:00) muestra día y hora.
 * - Si la hora es medianoche exacta (o es un DATE) muestra solo el día.
 *
 * Centralizada aquí para no repetirla en index.php y progreso.php.
 */
function formatear_fecha($fecha) {
    if (!$fecha || $fecha === '-') return '-';
    $f = date_create($fecha);
    if (!$f) return htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8');

    // Si la hora no es 00:00 mostramos también la hora
    if (date_format($f, 'H:i') !== '00:00') {
        return date_format($f, 'd/m/Y H:i');
    }
    return date_format($f, 'd/m/Y');
}

/**
 * Devuelve cuánto tiempo queda hasta una fecha límite DATETIME.
 * Útil para los avisos del sidebar.
 * Ejemplos: "Vence en 2 h", "Vence hoy", "Vence mañana"
 */
function tiempo_restante($fecha_limite) {
    if (!$fecha_limite) return '';
    $ahora   = new DateTime();
    $limite  = new DateTime($fecha_limite);
    $diff    = $ahora->diff($limite);

    if ($diff->invert) return 'Vencida'; // ya pasó

    $horas_total = $diff->days * 24 + $diff->h;

    if ($horas_total < 1)  return 'Vence en menos de 1 h';
    if ($horas_total < 24) return "Vence en {$horas_total} h";
    if ($diff->days === 1) return 'Vence mañana';
    return "Vence en {$diff->days} días";
}
// ============================================================
// GAMIFICACIÓN: niveles, XP y logros
// ============================================================

/**
 * Devuelve la información del nivel actual según los XP del usuario.
 * Los niveles siguen el ciclo de vida de una estrella, de menos a más.
 * Cuantos más XP tienes, más "brillas".
 */
function obtenerNivel($xp) {
    // Definimos los niveles de menor a mayor XP necesario
    $niveles = [
        ['nombre' => 'Nebulosa',  'icono' => '🌫️', 'min' => 0,   'sub' => 'Apenas empiezas a tomar forma'],
        ['nombre' => 'Destello',  'icono' => '✨',  'min' => 100, 'sub' => 'Tu energía empieza a notarse'],
        ['nombre' => 'Fulgor',    'icono' => '💫',  'min' => 250, 'sub' => 'Brillas con luz propia'],
        ['nombre' => 'Supernova', 'icono' => '🌟',  'min' => 500, 'sub' => 'Tu productividad es imparable'],
    ];

    // Recorremos al revés para quedarnos con el nivel más alto alcanzado
    $nivel_actual = $niveles[0];
    foreach ($niveles as $n) {
        if ($xp >= $n['min']) {
            $nivel_actual = $n;
        }
    }

    // Buscamos el siguiente nivel para calcular el porcentaje de progreso
    $siguiente = null;
    foreach ($niveles as $n) {
        if ($n['min'] > $xp) {
            $siguiente = $n;
            break;
        }
    }

    // Calculamos qué % de la barra está rellena entre el nivel actual y el siguiente
    $porcentaje = 100;
    if ($siguiente !== null) {
        $rango      = $siguiente['min'] - $nivel_actual['min'];
        $avance     = $xp - $nivel_actual['min'];
        $porcentaje = (int) (($avance / $rango) * 100);
    }

    return [
        'actual'      => $nivel_actual,
        'siguiente'   => $siguiente,
        'porcentaje'  => $porcentaje,
    ];
}

/**
 * Suma XP al usuario y actualiza la racha diaria.
 * La racha sube si el usuario completa al menos una tarea cada día.
 * Si pasa un día sin completar nada, la racha vuelve a 1.
 */
function sumarXP($usuario_id, $cantidad) {
    global $conexion;
    if (!$usuario_id) return;

    $hoy = date('Y-m-d');

    // Leemos el último día que el usuario completó una tarea
    $stmt = $conexion->prepare('SELECT ultimo_dia, racha FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $stmt->bind_result($ultimo_dia, $racha_actual);
    $stmt->fetch();
    $stmt->close();

    // Calculamos la nueva racha
    if ($ultimo_dia === null) {
        // Primera vez que completa algo
        $nueva_racha = 1;
    } elseif ($ultimo_dia === $hoy) {
        // Ya completó algo hoy, la racha no cambia
        $nueva_racha = $racha_actual;
    } elseif ($ultimo_dia === date('Y-m-d', strtotime('-1 day'))) {
        // Completó algo ayer, sigue la racha
        $nueva_racha = $racha_actual + 1;
    } else {
        // Ha pasado más de un día, la racha se rompe
        $nueva_racha = 1;
    }

    // Guardamos los nuevos valores en la base de datos
    $stmt = $conexion->prepare(
        'UPDATE usuarios SET xp = xp + ?, racha = ?, ultimo_dia = ? WHERE id = ?'
    );
    $stmt->bind_param('iisi', $cantidad, $nueva_racha, $hoy, $usuario_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Comprueba qué logros ha desbloqueado el usuario y los guarda en BD.
 * Se llama cada vez que el usuario completa una tarea.
 */
function comprobarLogros($usuario_id) {
    global $conexion;
    if (!$usuario_id) return;

    // Leemos datos actuales del usuario
    $stmt = $conexion->prepare('SELECT xp, racha, logros FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $stmt->bind_result($xp, $racha, $logros_json);
    $stmt->fetch();
    $stmt->close();

    // Convertimos el JSON de logros a array de PHP (o array vacío si es null)
    $logros = $logros_json ? json_decode($logros_json, true) : [];

    // Contamos las tareas completadas del usuario
    $stmt2 = $conexion->prepare(
        'SELECT COUNT(*) FROM tareas WHERE id_usuario = ? AND completada = 1'
    );
    $stmt2->bind_param('i', $usuario_id);
    $stmt2->execute();
    $stmt2->bind_result($total_completadas);
    $stmt2->fetch();
    $stmt2->close();

    // Lista de todos los logros posibles con sus condiciones
    $posibles = [
        'primera_chispa'    => $total_completadas >= 1,
        'orbita_estable'    => $racha >= 3,
        'campo_asteroides'  => $total_completadas >= 10,
        'tormenta_solar'    => false, // este se gestiona en el controller del día
        'gravedad_propia'   => $racha >= 7,
        'explosion_estelar' => $xp >= 500,
    ];

    // Añadimos al array solo los que se acaban de desbloquear
    $nuevo = false;
    foreach ($posibles as $clave => $cumple) {
        if ($cumple && !in_array($clave, $logros)) {
            $logros[] = $clave;
            $nuevo    = true;
        }
    }

    // Solo actualizamos la BD si hay algo nuevo que guardar
    if ($nuevo) {
        $json = json_encode($logros);
        $stmt3 = $conexion->prepare('UPDATE usuarios SET logros = ? WHERE id = ?');
        $stmt3->bind_param('si', $json, $usuario_id);
        $stmt3->execute();
        $stmt3->close();
    }

    return $logros;
}

/**
 * Devuelve todos los datos de gamificación del usuario de una vez.
 * Así en index.php solo llamamos a esta función y tenemos todo.
 */
function obtenerDatosGamificacion($usuario_id) {
    global $conexion;
    if (!$usuario_id) {
        return ['xp' => 0, 'racha' => 0, 'logros' => [], 'nivel' => obtenerNivel(0)];
    }

    $stmt = $conexion->prepare('SELECT xp, racha, logros FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $stmt->bind_result($xp, $racha, $logros_json);
    $stmt->fetch();
    $stmt->close();

    $logros = $logros_json ? json_decode($logros_json, true) : [];

    return [
        'xp'    => (int) $xp,
        'racha' => (int) $racha,
        'logros'=> $logros,
        'nivel' => obtenerNivel((int) $xp),
    ];
}