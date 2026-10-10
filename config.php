<?php
declare(strict_types=1);

/*
 * Configuración de conexión.
 * Recomendado: configura estas variables de entorno en producción para no guardar
 * credenciales dentro del código.
 */
return [
    'host' => getenv('DB_HOST') ?: 'mysql-ccdoriv.alwaysdata.net',
    'user' => getenv('DB_USER') ?: 'ccdoriv',
    'password' => getenv('DB_PASSWORD') ?: '251804ys',
    'database' => getenv('DB_NAME') ?: 'ccdoriv_enviosgit',
    'charset' => 'utf8mb4',
];
