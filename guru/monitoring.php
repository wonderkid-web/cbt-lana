<?php
// === MONITORING UJIAN GURU ===
// File ini di-include oleh dashboard.php (page=monitoring)
// Guru memantau ujian yang sedang berlangsung untuk mapel yang diampunya

date_default_timezone_set('Asia/Jakarta');

// Helper: truncate teks panjang
function truncateKelasGuru($text, $max = 25) {
    if (strlen($text) <= $max) return $text;
    return substr($text, 0, $max) . '...';
}

 $id_user = (int)$_SESSION['id_user'];

// Ambil data guru untuk dapatkan id_mapel
 $q_guru_mapel = mysqli_query($conn, "SELECT guru.id_mapel, mapel.nama_mapel 
                                      FROM guru 
                                      LEFT JOIN mapel ON guru.id_mapel = mapel.id_mapel 
                                      WHERE guru.id_user = '$id_user'");
 $guru_mapel = mysqli_fetch_assoc($q_guru_mapel);
 $id_mapel_guru = (int)($guru_mapel['id_mapel'] ?? 0);

// --- PROSES: SELESAIKAN UJIAN SECARA PAKSA dari guru ---
if (isset($_GET['force_selesai']) && isset($_GET['id_ujian'])) {
    $id_ujian = (int)$_GET['id_ujian'];
    $id_sesi_fs = (int)$_GET['force_selesai'];
    
    $cek = mysqli_query($conn, "SELECT su.id_sesi FROM sesi_ujian su 
                                 JOIN ujian u ON u.id_sesi = su.id_sesi 
                                 WHERE u.id_ujian = '$id_ujian' AND su.id_mapel = '$id_mapel_guru'");
    if (mysqli_num_rows($cek) > 0) {
        $q_ujian = mysqli_query($conn, "SELECT * FROM ujian WHERE id_ujian = '$id_ujian'");
        $ujian = mysqli_fetch_assoc($q_ujian);
        
        if ($ujian && $ujian['status'] == 'sedang_dikerjakan') {
            $q_jawaban = mysqli_query($conn, "SELECT js.id_soal, js.jawaban, bs.kunci_jawaban
                                               FROM jawaban_siswa js
                                               JOIN bank_soal bs ON js.id_soal = bs.id_soal
                                               WHERE js.id_ujian = '$id_ujian'");
            
            $benar = 0;
            $total_dijawab = 0;
            while ($j = mysqli_fetch_assoc($q_jawaban)) {
                $total_dijawab++;
                if (strtoupper($j['jawaban']) == strtoupper($j['kunci_jawaban'])) {
                    $benar++;
                }
            }
            
            $id_mapel_sesi = 0;
            $q_mapel_sesi = mysqli_query($conn, "SELECT id_mapel FROM sesi_ujian WHERE id_sesi = '$id_sesi_fs'");
            if ($ms = mysqli_fetch_assoc($q_mapel_sesi)) {
                $id_mapel_sesi = (int)$ms['id_mapel'];
            }
            
            $q_total_soal = mysqli_query($conn, "SELECT COUNT(*) as total FROM bank_soal WHERE id_mapel = '$id_mapel_sesi' AND status = 'aktif'");
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

// Ambil sesi ujian yang AKTIF untuk mapel guru ini
 $q_sesi_aktif = mysqli_query($conn, "SELECT su.*,
                                       (SELECT GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ', ') 
                                        FROM sesi_ujian_kelas suk JOIN kelas k ON suk.id_kelas = k.id_kelas 
                                        WHERE suk.id_sesi = su.id_sesi) as nama_kelas
                                       FROM sesi_ujian su
                                       WHERE su.id_mapel = '$id_mapel_guru' AND su.status = 'aktif'
                                       ORDER BY su.tgl_mulai DESC");

// ============================================================
// UJIAN SELESAI — PAGINATION
// ============================================================
 $batas_selesai_g = 5;
 $hal_selesai_g = isset($_GET['hal_selesai']) ? (int)$_GET['hal_selesai'] : 1;
 $hal_selesai_g = max(1, $hal_selesai_g);
 $awal_selesai_g = ($hal_selesai_g - 1) * $batas_selesai_g;

 $total_data_selesai_g = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian WHERE id_mapel = '$id_mapel_guru' AND status = 'nonaktif'");
 $jml_data_selesai_g = mysqli_fetch_assoc($total_data_selesai_g)['total'];
 $total_hal_selesai_g = ceil($jml_data_selesai_g / $batas_selesai_g);

// Jika ada parameter detail, tampilkan detail per sesi
 $show_detail = false;
 $detail_sesi = null;

if (isset($_GET['detail']) && is_numeric($_GET['detail'])) {
    $id_sesi_detail = (int)$_GET['detail'];
    
    $q_detail = mysqli_query($conn, "SELECT su.*,
                                      (SELECT GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ', ') 
                                       FROM sesi_ujian_kelas suk JOIN kelas k ON suk.id_kelas = k.id_kelas 
                                       WHERE suk.id_sesi = su.id_sesi) as nama_kelas
                                      FROM sesi_ujian su
                                      WHERE su.id_sesi = '$id_sesi_detail' AND su.id_mapel = '$id_mapel_guru'");
    $detail_sesi = mysqli_fetch_assoc($q_detail);
    
    if ($detail_sesi) {
        $show_detail = true;
    }
}
?>

<!-- Inline style untuk kelas badge (karena file ini di-include) -->
<style>
.kelas-badge-wrap-guru {
    max-width: 200px;
    white-space: normal !important;
    word-wrap: break-word;
    overflow-wrap: break-word;
    line-height: 1.4;
    display: inline-block;
}
@media (max-width: 768px) {
    .kelas-badge-wrap-guru { max-width: 140px; }
}
</style>

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
<?php
 $peserta_list = mysqli_query($conn, "SELECT s.id_user, s.nama_siswa, s.nisn, k.nama_kelas as nama_kelas_siswa,
                                           u.id_ujian, u.waktu_mulai, u.waktu_selesai, u.nilai, u.status as status_ujian,
                                           (SELECT COUNT(*) FROM jawaban_siswa js WHERE js.id_ujian = u.id_ujian) as dijawab
                                    FROM siswa s
                                    JOIN kelas k ON s.id_kelas = k.id_kelas
                                    LEFT JOIN ujian u ON s.id_user = u.id_user AND u.id_sesi = '$id_sesi_detail'
                                    WHERE s.id_kelas IN (SELECT id_kelas FROM sesi_ujian_kelas WHERE id_sesi = '$id_sesi_detail')
                                    ORDER BY k.nama_kelas ASC, s.nama_siswa ASC");

  $tot_siswa = 0; $tot_selesai = 0; $tot_kerja = 0; $tot_belum = 0; $tot_tidak_ikut = 0;
 $all_peserta = [];
 $sesi_waktu_habis = false;
 if ($detail_sesi) {
     $sesi_end_ts = strtotime($detail_sesi['tgl_mulai']) + ((int)$detail_sesi['durasi'] * 60);
     if (time() > $sesi_end_ts) $sesi_waktu_habis = true;
 }
if ($peserta_list) {
    while ($p = mysqli_fetch_assoc($peserta_list)) {
        $all_peserta[] = $p;
        $tot_siswa++;
        if ($p['status_ujian'] == 'selesai') $tot_selesai++;
        elseif ($p['status_ujian'] == 'sedang_dikerjakan') $tot_kerja++;
        elseif (!$p['status_ujian'] && $sesi_waktu_habis) $tot_tidak_ikut++;
        else $tot_belum++;
    }
}

// Pagination detail peserta
 $batas_detail = 5;
 $hal_detail = isset($_GET['hal_detail']) ? (int)$_GET['hal_detail'] : 1;
 $hal_detail = max(1, $hal_detail);
 $total_hal_detail = ceil($tot_siswa / $batas_detail);
 $offset_detail = ($hal_detail - 1) * $batas_detail;
 $peserta_page = array_slice($all_peserta, $offset_detail, $batas_detail);
?>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="?page=monitoring" class="btn btn-light btn-sm rounded-pill px-3 shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
    <div>
        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($detail_sesi['nama_ujian']); ?></h6>
        <small class="text-muted">
            <span title="<?= htmlspecialchars($detail_sesi['nama_kelas']); ?>">Kelas: <strong><?= truncateKelasGuru($detail_sesi['nama_kelas'], 50); ?></strong></span> &bull;
            Token: <code><?= $detail_sesi['token']; ?></code> &bull;
            Durasi: <?= $detail_sesi['durasi']; ?> menit
        </small>
    </div>
</div>

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
                    <div class="stat-label">SEDANG DIKERJAKAN</div>
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
                <?php if ($sesi_waktu_habis && $tot_tidak_ikut > 0): ?>
                <div class="col-6 col-lg-3">
                    <div class="stat-card" style="border-left: 4px solid #7c3aed;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-label">TIDAK MENGIKUTI</div>
                                <div class="stat-value mt-1" style="color:#7c3aed;"><?= $tot_tidak_ikut; ?></div>
                            </div>
                            <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="bi bi-slash-circle"></i></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
</div>

<!-- Progress Bar Keseluruhan -->
<?php
 $pct_selesai = $tot_siswa > 0 ? round(($tot_selesai / $tot_siswa) * 100) : 0;
 $pct_kerja = $tot_siswa > 0 ? round(($tot_kerja / $tot_siswa) * 100) : 0;
?>
<div class="card card-custom p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
        <span class="small fw-bold text-muted">PROGRES KESELURUHAN</span>
        <span class="fw-bold text-dark"><?= $tot_selesai; ?> / <?= $tot_siswa; ?> selesai (<?= $pct_selesai; ?>%)</span>
    </div>
    <div class="progress" style="height: 12px; border-radius: 8px;">
        <div class="progress-bar bg-success" style="width: <?= $pct_selesai; ?>%; border-radius: 8px 0 0 8px;"></div>
        <div class="progress-bar bg-primary" style="width: <?= $pct_kerja; ?>%;"></div>
    </div>
    <div class="d-flex gap-4 mt-2">
        <small><span class="d-inline-block rounded" style="width:10px;height:10px;background:#16a34a;"></span> Selesai</small>
        <small><span class="d-inline-block rounded" style="width:10px;height:10px;background:#3b82f6;"></span> Mengerjakan</small>
        <small><span class="d-inline-block rounded" style="width:10px;height:10px;background:#e2e8f0;"></span> Belum Mulai</small>
    </div>
</div>

<!-- Tabel Peserta -->
<div class="card card-custom overflow-hidden">
    <div class="card-header bg-white border-bottom px-4 py-3">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-people me-2"></i>Daftar Peserta</h6>
        <small class="text-muted" title="<?= htmlspecialchars($detail_sesi['nama_kelas']); ?>"><?= truncateKelasGuru($detail_sesi['nama_kelas'], 60); ?></small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="text-center" width="5%">No</th>
                    <th width="20%">Nama Siswa</th>
                    <th class="text-center" width="12%">Kelas</th>
                    <th class="text-center d-none d-md-table-cell" width="10%">NISN</th>
                    <th class="text-center d-none d-lg-table-cell" width="13%">Waktu Mulai</th>
                    <th class="text-center" width="10%">Dijawab</th>
                    <th class="text-center" width="8%">Nilai</th>
                    <th class="text-center" width="12%">Status</th>
                    <th class="text-center" width="10%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tot_siswa > 0):
                    $n = $offset_detail + 1;
                    foreach ($peserta_page as $p):
                        $dijawab = $p['dijawab'] ?? 0;
                        
                        if ($p['status_ujian'] == 'selesai') {
                            $badge = '<span class="badge bg-success px-3 py-2 rounded-pill" style="font-size:0.75rem;"><i class="bi bi-check-circle me-1"></i>Selesai</span>';
                            $nilai_color = ($p['nilai'] >= 70) ? 'text-success' : 'text-danger';
                        } elseif ($p['status_ujian'] == 'sedang_dikerjakan') {
                            $badge = '<span class="badge bg-primary px-3 py-2 rounded-pill" style="font-size:0.75rem;"><i class="bi bi-pencil me-1"></i>Mengerjakan</span>';
                            $nilai_color = 'text-muted';
                        } elseif ($p['status_ujian'] == 'gagal') {
                            $badge = '<span class="badge bg-danger px-3 py-2 rounded-pill" style="font-size:0.75rem;"><i class="bi bi-x-circle me-1"></i>Gagal</span>';
                            $nilai_color = 'text-danger';
                                                } else {
                            if ($sesi_waktu_habis) {
                                $badge = '<span class="badge bg-secondary bg-opacity-75 text-white px-3 py-2 rounded-pill" style="font-size:0.75rem;"><i class="bi bi-slash-circle me-1"></i>Tidak Mengikuti</span>';
                            } else {
                                $badge = '<span class="badge bg-light text-muted border px-3 py-2 rounded-pill" style="font-size:0.75rem;"><i class="bi bi-clock me-1"></i>Belum Mulai</span>';
                            }
                            $nilai_color = 'text-muted';
                        }

                        $waktu_mulai_text = '-';
                        $durasi_text = '';
                        if ($p['waktu_mulai']) {
                            $waktu_mulai_text = date('d M Y, H:i', strtotime($p['waktu_mulai']));
                        }

                        if ($p['status_ujian'] == 'sedang_dikerjakan' && $p['waktu_mulai']) {
                            $waktu_mulai_ts = strtotime($p['waktu_mulai']);
                            $durasi_sesi = (int)$detail_sesi['durasi'] * 60;
                            $batas_waktu = $waktu_mulai_ts + $durasi_sesi;
                            $sisa = $batas_waktu - time();
                            if ($sisa > 0) {
                                $menit_sisa = floor($sisa / 60);
                                $detik_sisa = $sisa % 60;
                                $durasi_text = '<div class="mt-1"><span class="badge bg-danger bg-opacity-10 text-danger fw-bold rounded-pill px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-hourglass-split me-1"></i>' . $menit_sisa . ':' . str_pad($detik_sisa, 2, '0', STR_PAD_LEFT) . '</span></div>';
                            } else {
                                $durasi_text = '<div class="mt-1"><span class="badge bg-danger text-white rounded-pill px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-exclamation-triangle me-1"></i>Habis!</span></div>';
                            }
                        } elseif ($p['status_ujian'] == 'selesai' && $p['waktu_mulai'] && $p['waktu_selesai']) {
                            $mulai = strtotime($p['waktu_mulai']);
                            $selesai_ts = strtotime($p['waktu_selesai']);
                            $diff = $selesai_ts - $mulai;
                            $menit = floor($diff / 60);
                            $durasi_text = '<div class="mt-1"><span class="text-muted" style="font-size:0.75rem;"><i class="bi bi-stopwatch me-1"></i>' . $menit . ' mnt</span></div>';
                        }
                ?>
                <tr class="<?= ($p['status_ujian'] == 'sedang_dikerjakan') ? 'table-primary' : ''; ?>">
                    <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                    <td>
                        <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($p['nama_siswa']); ?></div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border" style="font-size:0.72rem;"><?= htmlspecialchars($p['nama_kelas_siswa'] ?? '-'); ?></span>
                    </td>
                    <td class="text-center d-none d-md-table-cell">
                        <small class="text-muted"><?= htmlspecialchars($p['nisn'] ?? '-'); ?></small>
                    </td>
                    <td class="text-center d-none d-lg-table-cell">
                        <small class="text-muted"><?= $waktu_mulai_text; ?></small>
                        <?= $durasi_text; ?>
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
                        <?php elseif ($p['status_ujian'] == 'gagal'): ?>
                            <span class="fw-bold text-danger" style="font-size:1.1rem;">0</span>
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
                        <?php elseif ($p['status_ujian'] == 'gagal'): ?>
                            <span class="text-danger"><i class="bi bi-x-circle fs-5"></i></span>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                        <strong>Tidak ada peserta</strong><br>
                        <small>Belum ada siswa terdaftar di kelas-kelas ini.</small>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
                </div>
        </div>

        <?php if ($total_hal_detail > 1): ?>
        <div class="card card-custom p-3 mt-3">
            <nav aria-label="Pagination peserta">
                <ul class="pagination pagination-sm justify-content-center mb-2">
                    <?php if ($hal_detail > 1): ?>
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3 me-2" href="?page=monitoring&detail=<?= $id_sesi_detail; ?>&hal_detail=<?= $hal_detail - 1; ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    <?php endif; ?>

                    <?php
                    $start_pg = max(1, $hal_detail - 2);
                    $end_pg = min($total_hal_detail, $hal_detail + 2);
                    if ($start_pg > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    for ($pg = $start_pg; $pg <= $end_pg; $pg++):
                    ?>
                    <li class="page-item <?= ($hal_detail == $pg) ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill mx-1" href="?page=monitoring&detail=<?= $id_sesi_detail; ?>&hal_detail=<?= $pg; ?>"><?= $pg; ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($end_pg < $total_hal_detail) echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; ?>

                    <?php if ($hal_detail < $total_hal_detail): ?>
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3 ms-2" href="?page=monitoring&detail=<?= $id_sesi_detail; ?>&hal_detail=<?= $hal_detail + 1; ?>"><i class="bi bi-chevron-right"></i></a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="text-center">
                <small class="text-muted">Menampilkan <?= $offset_detail + 1; ?> - <?= min($offset_detail + $batas_detail, $tot_siswa); ?> dari <b><?= $tot_siswa; ?></b> peserta</small>
            </div>
        </div>
        <?php endif; ?>

<?php else: ?>
<!-- ==================== MODE LIST: SESI UJIAN AKTIF ==================== -->

<?php
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
                    <div class="stat-label">SEDANG DIKERJAKAN</div>
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
    $id_s = (int)$sesi['id_sesi'];
    $q_stat = mysqli_query($conn, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN u.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN u.status = 'sedang_dikerjakan' THEN 1 ELSE 0 END) as kerja,
        SUM(CASE WHEN u.status = 'gagal' THEN 1 ELSE 0 END) as gagal,
        ROUND(AVG(CASE WHEN u.status = 'selesai' THEN u.nilai END), 1) as rata
    FROM ujian u WHERE u.id_sesi = '$id_s'");
    $stat = mysqli_fetch_assoc($q_stat);

    $q_tot_siswa = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa WHERE id_kelas IN (SELECT id_kelas FROM sesi_ujian_kelas WHERE id_sesi = '$id_s')");
    $tot_siswa = mysqli_fetch_assoc($q_tot_siswa)['total'];
    
    $now = time();
    $tgl_mulai_ts = strtotime($sesi['tgl_mulai']);
    $durasi_detik = (int)$sesi['durasi'] * 60;
    
    if ($now >= $tgl_mulai_ts && $now <= $tgl_mulai_ts + $durasi_detik) {
        $time_status_class = 'text-success';
        $time_status_text = 'Berlangsung';
        $time_status_icon = 'pulse-dot';
    } elseif ($tgl_mulai_ts > $now) {
        $time_status_class = 'text-warning';
        $time_status_text = 'Belum Dimulai';
        $time_status_icon = '';
    } else {
        $time_status_class = 'text-muted';
        $time_status_text = 'Waktu Habis';
        $time_status_icon = '';
    }
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
                        <small class="text-muted d-flex align-items-center flex-wrap gap-1">
                            <span class="badge bg-light text-dark border kelas-badge-wrap-guru" style="font-size:0.7rem;" title="<?= htmlspecialchars($sesi['nama_kelas'] ?? '-'); ?>"><?= truncateKelasGuru($sesi['nama_kelas'] ?? '-', 25); ?></span>
                            <span class="<?= $time_status_class; ?>" style="font-size:0.75rem;"><i class="bi bi-circle-fill me-1 <?= $time_status_icon; ?>" style="font-size:0.5rem;"></i><?= $time_status_text; ?></span>
                        </small>
                    </div>
                </div>
                <div class="d-flex gap-3 small text-muted mt-2 flex-wrap">
                    <span><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($sesi['tgl_mulai'])); ?></span>
                    <span><i class="bi bi-hourglass-split me-1"></i><?= $sesi['durasi']; ?> mnt</span>
                    <span><i class="bi bi-key me-1"></i><code><?= $sesi['token']; ?></code></span>
                </div>
            </div>
            <div class="col-lg-4 mb-3 mb-lg-0">
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Progress</small>
                        <small class="fw-bold text-dark"><?= ($stat['selesai'] ?? 0) + ($stat['kerja'] ?? 0); ?> / <?= $tot_siswa; ?></small>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 6px;">
                        <div class="progress-bar bg-success" style="width: <?= $tot_siswa > 0 ? round((($stat['selesai'] ?? 0) / $tot_siswa) * 100) : 0; ?>%;"></div>
                        <div class="progress-bar bg-primary" style="width: <?= $tot_siswa > 0 ? round((($stat['kerja'] ?? 0) / $tot_siswa) * 100) : 0; ?>%;"></div>
                    </div>
                </div>
                <div class="d-flex gap-3 small flex-wrap">
                    <span class="text-success"><i class="bi bi-check-circle me-1"></i><?= $stat['selesai'] ?? 0; ?> selesai</span>
                    <span class="text-primary"><i class="bi bi-pencil me-1"></i><?= $stat['kerja'] ?? 0; ?> kerja</span>
                    <span class="text-danger"><i class="bi bi-x-circle me-1"></i><?= $stat['gagal'] ?? 0; ?> gagal</span>
                </div>
            </div>
            <div class="col-lg-3 text-lg-end">
                <div class="mb-2">
                    <span class="small text-muted">Rata-rata</span>
                    <div class="fw-bold fs-4" style="color:<?= (($stat['rata'] ?? 0) >= 70) ? '#16a34a' : (($stat['rata'] ?? 0) > 0 ? '#dc2626' : '#94a3b8'); ?>;">
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
        <a href="?page=nilai_siswa" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
            <i class="bi bi-calendar-check me-1"></i> Lihat Nilai
        </a>
    </div>
</div>
<?php endif; ?>

<!-- ========== UJIAN SELESAI + PAGINATION ========== -->
<?php
 $q_selesai = mysqli_query($conn, "SELECT su.*,
                                         (SELECT GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ', ') 
                                          FROM sesi_ujian_kelas suk JOIN kelas k ON suk.id_kelas = k.id_kelas 
                                          WHERE suk.id_sesi = su.id_sesi) as nama_kelas,
                                         (SELECT COUNT(*) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'selesai') as peserta,
                                         (SELECT ROUND(AVG(u.nilai),1) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'selesai') as rata
                                  FROM sesi_ujian su
                                  WHERE su.id_mapel = '$id_mapel_guru' AND su.status = 'nonaktif'
                                  ORDER BY su.tgl_mulai DESC 
                                  LIMIT $awal_selesai_g, $batas_selesai_g");
if (mysqli_num_rows($q_selesai) > 0):
?>
<div class="card card-custom mt-4">
    <div class="card-header bg-white border-bottom px-4 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-archive me-2"></i>Ujian Selesai</h6>
            <a href="?page=nilai_siswa" class="text-primary text-decoration-none" style="font-size:0.82rem;">Lihat Nilai <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="px-3 small text-muted">Ujian</th>
                    <th class="text-center small text-muted" style="min-width:130px;max-width:200px;">Kelas</th>
                    <th class="text-center small text-muted d-none d-md-table-cell">Tanggal</th>
                    <th class="text-center small text-muted">Peserta</th>
                    <th class="text-center small text-muted">Rata-rata</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($sq = mysqli_fetch_assoc($q_selesai)): ?>
                <tr>
                    <td class="fw-bold text-dark" style="font-size:0.85rem;max-width:180px;"><?= htmlspecialchars($sq['nama_ujian']); ?></td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border kelas-badge-wrap-guru" style="font-size:0.72rem;" title="<?= htmlspecialchars($sq['nama_kelas'] ?? '-'); ?>">
                            <?= truncateKelasGuru($sq['nama_kelas'] ?? '-', 25); ?>
                        </span>
                    </td>
                    <td class="text-center small text-muted d-none d-md-table-cell"><?= date('d M Y', strtotime($sq['tgl_mulai'])); ?></td>
                    <td class="text-center fw-bold"><?= $sq['peserta']; ?></td>
                    <td class="text-center fw-bold" style="color:<?= (($sq['rata'] ?? 0) >= 70) ? '#16a34a' : '#dc2626'; ?>;"><?= $sq['rata'] ?: '-'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <!-- Pagination -->
    <?php if ($total_hal_selesai_g > 1): ?>
    <div class="card-footer bg-white border-top-0 py-3">
        <nav aria-label="Pagination ujian selesai guru">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php if ($hal_selesai_g > 1): ?>
                <li class="page-item">
                    <a class="page-link rounded-pill px-3 me-2" href="?page=monitoring&hal_selesai=<?= $hal_selesai_g - 1; ?>"><i class="bi bi-chevron-left"></i></a>
                </li>
                <?php endif; ?>

                <?php
                $start_pg = max(1, $hal_selesai_g - 2);
                $end_pg = min($total_hal_selesai_g, $hal_selesai_g + 2);
                if ($start_pg > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                for ($pg = $start_pg; $pg <= $end_pg; $pg++):
                ?>
                <li class="page-item <?= ($hal_selesai_g == $pg) ? 'active' : ''; ?>">
                    <a class="page-link rounded-pill mx-1" href="?page=monitoring&hal_selesai=<?= $pg; ?>"><?= $pg; ?></a>
                </li>
                <?php endfor; ?>
                <?php if ($end_pg < $total_hal_selesai_g) echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; ?>

                <?php if ($hal_selesai_g < $total_hal_selesai_g): ?>
                <li class="page-item">
                    <a class="page-link rounded-pill px-3 ms-2" href="?page=monitoring&hal_selesai=<?= $hal_selesai_g + 1; ?>"><i class="bi bi-chevron-right"></i></a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="text-center mt-2">
            <small class="text-muted">Total: <b><?= $jml_data_selesai_g; ?></b> ujian &mdash; Halaman <?= $hal_selesai_g; ?> / <?= $total_hal_selesai_g; ?></small>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Auto refresh -->
<script>
    <?php if (!$show_detail && ($total_aktif ?? 0) > 0): ?>
    setTimeout(function() { location.reload(); }, 30000);
    <?php endif; ?>
    <?php if ($show_detail && ($tot_kerja ?? 0) > 0): ?>
    setTimeout(function() { location.reload(); }, 15000);
    <?php endif; ?>
</script>