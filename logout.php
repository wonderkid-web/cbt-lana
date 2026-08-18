<?php
// Memulai session agar bisa diakses dan dihancurkan
session_start();

// Menghapus semua variabel session yang terdaftar
$_SESSION = array();

// Jika ingin menghapus session cookie juga (opsional tapi lebih aman)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Menghancurkan session di server
session_destroy();

// Mengalihkan pengguna kembali ke halaman login
// Sesuaikan path 'login.php' dengan letak file login kamu
header("Location: login.php?msg=logout");
exit;
?>