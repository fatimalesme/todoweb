<?php
// Configuracion de la conexion a la base de datos

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'todoweb_db');

$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Codificacion UTF8 para caracteres especiales en espanol
$conexion->set_charset('utf8mb4');

if ($conexion->connect_error) {
    error_log('Error de conexion MySQL: ' . $conexion->connect_error);
    die('Error interno del servidor. Por favor intentalo mas tarde.');
}
