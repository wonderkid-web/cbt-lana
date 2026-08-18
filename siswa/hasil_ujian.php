<?php 
date_default_timezone_set('Asia/Jakarta');
require_once 'auth_check.php'; 

$id_ujian = isset($_GET['id_ujian']) ? (int)$_GET['id_ujian'] : 0;
$id_user = (int)$_SESSION['id_user'];

if ($id_ujian == 0) {
    header("Location: ujian_list.php");
    exit;
}

// Ambil data ujian
$q_ujian = mysqli_query($conn, "SELECT u.*, m.nama_mapel, su.nama_ujian as nama_sesi, su.durasi as durasi_sesi,
                                  s.nama_siswa, k.nama_kelas
                                  FROM ujian u 
                                  JOIN sesi_ujian su ON u.id_sesi = su.id_sesi
                                  JOIN mapel m ON u.id_mapel = m.id_mapel
                                  JOIN siswa s ON u.id_user = s.id_user
                                  JOIN kelas k ON s.id_kelas = k.id_kelas
                                  WHERE u.id_ujian = '$id_ujian' AND u.id_user = '$id_user'");
$ujian = mysqli_fetch_assoc($q_ujian);

if (!$ujian) {
    header("Location: ujian_list.php");
    exit;
}

// Hanya pemilik ujian yang bisa lihat hasil
if ($ujian['id_user'] != $id_user) {
    header("Location: ujian_list.php");
    exit;
}

// Hitung statistik jawaban
$q_stats = mysqli_query($conn, "SELECT 
                                  COUNT(*) as total_soal,
                                  SUM(CASE WHEN jawaban IS NOT NULL AND jawaban != '' THEN 1 ELSE 0 END) as dijawab,
                                  SUM(CASE WHEN is_benar = 1 THEN 1 ELSE 0 END) as benar,
                                  SUM(CASE WHEN is_benar = 0 AND jawaban IS NOT NULL AND jawaban != '' THEN 1 ELSE 0 END) as salah,
                                  SUM(CASE WHEN jawaban IS NULL OR jawaban = '' THEN 1 ELSE 0 END) as kosong
                                  FROM jawaban_siswa WHERE id_ujian = '$id_ujian'");
$stats = mysqli_fetch_assoc($q_stats);

// Ambil rincian jawaban
$q_detail = mysqli_query($conn, "SELECT js.*, bs.pertanyaan as soal, bs.opsi_a, bs.opsi_b, bs.opsi_c, bs.opsi_d, bs.opsi_e, bs.kunci_jawaban as kunci
                                  FROM jawaban_siswa js
                                  JOIN bank_soal bs ON js.id_soal = bs.id_soal
                                  WHERE js.id_ujian = '$id_ujian'
                                  ORDER BY js.id_jawaban ASC");

// Tentukan grade berdasarkan nilai
$nilai = $ujian['nilai'];
if ($nilai >= 90) $grade = 'A';
elseif ($nilai >= 80) $grade = 'B';
elseif ($nilai >= 70) $grade = 'C';
elseif ($nilai >= 60) $grade = 'D';
else $grade = 'E';

// Warna grade
$grade_colors = ['A' => '#16a34a', 'B' => '#2563eb', 'C' => '#d97706', 'D' => '#ea580c', 'E' => '#dc2626'];
$grade_color = $grade_colors[$grade] ?? '#64748b';

// Hitung waktu pengerjaan
$waktu_mulai = strtotime($ujian['waktu_mulai']);
$waktu_selesai = strtotime($ujian['waktu_selesai']);
$selisih_detik = $waktu_selesai - $waktu_mulai;
$waktu_kerja_menit = floor($selisih_detik / 60);
$waktu_kerja_detik = $selisih_detik % 60;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Ujian | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .navbar-custom { background: #0f172a; color: white; }

        /* Score Card */
        .score-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 20px; color: #fff; padding: 30px;
            box-shadow: 0 20px 50px rgba(15,23,42,0.3);
        }
        .score-circle {
            width: 140px; height: 140px; border-radius: 50%;
            border: 6px solid <?= $grade_color; ?>;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            margin: 0 auto;
        }
        .score-value { font-size: 2.5rem; font-weight: 800; line-height: 1; color: #fff; }
        .score-label { font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-top: 2px; }

        .grade-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 45px; height: 45px; border-radius: 12px; font-weight: 800;
            font-size: 1.3rem; background: <?= $grade_color; ?>; color: #fff;
        }

        .stat-card {
            background: #fff; border-radius: 12px; padding: 16px;
            border: 1px solid #e2e8f0; text-align: center;
        }
        .stat-icon { font-size: 1.5rem; margin-bottom: 6px; }
        .stat-value { font-size: 1.4rem; font-weight: 800; }
        .stat-label { font-size: 0.75rem; color: #64748b; }

        /* Detail Soal */
        .detail-card { border-radius: 16px; border: 1px solid #e2e8f0; }
        .detail-header { 
            background: #f8fafc; padding: 16px 20px; border-radius: 16px 16px 0 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-body { padding: 20px; }

        .opsi-item {
            padding: 10px 14px; border-radius: 8px; margin-bottom: 6px;
            display: flex; align-items: center; gap: 10px; font-size: 0.9rem;
        }
        .opsi-benar { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .opsi-salah { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
        .opsi-netral { background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; }
        .opsi-kosong { background: #f8fafc; border: 1px solid #e2e8f0; color: #94a3b8; font-style: italic; }

        .badge-benar { background: #dcfce7; color: #166534; font-weight: 600; }
        .badge-salah { background: #fee2e2; color: #991b1b; font-weight: 600; }
        .badge-kosong { background: #f1f5f9; color: #64748b; font-weight: 600; }

        .btn-toggle-detail {
            border-radius: 10px; font-weight: 600; font-size: 0.85rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom py-3">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand fw-bold text-white text-decoration-none">CBT - SISWA</a>
        <div class="d-flex align-items-center">
            <a href="dashboard.php" class="btn btn-light btn-sm rounded-pill px-3 me-2">
                <i class="bi bi-house me-1"></i> Dashboard
            </a>
            <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-left me-1"></i> Keluar
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">

    <!-- SCORE CARD -->
    <div class="score-card mb-4">
        <div class="row align-items-center text-center text-md-start">
            <div class="col-md-3 mb-4 mb-md-0">
                <div class="score-circle">
                    <div class="score-value"><?= number_format($nilai, 0); ?></div>
                    <div class="score-label">DARI 100</div>
                </div>
            </div>
            <div class="col-md-5 mb-4 mb-md-0">
                <h4 class="fw-bold mb-1"><?= $ujian['judul_ujian']; ?></h4>
                <p class="text-white-50 small mb-3">
                    <?= $ujian['nama_mapel']; ?> &bull; <?= $ujian['nama_kelas']; ?>
                </p>
                <div class="d-flex gap-3 justify-content-center justify-content-md-start">
                    <div>
                        <span class="text-white-50 small d-block">Grade</span>
                        <span class="grade-badge"><?= $grade; ?></span>
                    </div>
                    <div>
                        <span class="text-white-50 small d-block">Status</span>
                        <span class="badge bg-success px-3 py-2 mt-1 rounded-pill" style="font-size:0.8rem;">
                            <i class="bi bi-check-circle me-1"></i> SELESAI
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="stat-card bg-transparent border-secondary">
                            <div class="stat-icon text-white-50"><i class="bi bi-clock"></i></div>
                            <div class="stat-value text-white"><?= $waktu_kerja_menit; ?>:<span style="font-size:0.9rem;"><?= sprintf('%02d', $waktu_kerja_detik); ?></span></div>
                            <div class="stat-label" style="color:rgba(255,255,255,0.5);">Waktu Kerja</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card bg-transparent border-secondary">
                            <div class="stat-icon text-white-50"><i class="bi bi-calendar-check"></i></div>
                            <div class="stat-value text-white" style="font-size:1rem;"><?= date('d M Y', $waktu_selesai); ?></div>
                            <div class="stat-label" style="color:rgba(255,255,255,0.5);">Tanggal</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STATISTIK -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-primary"><i class="bi bi-list-check"></i></div>
                <div class="stat-value text-dark"><?= $stats['total_soal']; ?></div>
                <div class="stat-label">Total Soal</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-success"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-value text-success"><?= $stats['benar']; ?></div>
                <div class="stat-label">Benar</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-danger"><i class="bi bi-x-circle-fill"></i></div>
                <div class="stat-value text-danger"><?= $stats['salah']; ?></div>
                <div class="stat-label">Salah</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-warning"><i class="bi bi-dash-circle-fill"></i></div>
                <div class="stat-value text-warning"><?= $stats['kosong']; ?></div>
                <div class="stat-label">Tidak Dijawab</div>
            </div>
        </div>
    </div>

    <!-- RINCIAN JAWABAN -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-card-text me-2"></i>Rincian Jawaban</h5>
        <button class="btn btn-outline-secondary btn-toggle-detail" onclick="toggleDetail()">
            <i class="bi bi-eye-slash me-1" id="toggleIcon"></i> <span id="toggleText">Sembunyikan</span>
        </button>
    </div>

    <div id="detailContainer">
        <?php $no = 1; while ($d = mysqli_fetch_assoc($q_detail)): 
            $kunci = strtoupper($d['kunci']);
            $jawab = strtoupper($d['jawaban']);
            $is_kosong = empty($jawab);
            $is_benar = ($d['is_benar'] == 1);
            
            // Tentukan status badge
            if ($is_kosong) {
                $status_class = 'badge-kosong';
                $status_text = 'Tidak Dijawab';
            } elseif ($is_benar) {
                $status_class = 'badge-benar';
                $status_text = 'Benar';
            } else {
                $status_class = 'badge-salah';
                $status_text = 'Salah';
            }
        ?>
        <div class="detail-card mb-3">
            <div class="detail-header d-flex justify-content-between align-items-start">
                <div>
                    <span class="badge <?= $status_class; ?> px-3 py-2 rounded-pill mb-2">
                        <i class="bi bi-<?= $is_kosong ? 'dash-circle' : ($is_benar ? 'check-circle-fill' : 'x-circle-fill'); ?> me-1"></i>
                        <?= $status_text; ?>
                    </span>
                    <p class="fw-medium text-dark mt-2 mb-0" style="font-size: 0.95rem; line-height: 1.6;">
                        <?= $no; ?>. <?= htmlspecialchars_decode($d['soal']); ?>
                    </p>
                </div>
            </div>
            <div class="detail-body">
                <div class="row g-2">
                    <?php 
                    $opsi = ['A' => 'opsi_a', 'B' => 'opsi_b', 'C' => 'opsi_c', 'D' => 'opsi_d', 'E' => 'opsi_e'];
                    foreach ($opsi as $huruf => $kolom):
                        $isi_opsi = htmlspecialchars_decode($d[$kolom]);
                        
                        if ($huruf === $kunci && $huruf === $jawab) {
                            // Benar & dipilih
                            $opsi_class = 'opsi-benar';
                            $icon = '<i class="bi bi-check-circle-fill text-success"></i>';
                        } elseif ($huruf === $kunci) {
                            // Kunci jawaban (tidak dipilih)
                            $opsi_class = 'opsi-benar';
                            $icon = '<i class="bi bi-check-circle text-success opacity-50"></i>';
                        } elseif ($huruf === $jawab && !$is_benar) {
                            // Salah dipilih
                            $opsi_class = 'opsi-salah';
                            $icon = '<i class="bi bi-x-circle-fill text-danger"></i>';
                        } elseif ($is_kosong && $huruf === $kunci) {
                            // Kosong tapi ini kunci
                            $opsi_class = 'opsi-benar';
                            $icon = '<i class="bi bi-check-circle text-success opacity-50"></i>';
                        } else {
                            $opsi_class = 'opsi-netral';
                            $icon = '<i class="bi bi-circle text-muted" style="font-size:0.7rem;"></i>';
                        }
                    ?>
                    <div class="col-md-6">
                        <div class="opsi-item <?= $opsi_class; ?>">
                            <?= $icon; ?>
                            <span><strong><?= $huruf; ?>.</strong> <?= $isi_opsi; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!$is_kosong): ?>
                <div class="mt-2 small text-muted">
                    Jawaban Anda: <strong class="text-<?= $is_benar ? 'success' : 'danger'; ?>"><?= $jawab; ?></strong> 
                    &bull; Kunci: <strong class="text-success"><?= $kunci; ?></strong>
                </div>
                <?php else: ?>
                <div class="mt-2 small text-muted">
                    <em>Anda tidak menjawab soal ini.</em> Kunci: <strong class="text-success"><?= $kunci; ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php $no++; endwhile; ?>
    </div>

    <!-- Tombol Aksi -->
    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-primary btn-lg rounded-pill px-5 shadow">
            <i class="bi bi-house me-2"></i> Kembali ke Dashboard
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle show/hide detail
    let detailVisible = true;
    function toggleDetail() {
        const container = document.getElementById('detailContainer');
        const icon = document.getElementById('toggleIcon');
        const text = document.getElementById('toggleText');
        
        detailVisible = !detailVisible;
        container.style.display = detailVisible ? 'block' : 'none';
        icon.className = detailVisible ? 'bi bi-eye-slash me-1' : 'bi bi-eye me-1';
        text.textContent = detailVisible ? 'Sembunyikan' : 'Tampilkan';
    }
</script>
</body>
</html>