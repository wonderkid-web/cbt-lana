<?php
// config/database.php

// Nilai default = setelan XAMPP (agar tetap jalan di luar Docker).
// Di dalam Docker, nilai diambil dari environment variable pada docker-compose.yml.
$host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : "localhost";
$user = getenv('DB_USER') !== false ? getenv('DB_USER') : "root";
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ""; // Kosongkan jika pakai XAMPP default
$db   = getenv('DB_NAME') !== false ? getenv('DB_NAME') : "db_cbt_binjai"; // Sesuaikan dengan nama database di phpMyAdmin

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
