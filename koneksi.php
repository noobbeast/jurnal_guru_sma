<?php
// Pastikan direktori logs ada
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Error Handler
function errorHandler($errno, $errstr, $errfile, $errline) {
    $message = "[" . date('Y-m-d H:i:s') . "] Error: $errstr in $errfile on line $errline\n";
    error_log($message, 3, __DIR__ . '/logs/error.log');
    return true; // Jangan tampilkan error ke user
}

function exceptionHandler($exception) {
    $message = "[" . date('Y-m-d H:i:s') . "] Exception: " . $exception->getMessage() . 
               " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    error_log($message, 3, __DIR__ . '/logs/error.log');
    // Tampilkan pesan error ramah pengguna di production
    if (!ini_get('display_errors')) {
        echo "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
    }
}

// Daftarkan handler
set_error_handler("errorHandler");
set_exception_handler("exceptionHandler");

// Pengaturan error untuk production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Koneksi database
$host = 'localhost';
$db   = 'db_jurnal_sma';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Exception handler akan menangani error ini
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>