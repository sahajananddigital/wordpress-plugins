<?php
// src/db.php
require_once __DIR__ . '/config.php';

function get_db_connection(): PDO
{
    static $pdo = null; // Reuse connection

    if ($pdo === null) {
        if (!file_exists(DB_PATH)) {
            // Ideally setup.php should have been run, but check just in case
            throw new Exception("Database file not found at " . DB_PATH . ". Please run setup.php first.");
        }

        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Performance: Write-Ahead Logging
            $pdo->exec('PRAGMA journal_mode = WAL;');
            // Optional: Synchronous=NORMAL is faster and safe enough for most WAL usage
            $pdo->exec('PRAGMA synchronous = NORMAL;');

        } catch (PDOException $e) {
            // Log error internally, don't show full details to public
            error_log("DB Connection Error: " . $e->getMessage());
            die("Database connection failed.");
        }
    }

    return $pdo;
}
