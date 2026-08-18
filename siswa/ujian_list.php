<?php
date_default_timezone_set('Asia/Jakarta');
require_once 'auth_check.php';

$id_user = (int)$_SESSION['id_user'];

// Ambil data siswa (termasuk kelas)
$q_siswa = mysqli_query($conn, "SELECT s.*, k.nama_kelas 
                                 FROM siswa s 
                                 JOIN kelas k ON s.id_kelas = k.id_kelas 
                                 WHERE s.id_user = '$id_user'");
$siswa = mysqli_fetch_assoc($q_siswa);

if (!$siswa) {
    echo "<script>alert('Data siswa tidak ditemukan.'); window.location='logout.php';</script>";
    exit;
}

$id_kelas_siswa = (int)$siswa['id_kelas'];

// Cek apakah siswa sedang mengerjakan ujian
$q_aktif = mysqli_query($conn, "SELECT u.id_ujian, su.nama_ujian, su.token, m.nama_mapel
                                FROM ujian u 
                                JOIN sesi_ujian su ON u.id_sesi = su.id_sesi
                                JOIN mapel m ON su.id_mapel = m.id_mapel
                                WHERE u.id_user = '$id_user' AND u.status = 'sedang_dikerjakan'");
$ujian_aktif = mysqli_fetch_assoc($q_aktif);

if ($ujian_aktif) {
    $_SESSION['id_ujian_aktif'] = $ujian_aktif['id_ujian'];
    header("Location: mulai_ujian.php");
    exit;
}

// Ambil daftar sesi ujian yang AKTIF dimana kelas siswa terdaftar
$q_ujian_list = mysqli_query($conn, "SELECT su.*, m.nama_mapel,
                                        (SELECT GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ', ') 
                                         FROM sesi_ujian_kelas suk JOIN kelas k ON suk.id_kelas = k.id_kelas 
                                         WHERE suk.id_sesi = su.id_sesi) as nama_kelas,
                                        (SELECT COUNT(*) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'selesai') as sudah_selesai,
                                        (SELECT COUNT(*) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'sedang_dikerjakan') as sedang_kerja,
                                        (SELECT COUNT(DISTINCT s2.id_user) FROM siswa s2 JOIN sesi_ujian_kelas suk ON s2.id_kelas = suk.id_kelas WHERE suk.id_sesi = su.id_sesi) as total_peserta
                                 FROM sesi_ujian su
                                 JOIN mapel m ON su.id_mapel = m.id_mapel
                                 WHERE su.status = 'aktif'
                                   AND su.id_sesi IN (SELECT id_sesi FROM sesi_ujian_kelas WHERE id_kelas = '$id_kelas_siswa')
                                 ORDER BY su.tgl_mulai DESC");

// Cek status ujian untuk siswa ini (sudah selesai / belum / gagal)
function getStatusSiswa($conn, $id_sesi, $id_user) {
    $q = mysqli_query($conn, "SELECT status, nilai FROM ujian WHERE id_sesi = '$id_sesi' AND id_user = '$id_user'");
    $r = mysqli_fetch_assoc($q);
    if (!$r) return ['status' => 'belum', 'nilai' => null];
    return ['status' => $r['status'], 'nilai' => $r['nilai']];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Ujian | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; min-height: 100vh; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .status-badge { font-size: 0.75rem; padding: 6px 14px; border-radius: 20px; }
    </style>
</head>
<body>
<div class="container py-4">
    <!-- Header -->
    <div class="text-center mb-4">
        <h4 class="fw-bold text-dark"><i class="bi bi-journal-text text-primary me-2"></i>Daftar Ujian</h4>
        <p class="text-muted small">Kelas <strong><?= htmlspecialchars($siswa['nama_kelas']); ?></strong> &bull; <?= htmlspecialchars($siswa['nama_siswa']); ?></p>
    </div>

    <?php if (mysqli_num_rows($q_ujian_list) > 0): ?>
    <div class="row g-3">
        <?php while ($uj = mysqli_fetch_assoc($q_ujian_list)):
            $status = getStatusSiswa($conn, $uj['id_sesi'], $id_user);
            $now = time();
            $start = strtotime($uj['tgl_mulai']);
            $end = $start + ($uj['durasi'] * 60);

            // Waktu status
            if ($now < $start) { $time_class = 'text-warning'; $time_text = 'Belum Mulai'; $time_icon = 'bi-clock'; }
            elseif ($now >= $start && $now <= $end) { $time_class = 'text-success'; $time_text = 'Berlangsung'; $time_icon = 'bi-broadcast'; }
            else { $time_class = 'text-muted'; $time_text = 'Waktu Habis'; $time_icon = 'bi-hourglass-split'; }
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card card-custom h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($uj['nama_ujian']); ?></h6>
                            <span class="badge bg-info bg-opacity-10 text-info" style="font-size:0.72rem;"><?= htmlspecialchars($uj['nama_mapel']); ?></span>
                        </div>
                        <span class="<?= $time_class; ?>" style="font-size:0.75rem;"><i class="bi <?= $time_icon; ?> me-1"></i><?= $time_text; ?></span>
                    </div>

                    <div class="d-flex gap-2 mb-3 flex-wrap">
                        <?php 
                        $kelas_arr = explode(', ', $uj['nama_kelas'] ?? '');
                        foreach ($kelas_arr as $kn): ?>
                            <span class="badge bg-light text-dark border" style="font-size:0.68rem;"><?= htmlspecialchars(trim($kn)); ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between small text-muted mb-3">
                        <span><i class="bi bi-calendar3 me-1"></i><?= date('d M Y, H:i', strtotime($uj['tgl_mulai'])); ?></span>
                        <span><i class="bi bi-hourglass-split me-1"></i><?= $uj['durasi']; ?> menit</span>
                    </div>

                    <!-- Status Siswa -->
                                        <?php if ($status['status'] == 'belum'): ?>
                        <?php if ($now >= $start && $now <= $end): ?>
                            <form method="POST" action="konfirmasi_ujian.php" class="mb-2">
                                <input type="hidden" name="id_sesi" value="<?= $uj['id_sesi']; ?>">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Mulai Ujian
                                </button>
                            </form>
                        <?php elseif ($now < $start): ?>
                            <span class="status-badge bg-light text-warning border d-block text-center">
                                <i class="bi bi-clock me-1"></i>Menunggu Waktu Ujian
                            </span>
                        <?php else: ?>
                            <span class="status-badge bg-secondary text-white d-block text-center">
                                <i class="bi bi-hourglass-split me-1"></i>Waktu Habis
                            </span>
                        <?php endif; ?>
                    <?php elseif ($status['status'] == 'sedang_dikerjakan'): ?>
                        <span class="status-badge bg-primary text-white d-block text-center">
                            <i class="bi bi-pencil me-1"></i>Sedang Mengerjakan
                        </span>
                    <?php elseif ($status['status'] == 'selesai'): ?>
                        <div class="text-center">
                            <span class="status-badge bg-success text-white">
                                <i class="bi bi-check-circle me-1"></i>Selesai
                            </span>
                            <div class="mt-2">
                                <span class="fw-bold fs-5 <?= ($status['nilai'] >= 70) ? 'text-success' : 'text-danger'; ?>"><?= $status['nilai']; ?></span>
                            </div>
                        </div>
                    <?php elseif ($status['status'] == 'gagal'): ?>
                        <span class="status-badge bg-danger text-white d-block text-center">
                            <i class="bi bi-x-circle me-1"></i>Gagal
                        </span>
                    <?php endif; ?>

                    <!-- Progress info -->
                    <div class="d-flex justify-content-between small text-muted mt-3 pt-3 border-top">
                        <span><?= $uj['sudah_selesai']; ?> selesai</span>
                        <span><?= $uj['sedang_kerja']; ?> mengerjakan</span>
                        <span><?= $uj['total_peserta']; ?> peserta</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php else: ?>
    <div class="card card-custom p-5">
        <div class="text-center text-muted py-4">
            <i class="bi bi-journal-x fs-1 d-block mb-3 opacity-25"></i>
            <h6 class="fw-bold text-dark">Tidak Ada Ujian Aktif</h6>
            <p class="small">Saat ini tidak ada ujian yang tersedia untuk kelas Anda.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="../logout.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4">
            <i class="bi bi-box-arrow-left me-1"></i> Logout
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>