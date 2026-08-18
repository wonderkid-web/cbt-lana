<?php
require_once '../config/check_admin.php';
require_once '../config/database.php';

date_default_timezone_set('Asia/Jakarta');

// ============================================================
// FORCE COMPLETE LOGIC
// ============================================================
if (isset($_GET['force_selesai']) && isset($_GET['id_ujian'])) {
    $id_ujian_fs = (int)$_GET['id_ujian'];
    $id_sesi_fs = (int)$_GET['force_selesai'];

    $q_cek_ujian = mysqli_query($conn, "SELECT u.*, su.id_sesi, su.id_mapel 
                                          FROM ujian u 
                                          JOIN sesi_ujian su ON u.id_sesi = su.id_sesi 
                                          WHERE u.id_ujian = '$id_ujian_fs'");
    $cek_ujian = mysqli_fetch_assoc($q_cek_ujian);

    if ($cek_ujian && $cek_ujian['status'] == 'sedang_dikerjakan') {
        $q_jawaban = mysqli_query($conn, "SELECT js.id_soal, js.jawaban, bs.kunci_jawaban
                                           FROM jawaban_siswa js
                                           JOIN bank_soal bs ON js.id_soal = bs.id_soal
                                           WHERE js.id_ujian = '$id_ujian_fs'");

        $benar = 0;
        while ($j = mysqli_fetch_assoc($q_jawaban)) {
            if (strtoupper($j['jawaban']) == strtoupper($j['kunci_jawaban'])) {
                $benar++;
            }
        }

        $id_mapel_sesi = (int)$cek_ujian['id_mapel'];
        $q_total_soal = mysqli_query($conn, "SELECT COUNT(*) as total FROM bank_soal WHERE id_mapel = '$id_mapel_sesi' AND status = 'aktif'");
        $total_soal_aktif = mysqli_fetch_assoc($q_total_soal)['total'];

        if ($total_soal_aktif > 0) {
            $nilai = round(($benar / $total_soal_aktif) * 100, 1);
        } else {
            $nilai = 0;
        }

        $waktu_selesai = date('Y-m-d H:i:s');
        mysqli_query($conn, "UPDATE ujian SET nilai = '$nilai', status = 'selesai', waktu_selesai = '$waktu_selesai' WHERE id_ujian = '$id_ujian_fs'");
    }

    header("Location: monitoring.php?detail=$id_sesi_fs");
    exit;
}

// ============================================================
// FILTER: mapel dropdown
// ============================================================
 $filter_mapel = isset($_GET['filter_mapel']) ? (int)$_GET['filter_mapel'] : 0;

// ============================================================
// OVERVIEW STATS
// ============================================================
 $q_stat_aktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian WHERE status = 'aktif'");
 $stat_sesi_aktif = mysqli_fetch_assoc($q_stat_aktif)['total'];

 $q_stat_kerja = mysqli_query($conn, "SELECT COUNT(*) as total FROM ujian WHERE status = 'sedang_dikerjakan'");
 $stat_sedang_kerja = mysqli_fetch_assoc($q_stat_kerja)['total'];

 $q_stat_selesai_hari = mysqli_query($conn, "SELECT COUNT(*) as total FROM ujian WHERE status = 'selesai' AND DATE(waktu_selesai) = CURDATE()");
 $stat_selesai_hari = mysqli_fetch_assoc($q_stat_selesai_hari)['total'];

 $q_stat_terdaftar = mysqli_query($conn, "SELECT COUNT(DISTINCT s.id_user) as total 
                                          FROM siswa s 
                                          JOIN sesi_ujian_kelas suk ON s.id_kelas = suk.id_kelas 
                                          JOIN sesi_ujian su ON suk.id_sesi = su.id_sesi 
                                          WHERE su.status = 'aktif'");
 $stat_terdaftar = mysqli_fetch_assoc($q_stat_terdaftar)['total'];

// ============================================================
// MAPEL FILTER DROPDOWN
// ============================================================
 $q_mapel_filter = mysqli_query($conn, "SELECT DISTINCT m.id_mapel, m.nama_mapel 
                                        FROM mapel m 
                                        JOIN sesi_ujian su ON m.id_mapel = su.id_mapel 
                                        WHERE su.status = 'aktif' 
                                        ORDER BY m.nama_mapel ASC");

// ============================================================
// ACTIVE SESSIONS
// ============================================================
 $mapel_where = '';
if ($filter_mapel > 0) {
    $mapel_where = " AND su.id_mapel = '$filter_mapel'";
}

 $q_sesi_aktif = mysqli_query($conn, "SELECT su.*, m.nama_mapel,
                                       (SELECT nama_guru FROM guru WHERE id_mapel = su.id_mapel LIMIT 1) as nama_guru,
                                       (SELECT GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ', ') 
                                        FROM sesi_ujian_kelas suk JOIN kelas k ON suk.id_kelas = k.id_kelas 
                                        WHERE suk.id_sesi = su.id_sesi) as nama_kelas
                                       FROM sesi_ujian su
                                       LEFT JOIN mapel m ON su.id_mapel = m.id_mapel
                                       WHERE su.status = 'aktif'$mapel_where
                                       ORDER BY su.tgl_mulai DESC");

// ============================================================
// COMPLETED EXAMS — WITH PAGINATION
// ============================================================
 $batas_selesai = 5;
 $hal_selesai = isset($_GET['hal_selesai']) ? (int)$_GET['hal_selesai'] : 1;
 $hal_selesai = max(1, $hal_selesai);
 $awal_selesai = ($hal_selesai - 1) * $batas_selesai;

 $total_data_selesai = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian WHERE status = 'nonaktif'");
 $jml_data_selesai = mysqli_fetch_assoc($total_data_selesai)['total'];
 $total_hal_selesai = ceil($jml_data_selesai / $batas_selesai);

 $q_selesai = mysqli_query($conn, "SELECT su.*, m.nama_mapel,
                                         (SELECT nama_guru FROM guru WHERE id_mapel = su.id_mapel LIMIT 1) as nama_guru,
                                         (SELECT COUNT(*) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'selesai') as peserta,
                                         (SELECT ROUND(AVG(u.nilai),1) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'selesai') as rata,
                                         (SELECT GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ', ') 
                                          FROM sesi_ujian_kelas suk JOIN kelas k ON suk.id_kelas = k.id_kelas 
                                          WHERE suk.id_sesi = su.id_sesi) as nama_kelas
                                  FROM sesi_ujian su
                                  LEFT JOIN mapel m ON su.id_mapel = m.id_mapel
                                  WHERE su.status = 'nonaktif'
                                  ORDER BY su.tgl_mulai DESC 
                                  LIMIT $awal_selesai, $batas_selesai");

// Helper: truncate teks panjang
function truncateKelas($text, $max = 25) {
    if (strlen($text) <= $max) return $text;
    return substr($text, 0, $max) . '...';
}


function generatePagination($halaman, $total_halaman, $link_search) {
    $pages = []; $maxVisible = 5;
    if ($total_halaman <= $maxVisible) { for ($i = 1; $i <= $total_halaman; $i++) $pages[] = $i; }
    else { $pages[] = 1; $rangeStart = max(2, $halaman - 1); $rangeEnd = min($total_halaman - 1, $halaman + 1);
        if ($halaman <= 3) { $rangeStart = 2; $rangeEnd = min($maxVisible - 1, $total_halaman - 1); }
        elseif ($halaman >= $total_halaman - 2) { $rangeStart = max(2, $total_halaman - $maxVisible + 2); $rangeEnd = $total_halaman - 1; }
        if ($rangeStart > 2) $pages[] = '...';
        for ($i = $rangeStart; $i <= $rangeEnd; $i++) $pages[] = $i;
        if ($rangeEnd < $total_halaman - 1) $pages[] = '...'; $pages[] = $total_halaman; }
    foreach ($pages as $p) { if ($p === '...') { echo '<li class="page-item disabled"><span class="page-link px-1">...</span></li>'; }
        else { $active = ($halaman == $p) ? 'active' : ''; echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . $link_search . $p . '">' . $p . '</a></li>'; } }
}

// ============================================================
// DETAIL VIEW
// ============================================================
 $show_detail = false;
 $detail_sesi = null;
 $tot_siswa = 0;
 $tot_selesai = 0;
 $tot_kerja = 0;
 $tot_belum = 0;
 $tot_tidak_ikut = 0;
 $all_peserta = [];

if (isset($_GET['detail']) && is_numeric($_GET['detail'])) {
    $id_sesi_detail = (int)$_GET['detail'];

    $q_detail = mysqli_query($conn, "SELECT su.*, m.nama_mapel,
                                      (SELECT nama_guru FROM guru WHERE id_mapel = su.id_mapel LIMIT 1) as nama_guru
                                      FROM sesi_ujian su
                                      LEFT JOIN mapel m ON su.id_mapel = m.id_mapel
                                      WHERE su.id_sesi = '$id_sesi_detail'");
    $detail_sesi = mysqli_fetch_assoc($q_detail);

    if ($detail_sesi) {
        $show_detail = true;

        $q_kelas_detail = mysqli_query($conn, "SELECT k.nama_kelas, k.id_kelas
                                                FROM sesi_ujian_kelas suk
                                                JOIN kelas k ON suk.id_kelas = k.id_kelas
                                                WHERE suk.id_sesi = '$id_sesi_detail'
                                                ORDER BY k.nama_kelas ASC");
        $detail_kelas_list = [];
        $detail_kelas_ids = [];
        while ($kl = mysqli_fetch_assoc($q_kelas_detail)) {
            $detail_kelas_list[] = $kl['nama_kelas'];
            $detail_kelas_ids[] = (int)$kl['id_kelas'];
        }
        $detail_sesi['nama_kelas'] = implode(', ', $detail_kelas_list);

        if (!empty($detail_kelas_ids)) {
            $kelas_ids_str = implode(',', $detail_kelas_ids);
            $q_tot_all = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa WHERE id_kelas IN ($kelas_ids_str)");
            $detail_sesi['tot_siswa_all'] = mysqli_fetch_assoc($q_tot_all)['total'];
        } else {
            $detail_sesi['tot_siswa_all'] = 0;
        }

        $peserta_list = mysqli_query($conn, "SELECT s.id_user, s.nama_siswa, k.nama_kelas as nama_kelas_siswa,
                                               u.id_ujian, u.waktu_mulai, u.waktu_selesai, u.nilai, u.status as status_ujian,
                                               (SELECT COUNT(*) FROM jawaban_siswa js WHERE js.id_ujian = u.id_ujian) as dijawab
                                        FROM siswa s
                                        JOIN kelas k ON s.id_kelas = k.id_kelas
                                        LEFT JOIN ujian u ON s.id_user = u.id_user AND u.id_sesi = '$id_sesi_detail'
                                        WHERE s.id_kelas IN (SELECT id_kelas FROM sesi_ujian_kelas WHERE id_sesi = '$id_sesi_detail')
                                        ORDER BY k.nama_kelas ASC, s.nama_siswa ASC");

                if ($peserta_list) {
            $sesi_waktu_habis = false;
            $sesi_end_ts = strtotime($detail_sesi['tgl_mulai']) + ((int)$detail_sesi['durasi'] * 60);
            if (time() > $sesi_end_ts) $sesi_waktu_habis = true;

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
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Ujian | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
:root { --sidebar-bg: #0f172a; --accent: #3b82f6; --sidebar-w: 260px; }
body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; overflow-x: hidden; }
#sidebar { width: var(--sidebar-w); min-width: var(--sidebar-w); max-width: var(--sidebar-w); background: var(--sidebar-bg); color: #fff; height: 100vh; position: fixed; top: 0; left: 0; overflow-y: auto; overflow-x: hidden; transition: margin-left 0.3s ease; z-index: 1050; }
#sidebar::-webkit-scrollbar { width: 4px; }
#sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
#sidebar.collapsed { margin-left: calc(var(--sidebar-w) * -1); }
.sidebar-header { padding: 25px; background: rgba(0,0,0,0.2); text-align: center; }
.nav-link { color: rgba(255,255,255,0.7); padding: 12px 20px; display: flex; align-items: center; transition: 0.3s; }
.nav-link:hover, .nav-link.active { color: #fff; background: var(--accent); border-radius: 10px; margin: 0 10px; }
.nav-link i { font-size: 1.2rem; margin-right: 15px; }
.content-area { margin-left: var(--sidebar-w); width: calc(100% - var(--sidebar-w)); transition: margin-left 0.3s ease, width 0.3s ease; }
.content-area.expanded { margin-left: 0; width: 100%; }
.top-nav { background: #fff; padding: 15px 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
#sidebarOverlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1040; }
#sidebarOverlay.active { display: block; }
body.mobile-sidebar-open { overflow: hidden !important; }
@media (max-width: 768px) {
    #sidebar { margin-left: calc(var(--sidebar-w) * -1); }
    #sidebar.active { margin-left: 0; }
    .content-area { margin-left: 0 !important; width: 100% !important; }
    #sidebar.collapsed { margin-left: calc(var(--sidebar-w) * -1); }
}
.card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
.stat-card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 18px; text-align: center; transition: transform 0.2s; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.05); }
.stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 1.2rem; }
.stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: 0.75rem; color: #64748b; margin-top: 4px; }
.filter-card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; }
.progress { background-color: #e2e8f0; }
.kelas-badge-wrap { max-width: 200px; white-space: normal !important; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.4; display: inline-block; }
@keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
.pulse-dot { animation: pulse-dot 1.5s ease-in-out infinite; }
@media (max-width: 768px) { .kelas-badge-wrap { max-width: 140px; } }
    </style>
    <!-- Sidebar: grouping menu + mode mini -->
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=4">
    <script src="../assets/js/sidebar.js?v=4"></script>
</head>
<body>

<div id="sidebarOverlay"></div>
<div class="d-flex">
    <!-- SIDEBAR -->
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="content-area" id="contentArea">
        <nav class="top-nav d-flex justify-content-between align-items-center">
            <button class="btn btn-light" id="toggleBtn"><i class="bi bi-list fs-4"></i></button>
            <div class="dropdown">
                <a class="text-decoration-none text-dark dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                    <div class="text-end me-2 d-none d-md-block">
                        <small class="text-muted d-block" style="font-size: 10px;">Login Sebagai</small>
                        <span class="fw-bold" style="font-size: 14px;">Administrator</span>
                    </div>
                    <i class="bi bi-person-circle fs-3 text-primary"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                    <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-left me-2"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold text-dark mb-0"><i class="bi bi-broadcast text-primary me-2"></i>Monitoring Ujian</h4>
                    <p class="text-muted small mb-0">Pantau seluruh ujian yang sedang berlangsung secara real-time</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm" onclick="location.reload()" title="Refresh">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                </div>
            </div>

            <?php if ($show_detail && $detail_sesi): ?>
            <!-- ========== MODE DETAIL ========== -->
            <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
                <a href="monitoring.php<?= $filter_mapel > 0 ? '?filter_mapel='.$filter_mapel : ''; ?>" class="btn btn-light btn-sm rounded-pill px-3 shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <div>
                    <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($detail_sesi['nama_ujian']); ?></h6>
                    <small class="text-muted">
                        <?= htmlspecialchars($detail_sesi['nama_mapel'] ?? '-'); ?> &bull;
                        Guru: <strong><?= htmlspecialchars($detail_sesi['nama_guru'] ?? '-'); ?></strong> &bull;
                        <span title="<?= htmlspecialchars($detail_sesi['nama_kelas']); ?>">Kelas: <strong><?= truncateKelas($detail_sesi['nama_kelas'], 40); ?></strong></span> &bull;
                        Token: <code><?= $detail_sesi['token']; ?></code> &bull;
                        Durasi: <?= $detail_sesi['durasi']; ?> menit
                    </small>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div><div class="stat-label">TOTAL SISWA</div><div class="stat-value text-dark mt-1"><?= $tot_siswa; ?></div></div>
                            <div class="stat-icon bg-light text-dark"><i class="bi bi-people-fill"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card" style="border-left: 4px solid #16a34a;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div><div class="stat-label">SELESAI</div><div class="stat-value text-success mt-1"><?= $tot_selesai; ?></div></div>
                            <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-check-circle-fill"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div><div class="stat-label">SEDANG DIKERJAKAN</div><div class="stat-value text-primary mt-1"><?= $tot_kerja; ?></div></div>
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-pencil-fill"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card" style="border-left: 4px solid #94a3b8;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div><div class="stat-label">BELUM MULAI</div><div class="stat-value text-secondary mt-1"><?= $tot_belum; ?></div></div>
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

            <!-- Progress Bar -->
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
                    <small class="text-muted" title="<?= htmlspecialchars($detail_sesi['nama_kelas']); ?>"><?= truncateKelas($detail_sesi['nama_kelas'], 50); ?></small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3 py-3 text-center" width="5%">No</th>
                                <th width="20%">Nama Siswa</th>
                                <th class="text-center" width="12%">Kelas</th>
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
                                    if ($p['waktu_mulai']) $waktu_mulai_text = date('d M Y, H:i', strtotime($p['waktu_mulai']));

                                    if ($p['status_ujian'] == 'sedang_dikerjakan' && $p['waktu_mulai']) {
                                        $waktu_mulai_ts = strtotime($p['waktu_mulai']);
                                        $durasi_sesi = (int)$detail_sesi['durasi'] * 60;
                                        $batas_waktu = $waktu_mulai_ts + $durasi_sesi;
                                        $sisa = $batas_waktu - time();
                                        if ($sisa > 0) {
                                            $menit_sisa = floor($sisa / 60); $detik_sisa = $sisa % 60;
                                            $durasi_text = '<div class="mt-1"><span class="badge bg-danger bg-opacity-10 text-danger fw-bold rounded-pill px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-hourglass-split me-1"></i>' . $menit_sisa . ':' . str_pad($detik_sisa, 2, '0', STR_PAD_LEFT) . '</span></div>';
                                        } else {
                                            $durasi_text = '<div class="mt-1"><span class="badge bg-danger text-white rounded-pill px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-exclamation-triangle me-1"></i>Habis!</span></div>';
                                        }
                                    } elseif ($p['status_ujian'] == 'selesai' && $p['waktu_mulai'] && $p['waktu_selesai']) {
                                        $menit_diff = floor((strtotime($p['waktu_selesai']) - strtotime($p['waktu_mulai'])) / 60);
                                        $durasi_text = '<div class="mt-1"><span class="text-muted" style="font-size:0.75rem;"><i class="bi bi-stopwatch me-1"></i>' . $menit_diff . ' mnt</span></div>';
                                    }
                            ?>
                            <tr class="<?= ($p['status_ujian'] == 'sedang_dikerjakan') ? 'table-primary' : ''; ?>">
                                <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                                <td><div class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($p['nama_siswa']); ?></div></td>
                                <td class="text-center"><span class="badge bg-light text-dark border" style="font-size:0.72rem;"><?= htmlspecialchars($p['nama_kelas_siswa'] ?? '-'); ?></span></td>
                                <td class="text-center d-none d-lg-table-cell"><small class="text-muted"><?= $waktu_mulai_text; ?></small><?= $durasi_text; ?></td>
                                <td class="text-center"><?php if ($p['status_ujian']): ?><span class="fw-bold" style="font-size:0.85rem;"><?= $dijawab; ?></span><small class="text-muted"> soal</small><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                                <td class="text-center"><?php if ($p['status_ujian'] == 'selesai'): ?><span class="fw-bold <?= $nilai_color; ?>" style="font-size:1.1rem;"><?= $p['nilai']; ?></span><?php elseif ($p['status_ujian'] == 'gagal'): ?><span class="fw-bold text-danger" style="font-size:1.1rem;">0</span><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                                <td class="text-center"><?= $badge; ?></td>
                                <td class="text-center">
                                    <?php if ($p['status_ujian'] == 'sedang_dikerjakan' && $p['id_ujian']): ?>
                                        <a href="monitoring.php?detail=<?= $detail_sesi['id_sesi']; ?>&force_selesai=<?= $detail_sesi['id_sesi']; ?>&id_ujian=<?= $p['id_ujian']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2" onclick="return confirm('Selesaikan ujian paksa untuk siswa ini?')" title="Force Selesai"><i class="bi bi-stop-circle me-1"></i> Selesaikan</a>
                                    <?php elseif ($p['status_ujian'] == 'selesai'): ?><span class="text-success"><i class="bi bi-check-lg fs-5"></i></span>
                                    <?php elseif ($p['status_ujian'] == 'gagal'): ?><span class="text-danger"><i class="bi bi-x-circle fs-5"></i></span>
                                    <?php else: ?><span class="text-muted small">-</span><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i><strong>Tidak ada peserta</strong><br><small>Belum ada siswa terdaftar.</small></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <small class="text-muted">Menampilkan <?= $offset_detail + 1; ?>-<?= min($offset_detail + $batas_detail, $tot_siswa); ?> dari <b><?= $tot_siswa; ?></b> peserta</small>
                    <?php if($total_hal_detail > 0): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <?php $detail_filter = $filter_mapel > 0 ? '&filter_mapel='.$filter_mapel : ''; ?>
                            <li class="page-item <?= ($hal_detail <= 1) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?detail=<?= $id_sesi_detail; ?>&hal_detail=<?= $hal_detail-1 ?><?= $detail_filter; ?>">Prev</a></li>
                            <?php generatePagination($hal_detail, $total_hal_detail, '?detail='.$id_sesi_detail.'&filter_mapel='.$filter_mapel.'&hal_detail='); ?>
                            <li class="page-item <?= ($hal_detail >= $total_hal_detail) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?detail=<?= $id_sesi_detail; ?>&hal_detail=<?= $hal_detail+1 ?><?= $detail_filter; ?>">Next</a></li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- ========== MODE LIST: SESI AKTIF ========== -->

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-lightning-charge-fill"></i></div>
                        <div class="stat-value text-success"><?= $stat_sesi_aktif; ?></div>
                        <div class="stat-label">Sesi Ujian Aktif</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-pencil-fill"></i></div>
                        <div class="stat-value text-primary"><?= $stat_sedang_kerja; ?></div>
                        <div class="stat-label">Sedang Mengerjakan</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-value" style="color:#d97706;"><?= $stat_selesai_hari; ?></div>
                        <div class="stat-label">Selesai Hari Ini</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-value text-info"><?= $stat_terdaftar; ?></div>
                        <div class="stat-label">Siswa di Ujian Aktif</div>
                    </div>
                </div>
            </div>

            <!-- Filter Mapel -->
            <?php if (mysqli_num_rows($q_mapel_filter) > 1): ?>
            <div class="filter-card p-3 mb-4">
                <form method="GET" action="monitoring.php" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-1 d-block"><i class="bi bi-funnel me-1"></i>FILTER MAPEL</label>
                        <select name="filter_mapel" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">Semua Mapel</option>
                            <?php mysqli_data_seek($q_mapel_filter, 0); while ($mf = mysqli_fetch_assoc($q_mapel_filter)): ?>
                                <option value="<?= $mf['id_mapel']; ?>" <?= ($filter_mapel == $mf['id_mapel']) ? 'selected' : ''; ?>><?= htmlspecialchars($mf['nama_mapel']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <a href="monitoring.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-x-circle me-1"></i> Reset</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if (mysqli_num_rows($q_sesi_aktif) > 0): ?>
            <?php while ($sesi = mysqli_fetch_assoc($q_sesi_aktif)):
                $id_s = (int)$sesi['id_sesi'];
                $q_stat = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(CASE WHEN u.status='selesai' THEN 1 ELSE 0 END) as selesai, SUM(CASE WHEN u.status='sedang_dikerjakan' THEN 1 ELSE 0 END) as kerja, SUM(CASE WHEN u.status='gagal' THEN 1 ELSE 0 END) as gagal, ROUND(AVG(CASE WHEN u.status='selesai' THEN u.nilai END),1) as rata FROM ujian u WHERE u.id_sesi='$id_s'");
                $stat = mysqli_fetch_assoc($q_stat);

                $q_tot_siswa_kelas = mysqli_query($conn, "SELECT COUNT(DISTINCT s.id_user) as total FROM siswa s JOIN sesi_ujian_kelas suk ON s.id_kelas = suk.id_kelas WHERE suk.id_sesi = '$id_s'");
                $tot_siswa_kelas = mysqli_fetch_assoc($q_tot_siswa_kelas)['total'];

                $now = time(); $tgl_mulai_ts = strtotime($sesi['tgl_mulai']); $durasi_detik = (int)$sesi['durasi'] * 60;
                if ($now >= $tgl_mulai_ts && $now <= $tgl_mulai_ts + $durasi_detik) { $time_status_class='text-success'; $time_status_text='Berlangsung'; $time_status_icon='pulse-dot'; }
                elseif ($tgl_mulai_ts > $now) { $time_status_class='text-warning'; $time_status_text='Belum Dimulai'; $time_status_icon=''; }
                else { $time_status_class='text-muted'; $time_status_text='Waktu Habis'; $time_status_icon=''; }
            ?>
            <div class="card card-custom mb-3">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-lg-5 mb-3 mb-lg-0">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="stat-icon flex-shrink-0" style="background:#dcfce7;color:#16a34a;width:42px;height:42px;border-radius:12px;margin:0;"><i class="bi bi-journal-text"></i></div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($sesi['nama_ujian']); ?></h6>
                                    <small class="text-muted d-flex align-items-center flex-wrap gap-1">
                                        <span class="badge bg-info bg-opacity-10 text-info" style="font-size:0.7rem;"><?= htmlspecialchars($sesi['nama_mapel'] ?? '-'); ?></span>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:0.7rem;"><?= htmlspecialchars($sesi['nama_guru'] ?? '-'); ?></span>
                                        <span class="badge bg-light text-dark border kelas-badge-wrap" style="font-size:0.7rem;" title="<?= htmlspecialchars($sesi['nama_kelas'] ?? '-'); ?>"><?= truncateKelas($sesi['nama_kelas'] ?? '-', 25); ?></span>
                                    </small>
                                </div>
                            </div>
                            <div class="d-flex gap-3 small text-muted mt-2 flex-wrap">
                                <span><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($sesi['tgl_mulai'])); ?></span>
                                <span><i class="bi bi-hourglass-split me-1"></i><?= $sesi['durasi']; ?> mnt</span>
                                <span><i class="bi bi-key me-1"></i><code><?= $sesi['token']; ?></code></span>
                                <span class="<?= $time_status_class; ?>" style="font-size:0.75rem;"><i class="bi bi-circle-fill me-1 <?= $time_status_icon; ?>" style="font-size:0.5rem;"></i><?= $time_status_text; ?></span>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-3 mb-lg-0">
                            <div class="mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Progress</small>
                                    <small class="fw-bold text-dark"><?= ($stat['selesai'] ?? 0) + ($stat['kerja'] ?? 0); ?> / <?= $tot_siswa_kelas; ?></small>
                                </div>
                                <div class="progress" style="height: 8px; border-radius: 6px;">
                                    <div class="progress-bar bg-success" style="width: <?= $tot_siswa_kelas > 0 ? round((($stat['selesai'] ?? 0) / $tot_siswa_kelas) * 100) : 0; ?>%;"></div>
                                    <div class="progress-bar bg-primary" style="width: <?= $tot_siswa_kelas > 0 ? round((($stat['kerja'] ?? 0) / $tot_siswa_kelas) * 100) : 0; ?>%;"></div>
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
                                <div class="fw-bold fs-4" style="color:<?= (($stat['rata'] ?? 0) >= 70) ? '#16a34a' : (($stat['rata'] ?? 0) > 0 ? '#dc2626' : '#94a3b8'); ?>;"><?= $stat['rata'] ?: '-'; ?></div>
                            </div>
                            <a href="monitoring.php?detail=<?= $sesi['id_sesi']; ?>" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm w-100"><i class="bi bi-eye me-1"></i> Detail</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

            <?php else: ?>
            <div class="card card-custom p-5">
                <div class="text-center text-muted">
                    <i class="bi bi-emoji-neutral fs-1 d-block mb-3 opacity-25"></i>
                    <h6 class="fw-bold text-dark">Tidak Ada Ujian Aktif</h6>
                    <p class="small mb-3">Saat ini tidak ada sesi ujian yang sedang berlangsung.</p>
                    <a href="sesi_ujian.php" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm"><i class="bi bi-calendar-check me-1"></i> Kelola Sesi Ujian</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- ========== UJIAN SELESAI + PAGINATION ========== -->
            <?php if (mysqli_num_rows($q_selesai) > 0): ?>
            <div class="card card-custom mt-4">
                <div class="card-header bg-white border-bottom px-4 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-archive me-2"></i>Ujian Selesai</h6>
                        <a href="laporan_full.php" class="text-primary text-decoration-none" style="font-size:0.82rem;">Lihat Rekap Nilai <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3 text-center small text-muted" width="5%">No</th>
                                <th class="px-3 small text-muted">Ujian</th>
                                <th class="text-center small text-muted">Mapel</th>
                                <th class="text-center small text-muted" style="min-width:130px;max-width:200px;">Kelas</th>
                                <th class="text-center small text-muted">Tanggal</th>
                                <th class="text-center small text-muted">Peserta</th>
                                <th class="text-center small text-muted">Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $n = $awal_selesai + 1; while ($sq = mysqli_fetch_assoc($q_selesai)): ?>
                            <tr>
                                <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                                <td class="fw-bold text-dark" style="font-size:0.85rem;max-width:180px;"><?= htmlspecialchars($sq['nama_ujian']); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-info bg-opacity-10 text-info" style="font-size:0.75rem;"><?= htmlspecialchars($sq['nama_mapel'] ?? '-'); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border kelas-badge-wrap" style="font-size:0.72rem;" title="<?= htmlspecialchars($sq['nama_kelas'] ?? '-'); ?>">
                                        <?= truncateKelas($sq['nama_kelas'] ?? '-', 25); ?>
                                    </span>
                                </td>
                                <td class="text-center small text-muted"><?= date('d M Y', strtotime($sq['tgl_mulai'])); ?></td>
                                <td class="text-center fw-bold"><?= $sq['peserta']; ?></td>
                                <td class="text-center fw-bold" style="color:<?= (($sq['rata'] ?? 0) >= 70) ? '#16a34a' : '#dc2626'; ?>;"><?= $sq['rata'] ?: '-'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <small class="text-muted">Menampilkan <b>5</b> dari <b><?= $jml_data_selesai; ?></b> sesi</small>
                        <?php if($total_hal_selesai > 0): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <?php $filter_mapel_param = $filter_mapel > 0 ? '&filter_mapel='.$filter_mapel : ''; ?>
                                <li class="page-item <?= ($hal_selesai <= 1) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?hal_selesai=<?= $hal_selesai-1 ?><?= $filter_mapel_param; ?>">Prev</a></li>
                                <?php generatePagination($hal_selesai, $total_hal_selesai, '?filter_mapel='.$filter_mapel.'&hal_selesai='); ?>
                                <li class="page-item <?= ($hal_selesai >= $total_hal_selesai) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?hal_selesai=<?= $hal_selesai+1 ?><?= $filter_mapel_param; ?>">Next</a></li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const content = document.getElementById('contentArea');
    const toggle = document.getElementById('toggleBtn');
    const body = document.body;
    function isMobile() { return window.innerWidth <= 768; }
    function desktopToggle() { const isOpen = !sidebar.classList.contains('collapsed'); if (isOpen) { sidebar.classList.add('collapsed'); content.classList.add('expanded'); } else { sidebar.classList.remove('collapsed'); content.classList.remove('expanded'); } }
    function mobileOpen() { sidebar.classList.add('active'); overlay.classList.add('active'); body.classList.add('mobile-sidebar-open'); }
    function mobileClose() { sidebar.classList.remove('active'); overlay.classList.remove('active'); body.classList.remove('mobile-sidebar-open'); }
    toggle.addEventListener('click', function () { if (isMobile()) { sidebar.classList.contains('active') ? mobileClose() : mobileOpen(); } else { desktopToggle(); } });
    overlay.addEventListener('click', function () { mobileClose(); });
    document.querySelectorAll('#sidebar .nav-link').forEach(function (link) { link.addEventListener('click', function () { if (isMobile()) mobileClose(); }); });
    window.addEventListener('resize', function () { if (!isMobile()) mobileClose(); });
</script>
</body>
</html>