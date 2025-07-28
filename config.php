<?php
$serverName = "192.168.3.17"; // Hostname SQL Server
$connectionOptions = array(
    "Database" => "dbMultimedia", 
    "Uid" => "sa",               
    "PWD" => "TTIadmin777!"      
);

try {
    // Membuat koneksi PDO
    $conn = new PDO("sqlsrv:Server=$serverName;Database={$connectionOptions['Database']}", $connectionOptions['Uid'], $connectionOptions['PWD']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>