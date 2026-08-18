<?php 
date_default_timezone_set('Asia/Jakarta');
require_once 'auth_check.php'; 

// Ambil data siswa
$id_user = (int)$_SESSION['id_user'];
$q_siswa = mysqli_query($conn, "SELECT siswa.*, kelas.nama_kelas FROM siswa 
                                 JOIN kelas ON siswa.id_kelas = kelas.id_kelas 
                                 WHERE id_user = '$id_user'");
$s = mysqli_fetch_assoc($q_siswa);

// Ambil riwayat ujian yang sudah selesai
$q_riwayat = mysqli_query($conn, "SELECT u.id_ujian, u.judul_ujian, u.waktu_mulai, u.waktu_selesai, 
                                          u.durasi, u.nilai, u.status,
                                          m.nama_mapel, m.id_mapel,
                                          su.nama_ujian as nama_sesi, su.jenis_ujian
                                   FROM ujian u
                                   JOIN sesi_ujian su ON u.id_sesi = su.id_sesi
                                   JOIN mapel m ON u.id_mapel = m.id_mapel
                                   WHERE u.id_user = '$id_user' AND u.status = 'selesai'
                                   ORDER BY u.waktu_selesai DESC");

$total_ujian = mysqli_num_rows($q_riwayat);

// Hitung rata-rata nilai
$q_avg = mysqli_query($conn, "SELECT AVG(nilai) as rata_rata, MAX(nilai) as nilai_tertinggi, MIN(nilai) as nilai_terendah
                               FROM ujian WHERE id_user = '$id_user' AND status = 'selesai'");
$avg_data = mysqli_fetch_assoc($q_avg);
$rata_rata = $avg_data['rata_rata'] ? round($avg_data['rata_rata'], 1) : 0;
$nilai_tertinggi = $avg_data['nilai_tertinggi'] ? round($avg_data['nilai_tertinggi'], 1) : 0;
$nilai_terendah = $avg_data['nilai_terendah'] ? round($avg_data['nilai_terendah'], 1) : 0;

// Helper: tentukan grade & warna
function getGrade($nilai) {
    if ($nilai >= 90) return ['grade' => 'A', 'color' => '#16a34a', 'bg' => '#dcfce7'];
    elseif ($nilai >= 80) return ['grade' => 'B', 'color' => '#2563eb', 'bg' => '#dbeafe'];
    elseif ($nilai >= 70) return ['grade' => 'C', 'color' => '#d97706', 'bg' => '#fef3c7'];
    elseif ($nilai >= 60) return ['grade' => 'D', 'color' => '#ea580c', 'bg' => '#ffedd5'];
    else return ['grade' => 'E', 'color' => '#dc2626', 'bg' => '#fee2e2'];
}

// Helper: tentukan status warna
function getStatusColor($status) {
    return $status == 'selesai' ? '#16a34a' : '#64748b';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Nilai | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .navbar-custom { background: #0f172a; color: white; }

        /* Summary Cards */
        .summary-card {
            background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;
            padding: 20px; text-align: center; transition: transform 0.2s;
        }
        .summary-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
        .summary-icon {
            width: 50px; height: 50px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px; font-size: 1.3rem;
        }
        .summary-value { font-size: 1.6rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .summary-label { font-size: 0.78rem; color: #64748b; margin-top: 4px; }

        /* Table */
        .table-custom { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        .table-custom thead th { 
            background: #f8fafc; color: #64748b; font-size: 0.78rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0;
            padding: 14px 16px;
        }
        .table-custom tbody td { 
            padding: 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; 
        }
        .table-custom tbody tr:hover { background: #f8fafc; }

        /* Grade Badge */
        .grade-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 38px; height: 38px; border-radius: 10px; font-weight: 800;
            font-size: 1rem; color: #fff;
        }
        .nilai-text { font-size: 1.1rem; font-weight: 700; }

        /* Badge mapel */
        .badge-mapel { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-weight: 500; font-size: 0.78rem; }

        /* Status Badge */
        .badge-selesai { background: #dcfce7; color: #166534; font-weight: 600; }

        /* Empty state */
        .empty-state { border-radius: 16px; background: #fff; border: 2px dashed #e2e8f0; }

        /* Filter pills */
        .filter-pill {
            border: 2px solid #e2e8f0; background: #fff; border-radius: 50px;
            padding: 6px 16px; font-size: 0.82rem; font-weight: 600; color: #475569;
            cursor: pointer; transition: all 0.2s;
        }
        .filter-pill:hover, .filter-pill.active { border-color: #3b82f6; background: #3b82f6; color: #fff; }

        /* Link detail */
        .btn-detail {
            border-radius: 10px; font-size: 0.8rem; font-weight: 600; padding: 6px 14px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom py-3">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand fw-bold text-white text-decoration-none">CBT - SISWA</a>
        <div class="d-flex align-items-center">
            <span class="me-3 d-none d-md-inline text-white"><?= $s['nama_siswa']; ?></span>
            <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-left me-1"></i> Keluar
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Riwayat Nilai Ujian</h4>
            <p class="text-muted small mb-0"><?= $s['nama_siswa']; ?> &bull; Kelas <?= $s['nama_kelas']; ?></p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-primary btn-sm rounded-pill">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="summary-card">
                <div class="summary-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div class="summary-value"><?= $total_ujian; ?></div>
                <div class="summary-label">Ujian Dikerjakan</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card">
                <div class="summary-icon" style="background:#dbeafe;color:#2563eb;">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="summary-value" style="color:#2563eb;"><?= $rata_rata; ?></div>
                <div class="summary-label">Rata-rata Nilai</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card">
                <div class="summary-icon" style="background:#dcfce7;color:#16a34a;">
                    <i class="bi bi-trophy"></i>
                </div>
                <div class="summary-value" style="color:#16a34a;"><?= $nilai_tertinggi; ?></div>
                <div class="summary-label">Nilai Tertinggi</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card">
                <div class="summary-icon" style="background:#fee2e2;color:#dc2626;">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <div class="summary-value" style="color:#dc2626;"><?= $nilai_terendah; ?></div>
                <div class="summary-label">Nilai Terendah</div>
            </div>
        </div>
    </div>

    <!-- Daftar Riwayat -->
    <?php if ($total_ujian > 0): ?>
    <div class="card table-custom">
        <div class="card-header bg-white border-bottom-0 pt-3 px-4">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-table me-2"></i>Daftar Nilai</h6>
                <span class="badge bg-light text-muted border px-3 py-2" style="font-size:0.78rem;">
                    <?= $total_ujian; ?> data
                </span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" width="5%">No</th>
                        <th>Ujian</th>
                        <th>Mata Pelajaran</th>
                        <th class="text-center d-none d-md-table-cell">Tanggal</th>
                        <th class="text-center d-none d-md-table-cell">Durasi</th>
                        <th class="text-center">Nilai</th>
                        <th class="text-center">Grade</th>
                        <th class="text-center" width="10%">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; 
                    // Kelompokkan data per mapel untuk statistik
                    $mapel_stats = [];
                    while ($r = mysqli_fetch_assoc($q_riwayat)):
                        $g = getGrade($r['nilai']);
                        // Hitung waktu kerja
                        $mulai = strtotime($r['waktu_mulai']);
                        $selesai = strtotime($r['waktu_selesai']);
                        $diff_menit = floor(($selesai - $mulai) / 60);
                    ?>
                    <tr>
                        <td class="text-center fw-bold text-muted"><?= $no++; ?></td>
                        <td>
                            <div class="fw-bold text-dark" style="font-size:0.92rem;"><?= $r['judul_ujian']; ?></div>
                            <small class="text-muted"><?= $r['jenis_ujian']; ?></small>
                        </td>
                        <td>
                            <span class="badge badge-mapel px-2 py-1 rounded-pill"><?= $r['nama_mapel']; ?></span>
                        </td>
                        <td class="text-center d-none d-md-table-cell">
                            <div class="small fw-bold"><?= date('d M Y', $selesai); ?></div>
                            <div class="small text-muted"><?= date('H:i', $mulai); ?> WIB</div>
                        </td>
                        <td class="text-center d-none d-md-table-cell">
                            <span class="fw-bold" style="font-size:0.92rem;"><?= $diff_menit; ?> <small class="text-muted">mnt</small></span>
                        </td>
                        <td class="text-center">
                            <span class="nilai-text" style="color:<?= $g['color']; ?>;"><?= number_format($r['nilai'], 0); ?></span>
                        </td>
                        <td class="text-center">
                            <span class="grade-badge" style="background:<?= $g['color']; ?>;"><?= $g['grade']; ?></span>
                        </td>
                        <td class="text-center">
                            <a href="hasil_ujian.php?id_ujian=<?= $r['id_ujian']; ?>" class="btn btn-outline-primary btn-detail">
                                <i class="bi bi-eye me-1"></i> Lihat
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ringkasan Per Mapel -->
    <?php 
    // Ambil ulang untuk ringkasan per mapel
    mysqli_data_seek($q_riwayat, 0);
    $mapel_data = [];
    while ($r = mysqli_fetch_assoc($q_riwayat)) {
        $mp = $r['nama_mapel'];
        if (!isset($mapel_data[$mp])) {
            $mapel_data[$mp] = ['total' => 0, 'benar_total' => 0, 'nilai_sum' => 0];
        }
        $mapel_data[$mp]['total']++;
        $mapel_data[$mp]['nilai_sum'] += $r['nilai'];
    }
    ?>

    <?php if (count($mapel_data) > 1): ?>
    <div class="card table-custom mt-4">
        <div class="card-header bg-white border-bottom-0 pt-3 px-4">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart me-2"></i>Ringkasan Per Mata Pelajaran</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <?php foreach ($mapel_data as $mapel => $data): 
                    $avg_mapel = round($data['nilai_sum'] / $data['total'], 1);
                    $g = getGrade($avg_mapel);
                ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="p-3 rounded-3" style="background:<?= $g['bg']; ?>; border: 1px solid <?= $g['color'] ?>22;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <small class="fw-bold" style="color:<?= $g['color']; ?>;"><?= $mapel; ?></small>
                            <span class="badge rounded-pill px-2" style="background:<?= $g['color']; ?>; color:#fff; font-size:0.7rem;"><?= $g['grade']; ?></span>
                        </div>
                        <div class="fw-bold" style="font-size:1.4rem; color:<?= $g['color']; ?>;"><?= $avg_mapel; ?></div>
                        <small class="text-muted"><?= $data['total']; ?> ujian &bull; rata-rata</small>
                        <!-- Progress bar -->
                        <div class="progress mt-2" style="height: 4px; background:<?= $g['color'] ?>33;">
                            <div class="progress-bar" style="width:<?= $avg_mapel; ?>%; background:<?= $g['color']; ?>;"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state text-center py-5">
        <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
        <h5 class="fw-bold text-muted">Belum Ada Riwayat Ujian</h5>
        <p class="text-muted small mb-4">Anda belum pernah mengerjakan ujian apapun.</p>
        <a href="ujian_list.php" class="btn btn-primary btn-sm rounded-pill px-4">
            <i class="bi bi-pencil-square me-1"></i> Lihat Ujian Tersedia
        </a>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>