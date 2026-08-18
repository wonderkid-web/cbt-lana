<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'response' => 'ERROR']);
    exit;
}

 $input = json_decode(file_get_contents('php://input'), true);
 $uid = strtoupper(trim($input['uid'] ?? ''));

if (empty($uid)) {
    echo json_encode(['success' => false, 'response' => 'ERROR']);
    exit;
}

try {
    // 1. Cek kartu di database (Status diambil dari tabel USERS)
 $query = "
    SELECT kr.siswa_id, u.status, s.nama_siswa 
    FROM kartu_rfid kr
    JOIN siswa s ON kr.siswa_id = s.id_siswa
    JOIN users u ON s.id_user = u.id_user
    WHERE kr.uid_rfid = '$uid'
";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'response' => 'NOTFOUND']);
        exit;
    }

    $kartu = mysqli_fetch_assoc($result);

    if ($kartu['status'] !== 'aktif') {
        echo json_encode(['success' => false, 'response' => 'INVALID']);
        exit;
    }

    $siswa_id = $kartu['siswa_id'];
    $today = date('Y-m-d');

    // Cek sudah absen belum
    $cek = mysqli_query($conn, "SELECT id FROM absensi_hari_ini WHERE siswa_id = '$siswa_id' AND tanggal = '$today'");
    
    if (mysqli_num_rows($cek) > 0) {
        echo json_encode(['success' => false, 'response' => 'ALREADY']);
        exit;
    }

    // Simpan absensi
    mysqli_query($conn, "INSERT INTO absensi_hari_ini (siswa_id, tanggal, waktu_absen) VALUES ('$siswa_id', '$today', NOW())");
    
    // Simpan log
    mysqli_query($conn, "INSERT INTO log_rfid (siswa_id, uid_rfid) VALUES ('$siswa_id', '$uid')");

    echo json_encode(['success' => true, 'response' => "SUCCESS:" . $kartu['nama_siswa']]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'response' => 'ERROR']);
}