<?php 
require_once 'auth_check.php'; 
// Ambil data detail siswa untuk ditampilkan
$id_u = $_SESSION['id_user'];
$query_siswa = mysqli_query($conn, "SELECT siswa.*, kelas.nama_kelas FROM siswa 
               JOIN kelas ON siswa.id_kelas = kelas.id_kelas 
               WHERE id_user = '$id_u'");
$s = mysqli_fetch_assoc($query_siswa);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .navbar-custom { background: #0f172a; color: white; }
        .card-profile { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .welcome-banner { 
            background: linear-gradient(45deg, #3b82f6, #2563eb); 
            color: white; border-radius: 20px; padding: 30px; 
        }
    </style>
</head>
<body>

<script>
    // Fungsi untuk mengecek status secara berkala
    function checkUserStatus() {
        fetch('cek_status_ajax.php')
            .then(response => response.text())
            .then(status => {
                if (status === 'nonaktif' || status === 'logout') {
                    // Jika status berubah jadi nonaktif, langsung lempar ke login
                    window.location.href = '../login.php?msg=nonaktif';
                }
            })
            .catch(error => console.error('Error checking status:', error));
    }

    // Jalankan pengecekan setiap 5 detik (5000 ms)
    // Kamu bisa mempercepatnya jadi 3 detik jika ingin lebih responsif
    setInterval(checkUserStatus, 5000);
</script>

<nav class="navbar navbar-expand-lg navbar-custom py-3">
    <div class="container">
        <span class="navbar-brand fw-bold text-white">CBT - SISWA</span>
        <div class="d-flex align-items-center">
            <span class="me-3 d-none d-md-inline"><?= $s['nama_siswa']; ?></span>
            <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-left me-1"></i> Keluar
            </a>
        </div>
    </div>
</nav>
<?php 
// Ambil maksimal 2 kata pertama sebagai nama panggilan
 $nama_parts = explode(' ', trim($s['nama_siswa']));
 $nama_panggilan = count($nama_parts) > 1 ? $nama_parts[0] . ' ' . $nama_parts[1] : $nama_parts[0];
?>
<div class="container mt-4">
    <div class="welcome-banner mb-4">
        <h2 class="fw-bold">Selamat Datang, <?= $nama_panggilan; ?>! 👋</h2>
        <p class="mb-0">Pastikan kartu ujianmu sudah aktif sebelum memulai sesi ujian.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card card-profile p-4 text-center">
                <div class="mb-3">
                    <i class="bi bi-person-circle text-primary" style="font-size: 5rem;"></i>
                </div>
                <h5 class="fw-bold mb-0"><?= $s['nama_siswa']; ?></h5>
                <p class="text-muted small">NISN: <?= $s['nisn']; ?></p>
                <hr>
                <div class="d-flex justify-content-between px-3">
                    <span class="text-muted small">Kelas</span>
                    <span class="badge bg-primary rounded-pill"><?= $s['nama_kelas']; ?></span>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-profile p-4 h-100">
                <h5 class="fw-bold mb-4"><i class="bi bi-journal-text me-2"></i> Menu Utama</h5>
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <a href="ujian_list.php" class="text-decoration-none">
                            <div class="p-3 bg-light rounded-4 text-center border">
                                <i class="bi bi-pencil-square fs-2 text-primary"></i>
                                <div class="mt-2 text-dark fw-bold small">Mulai Ujian</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4">
                        <a href="riwayat_nilai.php" class="text-decoration-none">
                            <div class="p-3 bg-light rounded-4 text-center border">
                                <i class="bi bi-clipboard-data fs-2 text-success"></i>
                                <div class="mt-2 text-dark fw-bold small">Hasil Ujian</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>