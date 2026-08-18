<?php
require_once '../config/check_admin.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$id_sesi = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_sesi <= 0) {
    echo json_encode(['kelas_ids' => []]);
    exit;
}

$q = mysqli_query($conn, "SELECT id_kelas FROM sesi_ujian_kelas WHERE id_sesi = '$id_sesi'");
$kelas_ids = [];
while ($row = mysqli_fetch_assoc($q)) {
    $kelas_ids[] = (int)$row['id_kelas'];
}

echo json_encode(['kelas_ids' => $kelas_ids]);
