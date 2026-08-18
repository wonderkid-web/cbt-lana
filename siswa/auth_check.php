<?php
session_start();
require_once '../config/database.php';

// 1. Cek apakah sudah login dan rolenya benar
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../login.php");
    exit;
}

// 2. AMBIL STATUS TERBARU DARI DATABASE (Fitur Tendang Otomatis)
$id_u = $_SESSION['id_user'];
$query_status = mysqli_query($conn, "SELECT status FROM users WHERE id_user = '$id_u'");
$user_data = mysqli_fetch_assoc($query_status);

if ($user_data['status'] !== 'aktif') {
    // Jika status di database sudah tidak aktif, hapus session dan paksa logout
    session_unset();
    session_destroy();
    header("Location: ../login.php?msg=nonaktif");
    exit;
}
?>