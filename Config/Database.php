<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'ticketing_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Catat log ralat untuk kegunaan dalaman
            error_log("Database Connection Error: " . $e->getMessage());
            die("Ralat: Tidak dapat menyambung ke pangkalan data. Sila semak konfigurasi pelayan.");
        }
    }

    return $pdo;
}