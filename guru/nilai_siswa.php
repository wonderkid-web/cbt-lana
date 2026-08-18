<?php
// === NILAI SISWA GURU ===
// File ini di-include oleh dashboard.php (page=nilai_siswa)
// Guru melihat rekap nilai siswa hanya untuk mapel yang diampunya

date_default_timezone_set('Asia/Jakarta');

$id_user = (int)$_SESSION['id_user'];

// Ambil data guru untuk dapatkan id_mapel
$q_guru_mapel = mysqli_query($conn, "SELECT guru.id_mapel, mapel.nama_mapel 
                                      FROM guru 
                                      LEFT JOIN mapel ON guru.id_mapel = mapel.id_mapel 
                                      WHERE guru.id_user = '$id_user'");
$guru_mapel = mysqli_fetch_assoc($q_guru_mapel);
$id_mapel_guru = (int)($guru_mapel['id_mapel'] ?? 0);

// --- PROSES: EXPORT CSV ---
if (isset($_GET['export_csv'])) {
    $id_sesi_export = (int)$_GET['export_csv'];
    
    // Verifikasi sesi milik mapel guru
    $cek = mysqli_query($conn, "SELECT su.*, k.nama_kelas, m.nama_mapel
                                 FROM sesi_ujian su
                                 LEFT JOIN kelas k ON su.id_kelas = k.id_kelas
                                 LEFT JOIN mapel m ON su.id_mapel = m.id_mapel
                                 WHERE su.id_sesi = '$id_sesi_export' AND su.id_mapel = '$id_mapel_guru'");
    $sesi_export = mysqli_fetch_assoc($cek);
    
    if (!$sesi_export) {
        header("Location: ?page=nilai_siswa&msg=unauthorized");
        exit;
    }

    // FIXED: u.id_siswa → u.id_user
    $q_export = mysqli_query($conn, "SELECT s.nis, s.nama_siswa, k.nama_kelas, su.nama_ujian,
                                             u.nilai, u.status, u.waktu_mulai, u.waktu_selesai,
                                             CASE 
                                                 WHEN u.nilai >= 90 THEN 'A'
                                                 WHEN u.nilai >= 80 THEN 'B'
                                                 WHEN u.nilai >= 70 THEN 'C'
                                                 WHEN u.nilai >= 60 THEN 'D'
                                                 ELSE 'E'
                                             END as grade
                                      FROM ujian u
                                      JOIN siswa s ON u.id_user = s.id_user
                                      JOIN kelas k ON s.id_kelas = k.id_kelas
                                      JOIN sesi_ujian su ON u.id_sesi = su.id_sesi
                                      WHERE u.id_sesi = '$id_sesi_export'
                                      ORDER BY u.nilai DESC, s.nama_siswa ASC");

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="nilai_' . strtolower(str_replace(' ', '_', $sesi_export['nama_ujian'])) . '_' . date('Ymd') . '.csv"');

    $output = fopen('php://output', 'w');
    // BOM untuk Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['No', 'NIS', 'Nama Siswa', 'Kelas', 'Ujian', 'Nilai', 'Grade', 'Status', 'Waktu Mulai', 'Waktu Selesai']);
    
    $no = 1;
    while ($row = mysqli_fetch_assoc($q_export)) {
        fputcsv($output, [
            $no++,
            $row['nis'],
            $row['nama_siswa'],
            $row['nama_kelas'],
            $row['nama_ujian'],
            $row['nilai'],
            $row['grade'],
            ucfirst($row['status']),
            $row['waktu_mulai'],
            $row['waktu_selesai']
        ]);
    }
    fclose($output);
    exit;
}

// --- PROSES: HAPUS NILAI (reset ujian siswa) ---
if (isset($_GET['hapus_nilai'])) {
    $id_ujian = (int)$_GET['hapus_nilai'];
    
    // Verifikasi bahwa ujian ini untuk mapel guru
    $cek = mysqli_query($conn, "SELECT u.id_ujian, u.id_sesi
                                 FROM ujian u
                                 JOIN sesi_ujian su ON u.id_sesi = su.id_sesi
                                 WHERE u.id_ujian = '$id_ujian' AND su.id_mapel = '$id_mapel_guru'");
    if ($d = mysqli_fetch_assoc($cek)) {
        // Hapus jawaban siswa
        mysqli_query($conn, "DELETE FROM jawaban_siswa WHERE id_ujian = '$id_ujian'");
        // Hapus record ujian
        mysqli_query($conn, "DELETE FROM ujian WHERE id_ujian = '$id_ujian'");
        header("Location: ?page=nilai_siswa&msg=nilai_dihapus");
    } else {
        header("Location: ?page=nilai_siswa&msg=unauthorized");
    }
    exit;
}

// --- FILTER ---
$filter_sesi = isset($_GET['filter_sesi']) ? (int)$_GET['filter_sesi'] : 0;
$filter_kelas = isset($_GET['filter_kelas']) ? (int)$_GET['filter_kelas'] : 0;

// --- PAGINATION ---
$batas = 15;
$halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;
$sebelum = $halaman - 1;
$sesudah = $halaman + 1;

// Build WHERE clause
$where = "su.id_mapel = '$id_mapel_guru'";
if ($filter_sesi > 0) $where .= " AND su.id_sesi = '$filter_sesi'";
if ($filter_kelas > 0) $where .= " AND s.id_kelas = '$filter_kelas'";

// FIXED: u.id_siswa → u.id_user
$q_count = mysqli_query($conn, "SELECT COUNT(*) as total 
                                  FROM ujian u
                                  JOIN siswa s ON u.id_user = s.id_user
                                  JOIN kelas k ON s.id_kelas = k.id_kelas
                                  JOIN sesi_ujian su ON u.id_sesi = su.id_sesi
                                  WHERE $where AND u.status = 'selesai'");
$jumlah_data = mysqli_fetch_assoc($q_count)['total'];
$total_halaman = ceil($jumlah_data / $batas);

// FIXED: u.id_siswa → u.id_user
$q_nilai = mysqli_query($conn, "SELECT u.id_ujian, s.nisn, s.nama_siswa, k.nama_kelas, k.id_kelas,
                                        su.id_sesi, su.nama_ujian, su.jenis_ujian, su.tgl_mulai,
                                        u.nilai, u.status, u.waktu_mulai as waktu_mulai_ujian, u.waktu_selesai,
                                        CASE 
                                            WHEN u.nilai >= 90 THEN 'A'
                                            WHEN u.nilai >= 80 THEN 'B'
                                            WHEN u.nilai >= 70 THEN 'C'
                                            WHEN u.nilai >= 60 THEN 'D'
                                            ELSE 'E'
                                        END as grade
                                 FROM ujian u
                                 JOIN siswa s ON u.id_user = s.id_user
                                 JOIN kelas k ON s.id_kelas = k.id_kelas
                                 JOIN sesi_ujian su ON u.id_sesi = su.id_sesi
                                 WHERE $where AND u.status = 'selesai'
                                 ORDER BY su.tgl_mulai DESC, u.nilai DESC
                                 LIMIT $halaman_awal, $batas");

// Data untuk dropdown filter
$q_sesi_filter = mysqli_query($conn, "SELECT su.id_sesi, su.nama_ujian, k.nama_kelas, su.tgl_mulai
                                        FROM sesi_ujian su
                                        LEFT JOIN kelas k ON su.id_kelas = k.id_kelas
                                        WHERE su.id_mapel = '$id_mapel_guru'
                                        ORDER BY su.tgl_mulai DESC");

$q_kelas_filter = mysqli_query($conn, "SELECT DISTINCT k.id_kelas, k.nama_kelas
                                         FROM sesi_ujian su
                                         JOIN kelas k ON su.id_kelas = k.id_kelas
                                         WHERE su.id_mapel = '$id_mapel_guru'
                                         ORDER BY k.nama_kelas ASC");

// Statistik keseluruhan
$q_stats = mysqli_query($conn, "SELECT 
    COUNT(*) as total_peserta,
    ROUND(AVG(u.nilai),1) as rata_rata,
    MAX(u.nilai) as nilai_tertinggi,
    MIN(u.nilai) as nilai_terendah,
    SUM(CASE WHEN u.nilai >= 90 THEN 1 ELSE 0 END) as grade_a,
    SUM(CASE WHEN u.nilai >= 80 AND u.nilai < 90 THEN 1 ELSE 0 END) as grade_b,
    SUM(CASE WHEN u.nilai >= 70 AND u.nilai < 80 THEN 1 ELSE 0 END) as grade_c,
    SUM(CASE WHEN u.nilai >= 60 AND u.nilai < 70 THEN 1 ELSE 0 END) as grade_d,
    SUM(CASE WHEN u.nilai < 60 THEN 1 ELSE 0 END) as grade_e
    FROM ujian u 
    JOIN sesi_ujian su ON u.id_sesi = su.id_sesi 
    WHERE su.id_mapel = '$id_mapel_guru' AND u.status = 'selesai'");
$stats = mysqli_fetch_assoc($q_stats);

// Statistik per ujian (untuk tabel ringkas)
$q_per_ujian = mysqli_query($conn, "SELECT su.id_sesi, su.nama_ujian, k.nama_kelas, su.tgl_mulai,
                                           COUNT(*) as peserta,
                                           ROUND(AVG(u.nilai),1) as rata,
                                           MAX(u.nilai) as tertinggi,
                                           MIN(u.nilai) as terendah,
                                           SUM(CASE WHEN u.nilai >= 70 THEN 1 ELSE 0 END) as lulus,
                                           SUM(CASE WHEN u.nilai < 70 THEN 1 ELSE 0 END) as tidak_lulus
                                    FROM ujian u
                                    JOIN sesi_ujian su ON u.id_sesi = su.id_sesi
                                    LEFT JOIN kelas k ON su.id_kelas = k.id_kelas
                                    WHERE su.id_mapel = '$id_mapel_guru' AND u.status = 'selesai'
                                    GROUP BY su.id_sesi
                                    ORDER BY su.tgl_mulai DESC");
?>

<!-- ALERT MESSAGE -->
<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-<?= (in_array($_GET['msg'], ['unauthorized','gagal'])) ? 'danger' : 'success'; ?> border-0 shadow-sm mb-4 d-flex align-items-center">
    <i class="bi bi-<?= (in_array($_GET['msg'], ['unauthorized','gagal'])) ? 'exclamation-triangle-fill' : 'check-circle-fill'; ?> me-2"></i>
    <?php 
    $msgs = [
        'nilai_dihapus' => 'Data nilai berhasil dihapus (ujian direset).',
        'unauthorized' => 'Anda tidak memiliki akses untuk operasi ini.',
        'gagal' => 'Operasi gagal dilakukan.',
    ];
    echo $msgs[$_GET['msg']] ?? 'Operasi berhasil.';
    ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold text-dark mb-0">Nilai Siswa</h5>
        <p class="text-muted small mb-0">Mapel: <strong><?= $guru_mapel['nama_mapel'] ?? '-'; ?></strong> &bull; Rekap hasil ujian siswa untuk mapel Anda</p>
    </div>
    <?php if ($filter_sesi > 0): ?>
    <a href="?page=nilai_siswa&export_csv=<?= $filter_sesi; ?>" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm">
        <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
    </a>
    <?php endif; ?>
</div>

<!-- Statistik Keseluruhan -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-2">
        <div class="stat-card">
            <div class="text-center">
                <div class="stat-label">TOTAL PESERTA</div>
                <div class="stat-value text-dark mt-1"><?= $stats['total_peserta'] ?? 0; ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card">
            <div class="text-center">
                <div class="stat-label">RATA-RATA</div>
                <div class="stat-value text-primary mt-1"><?= $stats['rata_rata'] ?? 0; ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card">
            <div class="text-center">
                <div class="stat-label">TERTINGGI</div>
                <div class="stat-value text-success mt-1"><?= $stats['nilai_tertinggi'] ?? 0; ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card">
            <div class="text-center">
                <div class="stat-label">TERENDAH</div>
                <div class="stat-value text-danger mt-1"><?= $stats['nilai_terendah'] ?? 0; ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card">
            <div class="text-center">
                <div class="stat-label">LULUS (≥70)</div>
                <div class="stat-value mt-1" style="color:#16a34a;"><?= ($stats['grade_a'] ?? 0) + ($stats['grade_b'] ?? 0) + ($stats['grade_c'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card">
            <div class="text-center">
                <div class="stat-label">TIDAK LULUS</div>
                <div class="stat-value text-danger mt-1"><?= ($stats['grade_d'] ?? 0) + ($stats['grade_e'] ?? 0); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Distribusi Grade -->
<?php if (($stats['total_peserta'] ?? 0) > 0): ?>
<div class="card card-custom p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart me-2"></i>Distribusi Grade</h6>
        <span class="small text-muted"><?= $stats['total_peserta']; ?> peserta</span>
    </div>
    <div class="row g-3">
        <?php 
        $grades = [
            ['A', $stats['grade_a'] ?? 0, '#16a34a', '#dcfce7', 'Sangat Baik'],
            ['B', $stats['grade_b'] ?? 0, '#2563eb', '#dbeafe', 'Baik'],
            ['C', $stats['grade_c'] ?? 0, '#d97706', '#fef3c7', 'Cukup'],
            ['D', $stats['grade_d'] ?? 0, '#ea580c', '#ffedd5', 'Kurang'],
            ['E', $stats['grade_e'] ?? 0, '#dc2626', '#fee2e2', 'Sangat Kurang'],
        ];
        foreach ($grades as $g):
            $pct = ($stats['total_peserta'] ?? 0) > 0 ? round(($g[1] / $stats['total_peserta']) * 100) : 0;
        ?>
        <div class="col">
            <div class="text-center p-3 rounded-3" style="background:<?= $g[3]; ?>;">
                <div class="fw-bold fs-2" style="color:<?= $g[2]; ?>;"><?= $g[1]; ?></div>
                <div class="fw-bold" style="color:<?= $g[2]; ?>; font-size:0.85rem;">Grade <?= $g[0]; ?></div>
                <small class="text-muted"><?= $g[4]; ?> (<?= $pct; ?>%)</small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Filter -->
<div class="card card-custom p-3 mb-4">
    <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
        <input type="hidden" name="page" value="nilai_siswa">
        <span class="small fw-bold text-muted"><i class="bi bi-funnel me-1"></i> Filter:</span>
        <select name="filter_sesi" class="form-select form-select-sm" style="max-width:280px;" onchange="this.form.submit()">
            <option value="0">Semua Ujian</option>
            <?php mysqli_data_seek($q_sesi_filter, 0); ?>
            <?php while ($sf = mysqli_fetch_assoc($q_sesi_filter)): ?>
            <option value="<?= $sf['id_sesi']; ?>" <?= ($filter_sesi == $sf['id_sesi']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($sf['nama_ujian']) . ' - ' . ($sf['nama_kelas'] ?? '-') . ' (' . date('d M Y', strtotime($sf['tgl_mulai'])) . ')' ?>
            </option>
            <?php endwhile; ?>
        </select>
        <select name="filter_kelas" class="form-select form-select-sm" style="max-width:180px;" onchange="this.form.submit()">
            <option value="0">Semua Kelas</option>
            <?php while ($kf = mysqli_fetch_assoc($q_kelas_filter)): ?>
            <option value="<?= $kf['id_kelas']; ?>" <?= ($filter_kelas == $kf['id_kelas']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($kf['nama_kelas']); ?>
            </option>
            <?php endwhile; ?>
        </select>
        <?php if ($filter_sesi > 0 || $filter_kelas > 0): ?>
        <a href="?page=nilai_siswa" class="btn btn-outline-danger btn-sm rounded-pill px-3">
            <i class="bi bi-x-lg me-1"></i> Reset
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabel Nilai -->
<div class="card card-custom overflow-hidden">
    <div class="card-header bg-white border-bottom-0 pt-3 px-4">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-table me-2"></i>Data Nilai</h6>
            <span class="badge bg-light text-muted border px-3 py-2" style="font-size:0.78rem;"><?= $jumlah_data; ?> data</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="text-center" width="5%">No</th>
                    <th width="22%">Nama Siswa</th>
                    <th class="text-center d-none d-md-table-cell" width="8%">NISN</th>
                    <th class="text-center" width="8%">Kelas</th>
                    <th width="18%">Ujian</th>
                    <th class="text-center d-none d-md-table-cell" width="10%">Tanggal</th>
                    <th class="text-center" width="8%">Nilai</th>
                    <th class="text-center" width="7%">Grade</th>
                    <th class="text-center d-none d-lg-table-cell" width="8%">Status</th>
                    <th class="text-center" width="6%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($jumlah_data > 0):
                    $n = $halaman_awal + 1;
                    while ($v = mysqli_fetch_assoc($q_nilai)):
                        // Warna grade
                        $grade_colors = [
                            'A' => ['bg-success', '#16a34a'],
                            'B' => ['bg-primary', '#2563eb'],
                            'C' => ['bg-warning', '#d97706'],
                            'D' => ['bg-orange', '#ea580c'],
                            'E' => ['bg-danger', '#dc2626'],
                        ];
                        $gc = $grade_colors[$v['grade']] ?? ['bg-secondary', '#64748b'];
                        $nilai_color = ($v['nilai'] >= 70) ? 'text-success' : 'text-danger';
                ?>
                <tr>
                    <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                    <td>
                        <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($v['nama_siswa']); ?></div>
                    </td>
                    <td class="text-center d-none d-md-table-cell">
                        <small class="text-muted"><?= htmlspecialchars($v['nisn']); ?></small>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-info bg-opacity-10 text-info" style="font-size:0.72rem;"><?= $v['nama_kelas']; ?></span>
                    </td>
                    <td>
                        <div class="text-dark" style="font-size:0.82rem;"><?= htmlspecialchars($v['nama_ujian']); ?></div>
                        <small class="text-muted"><?= strtoupper($v['jenis_ujian']); ?></small>
                    </td>
                    <td class="text-center d-none d-md-table-cell">
                        <small class="text-muted"><?= date('d M Y', strtotime($v['tgl_mulai'])); ?></small>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold <?= $nilai_color; ?>" style="font-size:1.1rem;"><?= $v['nilai']; ?></span>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $gc[0]; ?> px-2 py-1 rounded-pill fw-bold" style="font-size:0.8rem;">
                            <?= $v['grade']; ?>
                        </span>
                    </td>
                    <td class="text-center d-none d-lg-table-cell">
                        <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill" style="font-size:0.72rem;">
                            <i class="bi bi-check-circle me-1"></i>Selesai
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="?page=nilai_siswa&hapus_nilai=<?= $v['id_ujian']; ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Hapus nilai ini? Data ujian siswa akan direset.')" title="Reset Nilai">
                            <i class="bi bi-trash3"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
                        <i class="bi bi-bar-chart fs-1 d-block mb-2 opacity-25"></i>
                        <strong>Belum ada data nilai</strong><br>
                        <small>Data akan muncul setelah siswa menyelesaikan ujian untuk mapel Anda.</small>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total_halaman > 1): ?>
    <div class="card-footer bg-white border-0 py-3">
        <nav>
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill px-3 me-2" href="?page=nilai_siswa&filter_sesi=<?= $filter_sesi; ?>&filter_kelas=<?= $filter_kelas; ?>&pagi=<?= $sebelum; ?>"><i class="bi bi-chevron-left"></i></a>
                </li>
                <?php 
                $start_page = max(1, $halaman - 2);
                $end_page = min($total_halaman, $halaman + 2);
                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link rounded-pill mx-1" href="?page=nilai_siswa&filter_sesi='.$filter_sesi.'&filter_kelas='.$filter_kelas.'&pagi=1">1</a></li>';
                    if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                for ($x = $start_page; $x <= $end_page; $x++):
                ?>
                    <li class="page-item <?= ($halaman == $x) ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill mx-1" href="?page=nilai_siswa&filter_sesi=<?= $filter_sesi; ?>&filter_kelas=<?= $filter_kelas; ?>&pagi=<?= $x; ?>"><?= $x; ?></a>
                    </li>
                <?php endfor; 
                if ($end_page < $total_halaman) {
                    if ($end_page < $total_halaman - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    echo '<li class="page-item"><a class="page-link rounded-pill mx-1" href="?page=nilai_siswa&filter_sesi='.$filter_sesi.'&filter_kelas='.$filter_kelas.'&pagi='.$total_halaman.'">'.$total_halaman.'</a></li>';
                }
                ?>
                <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill px-3 ms-2" href="?page=nilai_siswa&filter_sesi=<?= $filter_sesi; ?>&filter_kelas=<?= $filter_kelas; ?>&pagi=<?= $sesudah; ?>"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
        <div class="text-center mt-2"><small class="text-muted">Halaman <b><?= $halaman; ?></b> dari <b><?= $total_halaman; ?></b> (<?= $jumlah_data; ?> data)</small></div>
    </div>
    <?php endif; ?>
</div>

<!-- Ringkasan Per Ujian -->
<?php if (mysqli_num_rows($q_per_ujian) > 0): ?>
<div class="card card-custom mt-4">
    <div class="card-header bg-white border-bottom px-4 py-3">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-table me-2"></i>Ringkasan Per Ujian</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="px-3 small text-muted">Ujian</th>
                    <th class="text-center small text-muted d-none d-md-table-cell">Kelas</th>
                    <th class="text-center small text-muted d-none d-lg-table-cell">Tanggal</th>
                    <th class="text-center small text-muted">Peserta</th>
                    <th class="text-center small text-muted">Rata-rata</th>
                    <th class="text-center small text-muted d-none d-md-table-cell">Tertinggi</th>
                    <th class="text-center small text-muted d-none d-md-table-cell">Terendah</th>
                    <th class="text-center small text-muted">Lulus</th>
                    <th class="text-center small text-muted d-none d-lg-table-cell">Tidak Lulus</th>
                    <th class="text-center small text-muted">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($pu = mysqli_fetch_assoc($q_per_ujian)):
                    $pct_lulus = $pu['peserta'] > 0 ? round(($pu['lulus'] / $pu['peserta']) * 100) : 0;
                ?>
                <tr>
                    <td class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($pu['nama_ujian']); ?></td>
                    <td class="text-center d-none d-md-table-cell"><span class="badge bg-light text-dark border"><?= $pu['nama_kelas'] ?? '-'; ?></span></td>
                    <td class="text-center small text-muted d-none d-lg-table-cell"><?= date('d M Y', strtotime($pu['tgl_mulai'])); ?></td>
                    <td class="text-center fw-bold"><?= $pu['peserta']; ?></td>
                    <td class="text-center">
                        <span class="fw-bold" style="color:<?= ($pu['rata'] >= 70) ? '#16a34a' : '#dc2626'; ?>; font-size:1rem;"><?= $pu['rata']; ?></span>
                    </td>
                    <td class="text-center fw-bold text-success d-none d-md-table-cell"><?= $pu['tertinggi']; ?></td>
                    <td class="text-center fw-bold text-danger d-none d-md-table-cell"><?= $pu['terendah']; ?></td>
                    <td class="text-center">
                        <span class="fw-bold text-success"><?= $pu['lulus']; ?></span>
                        <small class="text-muted d-none d-md-inline">(<?= $pct_lulus; ?>%)</small>
                    </td>
                    <td class="text-center d-none d-lg-table-cell">
                        <span class="fw-bold text-danger"><?= $pu['tidak_lulus']; ?></span>
                    </td>
                    <td class="text-center">
                        <a href="?page=nilai_siswa&filter_sesi=<?= $pu['id_sesi']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2" title="Lihat Detail">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="?page=nilai_siswa&export_csv=<?= $pu['id_sesi']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-2" title="Export CSV">
                            <i class="bi bi-download"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
