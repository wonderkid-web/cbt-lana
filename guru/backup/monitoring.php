<?php
// === MONITORING UJIAN GURU ===
// File ini di-include oleh dashboard.php (page=monitoring)
// Guru memantau ujian yang sedang berlangsung untuk mapel yang diampunya

date_default_timezone_set('Asia/Jakarta');

$id_user = (int)$_SESSION['id_user'];

// Ambil data guru untuk dapatkan id_mapel
$q_guru_mapel = mysqli_query($conn, "SELECT guru.id_mapel, mapel.nama_mapel 
                                      FROM guru 
                                      LEFT JOIN mapel ON guru.id_mapel = mapel.id_mapel 
                                      WHERE guru.id_user = '$id_user'");
$guru_mapel = mysqli_fetch_assoc($q_guru_mapel);
$id_mapel_guru = (int)($guru_mapel['id_mapel'] ?? 0);

// Ambil sesi ujian yang AKTIF untuk mapel guru ini
$q_sesi_aktif = mysqli_query($conn, "SELECT su.*, k.nama_kelas
                                       FROM sesi_ujian su
                                       LEFT JOIN kelas k ON su.id_kelas = k.id_kelas
                                       WHERE su.id_mapel = '$id_mapel_guru' AND su.status = 'aktif'
                                       ORDER BY su.tgl_mulai DESC");

// Jika ada parameter detail, tampilkan detail per sesi
$show_detail = false;
$detail_sesi = null;
$peserta_list = null;

if (isset($_GET['detail']) && is_numeric($_GET['detail'])) {
    $id_sesi_detail = (int)$_GET['detail'];
    
    // Verifikasi sesi milik mapel guru
    $q_detail = mysqli_query($conn, "SELECT su.*, k.nama_kelas
                                      FROM sesi_ujian su
                                      LEFT JOIN kelas k ON su.id_kelas = k.id_kelas
                                      WHERE su.id_sesi = '$id_sesi_detail' AND su.id_mapel = '$id_mapel_guru'");
    $detail_sesi = mysqli_fetch_assoc($q_detail);
    
    if ($detail_sesi) {
        $show_detail = true;
        
        // Ambil semua siswa di kelas tersebut + data ujian mereka
        $id_kelas = (int)$detail_sesi['id_kelas'];
        $peserta_list = mysqli_query($conn, "SELECT s.id_siswa, s.nis, s.nama_siswa,
                                                   u.id_ujian, u.waktu_mulai, u.waktu_selesai, u.nilai, u.status as status_ujian,
                                                   (SELECT COUNT(*) FROM jawaban_siswa js WHERE js.id_ujian = u.id_ujian) as dijawab
                                            FROM siswa s
                                            LEFT JOIN ujian u ON s.id_siswa = u.id_siswa AND u.id_sesi = '$id_sesi_detail'
                                            WHERE s.id_kelas = '$id_kelas'
                                            ORDER BY s.nama_siswa ASC");
    }
}

// --- PROSES: SELESAIKAN UJIAN SECARA PAKSAdari guru ---
if (isset($_GET['force_selesai']) && isset($_GET['id_ujian'])) {
    $id_ujian = (int)$_GET['id_ujian'];
    $id_sesi_fs = (int)$_GET['force_selesai'];
    
    // Verifikasi sesi milik mapel guru
    $cek = mysqli_query($conn, "SELECT su.id_sesi FROM sesi_ujian su 
                                 JOIN ujian u ON u.id_sesi = su.id_sesi 
                                 WHERE u.id_ujian = '$id_ujian' AND su.id_mapel = '$id_mapel_guru'");
    if (mysqli_num_rows($cek) > 0) {
        // Proses auto-submit: hitung nilai dari jawaban yang sudah ada
        $q_ujian = mysqli_query($conn, "SELECT * FROM ujian WHERE id_ujian = '$id_ujian'");
        $ujian = mysqli_fetch_assoc($q_ujian);
        
        if ($ujian && $ujian['status'] == 'sedang_dikerjakan') {
            // Ambil semua jawaban siswa
            $q_jawaban = mysqli_query($conn, "SELECT js.id_soal, js.jawaban_siswa, bs.kunci_jawaban
                                               FROM jawaban_siswa js
                                               JOIN bank_soal bs ON js.id_soal = bs.id_soal
                                               WHERE js.id_ujian = '$id_ujian'");
            
            $benar = 0;
            $total = 0;
            $jawaban = [];
            while ($j = mysqli_fetch_assoc($q_jawaban)) {
                $total++;
                if (strtoupper($j['jawaban_siswa']) == strtoupper($j['kunci_jawaban'])) {
                    $benar++;
                }
                $jawaban[$j['id_soal']] = $j['jawaban_siswa'];
            }
            
            // Ambil total soal untuk sesi ini
            $q_total_soal = mysqli_query($conn, "SELECT COUNT(*) as total FROM bank_soal WHERE id_mapel = '$id_mapel_guru' AND status = 'aktif'");
            $total_soal_all = mysqli_fetch_assoc($q_total_soal)['total'];
            
            if ($total_soal_all > 0) {
                $nilai = round(($benar / $total_soal_all) * 100, 1);
            } else {
                $nilai = 0;
            }
            
            $waktu_selesai = date('Y-m-d H:i:s');
            mysqli_query($conn, "UPDATE ujian SET nilai = '$nilai', status = 'selesai', waktu_selesai = '$waktu_selesai' WHERE id_ujian = '$id_ujian'");
        }
    }
    header("Location: ?page=monitoring&detail=$id_sesi_fs");
    exit;
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold text-dark mb-0">Monitoring Ujian</h5>
        <p class="text-muted small mb-0">Mapel: <strong><?= $guru_mapel['nama_mapel'] ?? '-'; ?></strong> &bull; Pantau ujian yang sedang berlangsung secara real-time</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm" onclick="location.reload()" title="Refresh">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
    </div>
</div>

<?php if ($show_detail && $detail_sesi): ?>
<!-- ==================== MODE DETAIL: LIHAT PESERTA ==================== -->
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="?page=monitoring" class="btn btn-light btn-sm rounded-pill px-3 shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
    <div>
        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($detail_sesi['nama_ujian']); ?></h6>
        <small class="text-muted">Kelas <?= $detail_sesi['nama_kelas']; ?> &bull; Token: <code><?= $detail_sesi['token']; ?></code> &bull; Durasi: <?= $detail_sesi['durasi']; ?> menit</small>
    </div>
</div>

<?php
// Hitung ringkasan peserta
$tot_siswa = 0; $tot_selesai = 0; $tot_kerja = 0; $tot_belum = 0;
$all_peserta = [];
if ($peserta_list) {
    while ($p = mysqli_fetch_assoc($peserta_list)) {
        $all_peserta[] = $p;
        $tot_siswa++;
        if ($p['status_ujian'] == 'selesai') $tot_selesai++;
        elseif ($p['status_ujian'] == 'sedang_dikerjakan') $tot_kerja++;
        else $tot_belum++;
    }
}
?>

<!-- Statistik Peserta -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">TOTAL SISWA</div>
                    <div class="stat-value text-dark mt-1"><?= $tot_siswa; ?></div>
                </div>
                <div class="stat-icon bg-light text-dark"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="border-left: 4px solid #16a34a;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">SELESAI</div>
                    <div class="stat-value text-success mt-1"><?= $tot_selesai; ?></div>
                </div>
                <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-check-circle-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="border-left: 4px solid #3b82f6;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">SEDERHANA DIKERJAKAN</div>
                    <div class="stat-value text-primary mt-1"><?= $tot_kerja; ?></div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-pencil-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="border-left: 4px solid #94a3b8;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">BELUM MULAI</div>
                    <div class="stat-value text-secondary mt-1"><?= $tot_belum; ?></div>
                </div>
                <div class="stat-icon bg-light text-secondary"><i class="bi bi-clock"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Bar Keseluruhan -->
<?php
$pct_selesai = $tot_siswa > 0 ? round(($tot_selesai / $tot_siswa) * 100) : 0;
$pct_kerja = $tot_siswa > 0 ? round(($tot_kerja / $tot_siswa) * 100) : 0;
?>
<div class="card card-custom p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="small fw-bold text-muted">PROGRES KESELURUHAN</span>
        <span class="fw-bold text-dark"><?= $tot_selesai; ?> / <?= $tot_siswa; ?> siswa selesai (<?= $pct_selesai; ?>%)</span>
    </div>
    <div class="progress" style="height: 12px; border-radius: 8px;">
        <div class="progress-bar bg-success" style="width: <?= $pct_selesai; ?>%; border-radius: 8px 0 0 8px;" role="progressbar"></div>
        <div class="progress-bar bg-primary" style="width: <?= $pct_kerja; ?>%;" role="progressbar"></div>
    </div>
    <div class="d-flex gap-4 mt-2">
        <small><span class="d-inline-block rounded" style="width:10px;height:10px;background:#16a34a;"></span> Selesai</small>
        <small><span class="d-inline-block rounded" style="width:10px;height:10px;background:#3b82f6;"></span> Sedang Mengerjakan</small>
        <small><span class="d-inline-block rounded" style="width:10px;height:10px;background:#e2e8f0;"></span> Belum Mulai</small>
    </div>
</div>

<!-- Tabel Peserta -->
<div class="card card-custom overflow-hidden">
    <div class="card-header bg-white border-bottom px-4 py-3">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-people me-2"></i>Daftar Peserta - Kelas <?= $detail_sesi['nama_kelas']; ?></h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="px-3 py-3 text-center" width="5%">No</th>
                    <th width="25%">Nama Siswa</th>
                    <th class="text-center" width="10%">NIS</th>
                    <th class="text-center d-none d-md-table-cell" width="15%">Waktu Mulai</th>
                    <th class="text-center" width="12%">Dijawab</th>
                    <th class="text-center" width="10%">Nilai</th>
                    <th class="text-center" width="12%">Status</th>
                    <th class="text-center" width="11%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tot_siswa > 0):
                    $n = 1;
                    foreach ($all_peserta as $p):
                        // Hitung berapa soal yang dijawab
                        $dijawab = $p['dijawab'] ?? 0;
                        
                        // Status badge
                        if ($p['status_ujian'] == 'selesai') {
                            $badge = '<span class="badge bg-success px-3 py-2 rounded-pill" style="font-size:0.75rem;"><i class="bi bi-check-circle me-1"></i>Selesai</span>';
                            $nilai_color = ($p['nilai'] >= 70) ? 'text-success' : 'text-danger';
                        } elseif ($p['status_ujian'] == 'sedang_dikerjakan') {
                            $badge = '<span class="badge bg-primary px-3 py-2 rounded-pill" style="font-size:0.75rem;"><i class="bi bi-pencil me-1"></i>Mengerjakan</span>';
                            $nilai_color = 'text-muted';
                        } else {
                            $badge = '<span class="badge bg-light text-muted border px-3 py-2 rounded-pill" style="font-size:0.75rem;"><i class="bi bi-clock me-1"></i>Belum Mulai</span>';
                            $nilai_color = 'text-muted';
                        }

                        // Waktu mulai
                        $waktu_mulai_text = '-';
                        if ($p['waktu_mulai']) {
                            $waktu_mulai_text = date('d M Y, H:i', strtotime($p['waktu_mulai']));
                        }

                        // Hitung durasi jika sedang mengerjakan
                        $durasi_text = '-';
                        if ($p['status_ujian'] == 'sedang_dikerjakan' && $p['waktu_mulai']) {
                            $waktu_mulai_ts = strtotime($p['waktu_mulai']);
                            $durasi_sesi = (int)$detail_sesi['durasi'] * 60;
                            $batas_waktu = $waktu_mulai_ts + $durasi_sesi;
                            $sisa = $batas_waktu - time();
                            if ($sisa > 0) {
                                $menit_sisa = floor($sisa / 60);
                                $detik_sisa = $sisa % 60;
                                $durasi_text = '<span class="text-danger fw-bold">' . $menit_sisa . ':' . str_pad($detik_sisa, 2, '0', STR_PAD_LEFT) . '</span>';
                            } else {
                                $durasi_text = '<span class="text-danger fw-bold">Waktu Habis!</span>';
                            }
                        } elseif ($p['status_ujian'] == 'selesai' && $p['waktu_mulai'] && $p['waktu_selesai']) {
                            $mulai = strtotime($p['waktu_mulai']);
                            $selesai_ts = strtotime($p['waktu_selesai']);
                            $diff = $selesai_ts - $mulai;
                            $menit = floor($diff / 60);
                            $durasi_text = $menit . ' menit';
                        }
                ?>
                <tr class="<?= ($p['status_ujian'] == 'sedang_dikerjakan') ? 'table-primary' : ''; ?>">
                    <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                    <td>
                        <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($p['nama_siswa']); ?></div>
                    </td>
                    <td class="text-center">
                        <small class="text-muted"><?= htmlspecialchars($p['nis']); ?></small>
                    </td>
                    <td class="text-center d-none d-md-table-cell">
                        <small class="text-muted"><?= $waktu_mulai_text; ?></small>
                        <?php if ($durasi_text != '-'): ?>
                            <div class="mt-1"><?= $durasi_text; ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($p['status_ujian']): ?>
                            <span class="fw-bold" style="font-size:0.85rem;"><?= $dijawab; ?></span>
                            <small class="text-muted"> soal</small>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($p['status_ujian'] == 'selesai'): ?>
                            <span class="fw-bold <?= $nilai_color; ?>" style="font-size:1.1rem;"><?= $p['nilai']; ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= $badge; ?></td>
                    <td class="text-center">
                        <?php if ($p['status_ujian'] == 'sedang_dikerjakan' && $p['id_ujian']): ?>
                            <a href="?page=monitoring&detail=<?= $detail_sesi['id_sesi']; ?>&force_selesai=<?= $detail_sesi['id_sesi']; ?>&id_ujian=<?= $p['id_ujian']; ?>" 
                               class="btn btn-sm btn-outline-danger rounded-pill px-2" 
                               onclick="return confirm('Selesaikan ujian secara paksa untuk siswa ini? Nilai akan dihitung dari jawaban yang sudah diisi.')" title="Force Selesai">
                                <i class="bi bi-stop-circle me-1"></i> Selesaikan
                            </a>
                        <?php elseif ($p['status_ujian'] == 'selesai'): ?>
                            <span class="text-success"><i class="bi bi-check-lg fs-5"></i></span>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                        <strong>Tidak ada siswa</strong><br>
                        <small>Belum ada siswa terdaftar di kelas ini.</small>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- ==================== MODE LIST: SESI UJIAN AKTIF ==================== -->

<?php
// Statistik ringkas monitoring
$q_aktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian WHERE id_mapel = '$id_mapel_guru' AND status = 'aktif'");
$total_aktif = mysqli_fetch_assoc($q_aktif)['total'];

$q_total_kerja = mysqli_query($conn, "SELECT COUNT(*) as total FROM ujian u 
                                        JOIN sesi_ujian su ON u.id_sesi = su.id_sesi 
                                        WHERE su.id_mapel = '$id_mapel_guru' AND u.status = 'sedang_dikerjakan'");
$total_sedang_kerja = mysqli_fetch_assoc($q_total_kerja)['total'];

$q_total_selesai_hari = mysqli_query($conn, "SELECT COUNT(*) as total FROM ujian u 
                                              JOIN sesi_ujian su ON u.id_sesi = su.id_sesi 
                                              WHERE su.id_mapel = '$id_mapel_guru' AND u.status = 'selesai' AND DATE(u.waktu_selesai) = CURDATE()");
$total_selesai_hari = mysqli_fetch_assoc($q_total_selesai_hari)['total'];
?>

<!-- Statistik Ringkas -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">UJIAN AKTIF</div>
                    <div class="stat-value text-success mt-1"><?= $total_aktif; ?></div>
                </div>
                <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-lightning-charge-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">SEDANG MENGEMBANGKAN</div>
                    <div class="stat-value text-primary mt-1"><?= $total_sedang_kerja; ?></div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-pencil-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">SELESAI HARI INI</div>
                    <div class="stat-value mt-1" style="color:#d97706;"><?= $total_selesai_hari; ?></div>
                </div>
                <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-check-circle-fill"></i></div>
            </div>
        </div>
    </div>
</div>

<?php if ($total_aktif > 0): ?>
<!-- Daftar Ujian Aktif -->
<?php while ($sesi = mysqli_fetch_assoc($q_sesi_aktif)):
    // Hitung statistik per sesi
    $id_s = (int)$sesi['id_sesi'];
    $q_stat = mysqli_query($conn, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN u.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN u.status = 'sedang_dikerjakan' THEN 1 ELSE 0 END) as kerja,
        SUM(CASE WHEN u.status = 'gagal' THEN 1 ELSE 0 END) as gagal,
        ROUND(AVG(CASE WHEN u.status = 'selesai' THEN u.nilai END), 1) as rata
    FROM ujian u WHERE u.id_sesi = '$id_s'");
    $stat = mysqli_fetch_assoc($q_stat);

    // Total siswa di kelas
    $q_tot_siswa = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa WHERE id_kelas = '" . (int)$sesi['id_kelas'] . "'");
    $tot_siswa = mysqli_fetch_assoc($q_tot_siswa)['total'];
    
    $pct = $tot_siswa > 0 ? round((($stat['selesai'] + $stat['kerja']) / $tot_siswa) * 100) : 0;
    
    // Cek apakah ujian sudah dimulai berdasarkan waktu
    $now = time();
    $tgl_mulai_ts = strtotime($sesi['tgl_mulai']);
    $durasi_detik = (int)$sesi['durasi'] * 60;
    $batas_akhir = $tgl_mulai_ts + $durasi_detik;
    $is_running = ($now >= $tgl_mulai_ts && $sesi['status'] == 'aktif');
    
    $time_status_class = ($is_running) ? 'text-success' : ((strtotime($sesi['tgl_mulai']) > $now) ? 'text-warning' : 'text-muted');
    $time_status_text = ($is_running) ? 'Berlangsung' : ((strtotime($sesi['tgl_mulai']) > $now) ? 'Belum Dimulai' : 'Selesai Waktu');
?>

<div class="card card-custom mb-3">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-3 mb-lg-0">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="stat-icon flex-shrink-0" style="background:#dcfce7;color:#16a34a;width:42px;height:42px;border-radius:12px;">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($sesi['nama_ujian']); ?></h6>
                        <small class="text-muted">
                            <span class="badge bg-info bg-opacity-10 text-info me-1" style="font-size:0.7rem;"><?= $sesi['nama_kelas'] ?? '-'; ?></span>
                            <span class="<?= $time_status_class; ?>" style="font-size:0.75rem;"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i><?= $time_status_text; ?></span>
                        </small>
                    </div>
                </div>
                <div class="d-flex gap-3 small text-muted mt-2">
                    <span><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($sesi['tgl_mulai'])); ?></span>
                    <span><i class="bi bi-hourglass-split me-1"></i><?= $sesi['durasi']; ?> menit</span>
                    <span><i class="bi bi-key me-1"></i><code><?= $sesi['token']; ?></code></span>
                </div>
            </div>
            <div class="col-lg-4 mb-3 mb-lg-0">
                <!-- Progress Bar -->
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Progress</small>
                        <small class="fw-bold text-dark"><?= $stat['selesai'] + $stat['kerja']; ?> / <?= $tot_siswa; ?></small>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 6px;">
                        <div class="progress-bar bg-success" style="width: <?= $tot_siswa > 0 ? round(($stat['selesai'] / $tot_siswa) * 100) : 0; ?>%;"></div>
                        <div class="progress-bar bg-primary" style="width: <?= $tot_siswa > 0 ? round(($stat['kerja'] / $tot_siswa) * 100) : 0; ?>%;"></div>
                    </div>
                </div>
                <div class="d-flex gap-3 small">
                    <span class="text-success"><i class="bi bi-check-circle me-1"></i><?= $stat['selesai']; ?> selesai</span>
                    <span class="text-primary"><i class="bi bi-pencil me-1"></i><?= $stat['kerja']; ?> kerja</span>
                    <span class="text-danger"><i class="bi bi-x-circle me-1"></i><?= $stat['gagal']; ?> gagal</span>
                </div>
            </div>
            <div class="col-lg-3 text-lg-end">
                <div class="mb-2">
                    <span class="small text-muted">Rata-rata</span>
                    <div class="fw-bold fs-4" style="color:<?= ($stat['rata'] >= 70) ? '#16a34a' : (($stat['rata'] > 0) ? '#dc2626' : '#94a3b8'); ?>;">
                        <?= $stat['rata'] ?: '-'; ?>
                    </div>
                </div>
                <a href="?page=monitoring&detail=<?= $sesi['id_sesi']; ?>" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm w-100">
                    <i class="bi bi-eye me-1"></i> Detail
                </a>
            </div>
        </div>
    </div>
</div>

<?php endwhile; ?>

<?php else: ?>
<!-- Tidak ada ujian aktif -->
<div class="card card-custom p-5">
    <div class="text-center text-muted">
        <i class="bi bi-emoji-neutral fs-1 d-block mb-3 opacity-25"></i>
        <h6 class="fw-bold text-dark">Tidak Ada Ujian Aktif</h6>
        <p class="small mb-3">Saat ini tidak ada sesi ujian yang sedang berlangsung untuk mapel Anda.</p>
        <a href="?page=jadwal_ujian" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
            <i class="bi bi-calendar-plus me-1"></i> Buat Jadwal Ujian
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Ujian yang sudah selesai (riwayat singkat) -->
<?php
$q_selesai = mysqli_query($conn, "SELECT su.*, k.nama_kelas,
                                         (SELECT COUNT(*) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'selesai') as peserta,
                                         (SELECT ROUND(AVG(u.nilai),1) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'selesai') as rata
                                  FROM sesi_ujian su
                                  LEFT JOIN kelas k ON su.id_kelas = k.id_kelas
                                  WHERE su.id_mapel = '$id_mapel_guru' AND su.status = 'nonaktif'
                                  ORDER BY su.tgl_mulai DESC LIMIT 5");
if (mysqli_num_rows($q_selesai) > 0):
?>
<div class="card card-custom mt-4">
    <div class="card-header bg-white border-bottom px-4 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-archive me-2"></i>Ujian Selesai (Terbaru)</h6>
            <a href="?page=nilai_siswa" class="text-primary text-decoration-none" style="font-size:0.82rem;">Lihat Nilai <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="px-3 small text-muted">Ujian</th>
                    <th class="text-center small text-muted">Kelas</th>
                    <th class="text-center small text-muted">Tanggal</th>
                    <th class="text-center small text-muted">Peserta</th>
                    <th class="text-center small text-muted">Rata-rata</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($sq = mysqli_fetch_assoc($q_selesai)): ?>
                <tr>
                    <td class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($sq['nama_ujian']); ?></td>
                    <td class="text-center"><span class="badge bg-light text-dark border"><?= $sq['nama_kelas'] ?? '-'; ?></span></td>
                    <td class="text-center small text-muted"><?= date('d M Y', strtotime($sq['tgl_mulai'])); ?></td>
                    <td class="text-center fw-bold"><?= $sq['peserta']; ?></td>
                    <td class="text-center fw-bold" style="color:<?= ($sq['rata'] >= 70) ? '#16a34a' : '#dc2626'; ?>;"><?= $sq['rata'] ?: '-'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Auto refresh setiap 30 detik jika ada ujian aktif -->
<script>
    <?php if (!$show_detail && $total_aktif > 0): ?>
    // Auto refresh untuk mode list
    setTimeout(function() {
        location.reload();
    }, 30000);
    <?php endif; ?>

    <?php if ($show_detail && $tot_kerja > 0): ?>
    // Auto refresh untuk mode detail jika ada siswa yang sedang mengerjakan
    setTimeout(function() {
        location.reload();
    }, 15000);
    <?php endif; ?>
</script>