<?php
$host = "localhost";   // ALWAYS localhost if PostgreSQL is on same PC
$port = "5432";
$dbname = "rafiq";
$user = "postgres";
$password = "123456789";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    // echo "Connected successfully";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>