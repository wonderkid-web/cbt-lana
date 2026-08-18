<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../login.php");
    exit;
}

 $id_user = $_SESSION['id_user'];

// Ambil nama siswa
 $siswa_query = mysqli_query($conn, "SELECT nama_siswa, nisn FROM siswa WHERE id_user = '$id_user'");
 $siswa_data = mysqli_fetch_assoc($siswa_query);
 $siswa_nama = $siswa_data['nama_siswa'] ?? 'Siswa';

// SIMPAN WAKTU SAAT HALAMAN INI DIBUKA
// Jadi tap yang sebelumnya (berjam-jam lalu) tidak akan dihitung
if (!isset($_SESSION['waktu_buka_waiting'])) {
    $_SESSION['waktu_buka_waiting'] = date('Y-m-d H:i:s');
}

 $waktu_buka = $_SESSION['waktu_buka_waiting'];

if (isset($_GET['check'])) {
    header('Content-Type: application/json');
    
    // Cek apakah ada tap yang terjadi SETELAP halaman ini dibuka
    $cek = mysqli_query($conn, "
        SELECT ah.waktu_absen 
        FROM absensi_hari_ini ah
        JOIN siswa s ON ah.siswa_id = s.id_siswa
        WHERE s.id_user = '$id_user' AND ah.waktu_absen > '$waktu_buka'
    ");
    
    if (mysqli_num_rows($cek) > 0) {
        $row = mysqli_fetch_assoc($cek);
        
        // HAPUS waktu_buka_waiting agar tidak bisa dipakai lagi
        unset($_SESSION['waktu_buka_waiting']);
        
        echo json_encode(['verified' => true, 'waktu' => $row['waktu_absen']]);
    } else {
        echo json_encode(['verified' => false]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi RFID | CBT SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .card-box {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header-box {
            background: #0f172a;
            padding: 30px;
            text-align: center;
            color: white;
        }
        .rfid-icon {
            font-size: 80px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .rfid-icon.stop { animation: none; }
        .body-box { padding: 30px; text-align: center; }
        .status-pending {
            background: #fffbeb;
            border: 2px dashed #fbbf24;
            padding: 20px;
            border-radius: 15px;
            color: #92400e;
            margin: 20px 0;
        }
        .status-success {
            background: #dcfce7;
            border: 2px solid #22c55e;
            padding: 20px;
            border-radius: 15px;
            color: #166534;
            margin: 20px 0;
            display: none;
        }
        .btn-masuk {
            background: #22c55e;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: none;
        }
        .btn-masuk:hover { background: #16a34a; color: white; }
    </style>
</head>
<body>

<div class="card-box">
    <div class="header-box">
        <div class="rfid-icon" id="rfidIcon">📟</div>
        <h5 class="mt-2 mb-0">Verifikasi Absensi</h5>
    </div>
    
    <div class="body-box">
        <h6 class="text-muted">Selamat Datang,</h6>
        <h5 class="fw-bold text-primary"><?= htmlspecialchars($siswa_nama) ?></h5>
        
        <div class="status-pending" id="statusPending">
            <i class="bi bi-broadcast fs-4"></i><br>
            <strong>Menunggu Tap Kartu RFID...</strong><br>
            <small>Silakan tap kartu Anda pada alat yang tersedia</small>
        </div>
        
        <div class="status-success" id="statusSuccess">
            <i class="bi bi-check-circle-fill fs-1"></i><br>
            <strong>Absensi Terverifikasi!</strong><br>
            <small id="waktuText"></small>
        </div>
        
        <a href="dashboard.php" class="btn btn-masuk w-100" id="btnMasuk">
            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Ujian
        </a>
        
        <br>
        <a href="../logout.php" class="text-muted small mt-3 d-inline-block text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Keluar
        </a>
    </div>
</div>

<script>
    let interval = setInterval(() => {
        fetch('waiting_rfid.php?check=1')
            .then(r => r.json())
            .then(data => {
                if (data.verified) {
                    clearInterval(interval);
                    
                    document.getElementById('rfidIcon').textContent = '✅';
                    document.getElementById('rfidIcon').classList.add('stop');
                    document.getElementById('statusPending').style.display = 'none';
                    document.getElementById('statusSuccess').style.display = 'block';
                    document.getElementById('waktuText').textContent = 'Waktu: ' + data.waktu;
                    document.getElementById('btnMasuk').style.display = 'block';
                }
            });
    }, 2000);
</script>

</body>
</html>