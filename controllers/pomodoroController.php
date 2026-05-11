
<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

include_once '../includes/db.php';
include_once '../includes/functions.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;
$rol        = $_SESSION['rol'] ?? 'user';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$tarea_id = isset($_POST['tarea_id']) ? (int)$_POST['tarea_id'] : 0;
	$segundos = isset($_POST['segundos']) ? (int)$_POST['segundos'] : 0;
	if ($tarea_id > 0 && $segundos > 0) {
		if ($rol === 'guest') {
			if (isset($_SESSION['tareas_invitado'][$tarea_id])) {
				$_SESSION['tareas_invitado'][$tarea_id]['pomodoro_segundos'] = 
					(isset($_SESSION['tareas_invitado'][$tarea_id]['pomodoro_segundos']) ? $_SESSION['tareas_invitado'][$tarea_id]['pomodoro_segundos'] : 0) + $segundos;
				echo json_encode(['success' => true]);
				exit();
			}
		} else {
			$stmt = $conexion->prepare('UPDATE tareas SET pomodoro_segundos = pomodoro_segundos + ? WHERE id = ? AND id_usuario = ?');
			$stmt->bind_param('iii', $segundos, $tarea_id, $usuario_id);
			$stmt->execute();
			$stmt->close();
			echo json_encode(['success' => true]);
			exit();
		}
	}
	echo json_encode(['success' => false]);
	exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['tarea_id'])) {
	$tarea_id = (int)$_GET['tarea_id'];
	if ($rol === 'guest') {
		$seg = isset($_SESSION['tareas_invitado'][$tarea_id]['pomodoro_segundos']) ? $_SESSION['tareas_invitado'][$tarea_id]['pomodoro_segundos'] : 0;
		echo json_encode(['segundos' => $seg]);
		exit();
	} else {
		$stmt = $conexion->prepare('SELECT pomodoro_segundos FROM tareas WHERE id = ? AND id_usuario = ?');
		$stmt->bind_param('ii', $tarea_id, $usuario_id);
		$stmt->execute();
		$stmt->bind_result($seg);
		$stmt->fetch();
		$stmt->close();
		echo json_encode(['segundos' => (int)$seg]);
		exit();
	}
}

echo json_encode(['success' => false]);
exit();
