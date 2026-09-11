<?php

$host = "localhost";
$dbname = "a0071254_pagos";
$user = "a0071254_pagos";
$pass = "@VPa3GVwH*EW8gI";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}