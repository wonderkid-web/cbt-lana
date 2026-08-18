<?php
require_once '../config/database.php'; 

// 1. Cek apakah session guru ada
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    header('Location: ../login.php');
    exit;
}

// 2. Cek Status di Database
 $id_user_login = $_SESSION['id_user'];
 $query_cek_status = mysqli_query($conn, "SELECT status FROM users WHERE id_user = '$id_user_login'");
 $data_status = mysqli_fetch_assoc($query_cek_status);

// Jika user dihapus
if (!$data_status) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Jika status user adalah 'nonaktif'
if ($data_status['status'] == 'nonaktif') {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Jika masih aktif, lanjutkan
?>