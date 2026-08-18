<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id_user'])) {
    echo 'logout';
    exit;
}

$id_u = $_SESSION['id_user'];
$query = mysqli_query($conn, "SELECT status FROM users WHERE id_user = '$id_u'");
$data = mysqli_fetch_assoc($query);

echo ($data['status'] == 'aktif') ? 'aktif' : 'nonaktif';
?>