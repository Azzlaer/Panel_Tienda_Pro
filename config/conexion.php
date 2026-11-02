<?php
if (!defined('BASE_URL')) require_once __DIR__ . '/config.php';
require_once __DIR__ . '/install_guard.php';
$databaseConfig = require __DIR__ . '/database.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conexion = new mysqli(
        $databaseConfig['host'], $databaseConfig['user'], $databaseConfig['password'],
        $databaseConfig['name'], (int) $databaseConfig['port']
    );
    $conexion->set_charset('utf8mb4');
} catch (Throwable $e) {
    http_response_code(503);
    exit('No se pudo conectar a la base de datos. Revisa config/database.php y que MySQL esté iniciado.');
}
