<?php
// === JADWAL UJIAN GURU (READ-ONLY) ===
// File ini di-include oleh dashboard.php (page=jadwal_ujian)
// Guru hanya bisa MELIHAT jadwal ujian untuk mapel yang diampunya
// Pengelolaan jadwal (buat/edit/hapus) hanya bisa dilakukan oleh Admin

date_default_timezone_set('Asia/Jakarta');

$id_user = (int)$_SESSION['id_user'];

// Ambil data guru untuk dapatkan id_mapel
$q_guru_mapel = mysqli_query($conn, "SELECT guru.id_mapel, mapel.nama_mapel 
                                      FROM guru 
                                      LEFT JOIN mapel ON guru.id_mapel = mapel.id_mapel 
                                      WHERE guru.id_user = '$id_user'");
$guru_mapel = mysqli_fetch_assoc($q_guru_mapel);
$id_mapel_guru = (int)($guru_mapel['id_mapel'] ?? 0);

// --- PAGINATION ---
$batas = 10;
$halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;
$sebelum = $halaman - 1;
$sesudah = $halaman + 1;

// Filter status
$filter_status = isset($_GET['filter']) ? $_GET['filter'] : 'semua';

// Query: HANYA sesi ujian untuk mapel guru ini
$where_status = "";
if ($filter_status == 'aktif') $where_status = " AND su.status = 'aktif'";
elseif ($filter_status == 'nonaktif') $where_status = " AND su.status = 'nonaktif'";

$q_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian su WHERE su.id_mapel = '$id_mapel_guru' $where_status");
$jumlah_data = mysqli_fetch_assoc($q_count)['total'];
$total_halaman = ceil($jumlah_data / $batas);

// Hitung total semua sesi (tanpa filter) untuk statistik
$q_total_all = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian WHERE id_mapel = '$id_mapel_guru'");
$total_all_sesi = mysqli_fetch_assoc($q_total_all)['total'];

$q_sesi = mysqli_query($conn, "SELECT su.*,
                                       (SELECT GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ', ') 
                                        FROM sesi_ujian_kelas suk JOIN kelas k ON suk.id_kelas = k.id_kelas 
                                        WHERE suk.id_sesi = su.id_sesi) as nama_kelas,
                                       (SELECT COUNT(*) FROM bank_soal WHERE id_mapel = '$id_mapel_guru' AND status = 'aktif') as total_soal_mapel,
                                       (SELECT COUNT(*) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'selesai') as sudah_selesai,
                                       (SELECT COUNT(*) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'sedang_dikerjakan') as sedang_kerja,
                                       (SELECT COUNT(DISTINCT s.id_user) FROM siswa s JOIN sesi_ujian_kelas suk ON s.id_kelas = suk.id_kelas WHERE suk.id_sesi = su.id_sesi) as total_siswa_kelas,
                                       (SELECT ROUND(AVG(u.nilai),1) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'selesai') as rata_nilai
                                FROM sesi_ujian su
                                WHERE su.id_mapel = '$id_mapel_guru' $where_status
                                ORDER BY su.tgl_mulai DESC LIMIT $halaman_awal, $batas");

// Statistik ringkas
$q_total_aktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian WHERE id_mapel = '$id_mapel_guru' AND status = 'aktif'");
$total_aktif = mysqli_fetch_assoc($q_total_aktif)['total'];

$q_total_nonaktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian WHERE id_mapel = '$id_mapel_guru' AND status = 'nonaktif'");
$total_nonaktif = mysqli_fetch_assoc($q_total_nonaktif)['total'];

$q_total_soal = mysqli_query($conn, "SELECT COUNT(*) as total FROM bank_soal WHERE id_mapel = '$id_mapel_guru' AND status = 'aktif'");
$total_soal = mysqli_fetch_assoc($q_total_soal)['total'];

// Hitung waktu saat ini untuk pengecekan status ujian
$now = date('Y-m-d H:i:s');
?>

<!-- Info Banner -->
<div class="card card-custom p-3 mb-4" style="border-left: 4px solid #3b82f6;">
    <div class="d-flex align-items-center gap-2 mb-0">
        <i class="bi bi-info-circle-fill text-primary"></i>
        <small class="text-muted mb-0">
            Halaman ini hanya menampilkan jadwal ujian untuk mapel <strong><?= $guru_mapel['nama_mapel'] ?? '-'; ?></strong>. 
            Pengelolaan jadwal (tambah, edit, hapus, ubah status) hanya dapat dilakukan oleh <strong>Administrator</strong>.
        </small>
    </div>
</div>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold text-dark mb-0">Jadwal Ujian</h5>
        <p class="text-muted small mb-0">Mapel: <strong><?= $guru_mapel['nama_mapel'] ?? '-'; ?></strong> &bull; Daftar jadwal ujian yang telah diatur oleh Administrator</p>
    </div>
    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm" onclick="location.reload()" title="Refresh Data">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
    </button>
</div>

<!-- Statistik Ringkas -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">TOTAL SESI</div>
                    <div class="stat-value text-dark mt-1"><?= $total_all_sesi; ?></div>
                </div>
                <div class="stat-icon bg-light text-dark"><i class="bi bi-calendar-event"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">AKTIF</div>
                    <div class="stat-value text-success mt-1"><?= $total_aktif; ?></div>
                </div>
                <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-lightning-charge-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">NONAKTIF</div>
                    <div class="stat-value text-secondary mt-1"><?= $total_nonaktif; ?></div>
                </div>
                <div class="stat-icon bg-light text-secondary"><i class="bi bi-pause-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">SOAL SAYA</div>
                    <div class="stat-value text-primary mt-1"><?= $total_soal; ?></div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-stack"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card card-custom p-3 mb-4">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <span class="small fw-bold text-muted"><i class="bi bi-funnel me-1"></i> Filter:</span>
        <div class="btn-group btn-group-sm">
            <a href="?page=jadwal_ujian&filter=semua" class="btn <?= ($filter_status == 'semua') ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-3">Semua</a>
            <a href="?page=jadwal_ujian&filter=aktif" class="btn <?= ($filter_status == 'aktif') ? 'btn-success' : 'btn-outline-success'; ?> rounded-pill px-3">Aktif</a>
            <a href="?page=jadwal_ujian&filter=nonaktif" class="btn <?= ($filter_status == 'nonaktif') ? 'btn-secondary' : 'btn-outline-secondary'; ?> rounded-pill px-3">Nonaktif</a>
        </div>
    </div>
</div>

<!-- TABEL JADWAL UJIAN -->
<div class="card card-custom overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="px-3 py-3 text-center" width="5%">No</th>
                    <th width="20%">Nama Ujian</th>
                    <th class="text-center" width="8%">Jenis</th>
                    <th class="text-center" width="10%">Kelas</th>
                    <th class="text-center d-none d-md-table-cell" width="12%">Tanggal & Jam</th>
                    <th class="text-center" width="8%">Durasi</th>
                    <th class="text-center" width="8%">Token</th>
                    <th class="text-center d-none d-md-table-cell" width="10%">Peserta</th>
                    <th class="text-center d-none d-lg-table-cell" width="8%">Rata-rata</th>
                    <th class="text-center" width="11%">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($jumlah_data > 0):
                    $n = $halaman_awal + 1;
                    while ($s = mysqli_fetch_assoc($q_sesi)):
                        $tgl = date('d M Y', strtotime($s['tgl_mulai']));
                        $jam = date('H:i', strtotime($s['tgl_mulai']));

                        // Status & styling
                        if ($s['status'] == 'aktif') {
                            $status_class = 'bg-success';
                            $status_text = 'Aktif';
                            $status_icon = 'bi-lightning-charge-fill';
                        } else {
                            $status_class = 'bg-secondary';
                            $status_text = 'Nonaktif';
                            $status_icon = 'bi-pause-circle';
                        }

                        // Cek apakah ujian sudah lewat waktunya
                        $is_expired = (strtotime($s['tgl_mulai']) + ($s['durasi'] * 60)) < time();
                ?>
                <tr>
                    <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                    <td>
                        <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($s['nama_ujian']); ?></div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border" style="font-size:0.72rem;">
                            <?= ($s['jenis_ujian'] == 'uts') ? 'UTS' : (($s['jenis_ujian'] == 'uas') ? 'UAS' : (($s['jenis_ujian'] == 'uh') ? 'UH' : (($s['jenis_ujian'] == 'quiz') ? 'QUIZ' : strtoupper($s['jenis_ujian'])))); ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex flex-wrap justify-content-center gap-1">
                            <?php 
                            $kelas_arr = explode(', ', $s['nama_kelas'] ?? '');
                            foreach ($kelas_arr as $kn):
                            ?>
                                <span class="badge bg-info bg-opacity-10 text-info" style="font-size:0.68rem;"><?= htmlspecialchars(trim($kn)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td class="text-center d-none d-md-table-cell">
                        <div class="text-dark" style="font-size:0.82rem;"><?= $tgl; ?></div>
                        <small class="text-muted"><?= $jam; ?> WIB</small>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold text-dark" style="font-size:0.85rem;"><?= $s['durasi']; ?></span>
                        <small class="text-muted"> menit</small>
                    </td>
                    <td class="text-center">
                        <?php if ($s['status'] == 'aktif'): ?>
                            <code class="bg-warning bg-opacity-10 text-warning px-2 py-1 rounded fw-bold" style="font-size:0.85rem; letter-spacing:1px;"><?= $s['token']; ?></code>
                        <?php else: ?>
                            <code class="bg-light text-muted px-2 py-1 rounded" style="font-size:0.85rem;"><?= $s['token']; ?></code>
                        <?php endif; ?>
                    </td>
                    <td class="text-center d-none d-md-table-cell">
                        <div class="d-flex flex-column align-items-center gap-1">
                            <small class="text-muted" style="font-size:0.72rem;">
                                <span class="fw-bold text-success"><?= $s['sudah_selesai']; ?></span> selesai
                            </small>
                            <small class="text-muted" style="font-size:0.72rem;">
                                <span class="fw-bold text-primary"><?= $s['sedang_kerja']; ?></span> kerja
                            </small>
                            <small class="text-muted" style="font-size:0.68rem;">
                                dari <?= $s['total_siswa_kelas']; ?> siswa
                            </small>
                        </div>
                    </td>
                    <td class="text-center d-none d-lg-table-cell">
                        <?php if ($s['sudah_selesai'] > 0): ?>
                            <span class="fw-bold" style="font-size:0.95rem; color:<?= ($s['rata_nilai'] >= 70) ? '#16a34a' : '#dc2626'; ?>;"><?= $s['rata_nilai']; ?></span>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $status_class; ?> px-3 py-2 rounded-pill" style="font-size:0.75rem; cursor:default;">
                            <i class="bi <?= $status_icon; ?> me-1"></i><?= $status_text; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-25"></i>
                        <strong>Belum ada jadwal ujian</strong><br>
                        <small>Jadwal ujian untuk mapel Anda belum tersedia. Silakan hubungi <strong>Administrator</strong> untuk mengatur jadwal ujian.</small>
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
                    <a class="page-link rounded-pill px-3 me-2" href="?page=jadwal_ujian&filter=<?= $filter_status; ?>&pagi=<?= $sebelum; ?>"><i class="bi bi-chevron-left"></i></a>
                </li>
                <?php for ($x=1; $x<=$total_halaman; $x++): ?>
                    <li class="page-item <?= ($halaman == $x) ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill mx-1" href="?page=jadwal_ujian&filter=<?= $filter_status; ?>&pagi=<?= $x; ?>"><?= $x; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill px-3 ms-2" href="?page=jadwal_ujian&filter=<?= $filter_status; ?>&pagi=<?= $sesudah; ?>"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
        <div class="text-center mt-2"><small class="text-muted">Halaman <b><?= $halaman; ?></b> dari <b><?= $total_halaman; ?></b></small></div>
    </div>
    <?php endif; ?>
</div>