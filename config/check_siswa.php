<?php
session_start();
require_once 'database.php'; // Pastikan koneksi db ada

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../login.php");
    exit;
}

// Cek status terbaru dari database
$id_u = $_SESSION['id_user'];
$check_status = mysqli_query($conn, "SELECT status FROM users WHERE id_user = '$id_u'");
$u = mysqli_fetch_assoc($check_status);

if ($u['status'] !== 'aktif') {
    // Jika status tiba-tiba jadi nonaktif, hapus session dan tendang ke login
    session_destroy();
    header("Location: ../login.php?msg=blocked");
    exit;
}
?>