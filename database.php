<?php
/**
 * KRISUKA E-Commerce - Database Connection Handler
 * Brand: KRISUKA (Keripik Sukun dan Keladi - Fakfak, Papua Barat)
 * 
 * Mendukung MySQL (default XAMPP) dengan auto-create database,
 * serta fallback SQLite otomatis jika MySQL service belum dinyalakan.
 */

if (!defined('KRISUKA_APP')) {
    define('KRISUKA_APP', true);
}

// Konfigurasi Database MySQL
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'krisuka_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function get_db_connection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $driver = 'mysql';

    try {
        // Coba koneksi ke MySQL server terlebih dahulu
        $dsnWithoutDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $testPdo = new PDO($dsnWithoutDb, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2
        ]);

        // Buat database krisuka_db jika belum ada
        $testPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Hubungkan ke database krisuka_db
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        $driver = 'mysql';
    } catch (PDOException $e) {
        // Fallback cerdas: Jika MySQL belum dijalankan di XAMPP, gunakan SQLite file agar sistem tetap bekerja 100%
        $sqlitePath = __DIR__ . '/krisuka_db.sqlite';
        try {
            $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            // Aktifkan foreign keys pada SQLite
            $pdo->exec("PRAGMA foreign_keys = ON;");
            $driver = 'sqlite';
        } catch (PDOException $sqle) {
            die("Fatal Database Error: Tidak dapat menghubungkan ke MySQL maupun SQLite: " . htmlspecialchars($sqle->getMessage()));
        }
    }

    // Pastikan skema tabel dan seed data awal terpasang
    require_once __DIR__ . '/installer.php';
    krisuka_run_installer($pdo, $driver);

    return $pdo;
}

