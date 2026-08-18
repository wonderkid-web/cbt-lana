<?php
require_once 'config/database.php';

// Kita buat password baru: 123
$password_baru = password_hash('password', PASSWORD_DEFAULT);
$username = 'admin';

// Update database secara paksa
$query = "UPDATE users SET password = '$password_baru' WHERE username = '$username'";

if (mysqli_query($conn, $query)) {
    echo "<h3>Berhasil!</h3>";
    echo "Password admin sekarang adalah: <b>password</b><br>";
    echo "Silakan <a href='login.php'>Login di sini</a>";
    echo "<br><br><b>PENTING:</b> Hapus file reset.php ini setelah berhasil login demi keamanan.";
} else {
    echo "Gagal update: " . mysqli_error($conn);
}
?>