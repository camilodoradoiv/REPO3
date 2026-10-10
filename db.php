<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

function connectDatabase(array $config, bool $selectDatabase = true): PDO {
    $dsn = 'mysql:host=' . $config['host'] . ';charset=' . $config['charset'];
    if ($selectDatabase) {
        $dsn .= ';dbname=' . $config['database'];
    }
    return new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

try {
    // Primero conecta al servidor sin seleccionar la base para crearla si no existe.
    $pdo = connectDatabase($config, false);
    $dbName = str_replace('`', '``', $config['database']);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS envios (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            destinatario VARCHAR(150) NOT NULL,
            direccion VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_destinatario (destinatario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Throwable $e) {
    http_response_code(500);
    exit(
        '<!doctype html><html lang="es"><meta charset="utf-8"><title>Error de conexión</title>' .
        '<body style="font-family:Arial,sans-serif;max-width:760px;margin:50px auto;padding:20px">' .
        '<h1>No se pudo preparar la base de datos</h1>' .
        '<p>Verifica el host, usuario, contraseña y permisos del usuario MySQL para crear/usar la base de datos.</p>' .
        '<p><strong>Detalle técnico:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>' .
        '</body></html>'
    );
}
