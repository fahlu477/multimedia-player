<?php
$serverName = "192.168.3.17";
$connectionOptions = array(
    "Database" => "dbMultimedia", 
    "Uid" => "sa",               
    "PWD" => "TTIadmin777!"      
);

try {
    // Membuat koneksi PDO dengan error handling yang lebih baik
    $dsn = "sqlsrv:Server=$serverName;Database={$connectionOptions['Database']};TrustServerCertificate=true";
    $conn = new PDO($dsn, $connectionOptions['Uid'], $connectionOptions['PWD']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Koneksi database gagal. Silakan coba lagi nanti.");
}

// Fungsi helper untuk logging
function logError($message) {
    error_log(date('Y-m-d H:i:s') . " - " . $message);
}

// Fungsi helper untuk sanitasi input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>
