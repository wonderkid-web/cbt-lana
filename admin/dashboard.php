<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- AMBIL DATA STATISTIK ---
$jml_siswa   = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM siswa"));
$jml_guru    = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM guru"));
$jml_kelas   = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kelas"));
$jml_jurusan = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM jurusan"));
$jml_konsentrasi = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM konsentrasi"));
$jml_ruang   = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM ruang_ujian"));
$jml_mapel   = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM mapel"));
$jml_soal    = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM bank_soal"));
$jml_sesi    = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM sesi_ujian"));
$jml_ujian_aktif = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM sesi_ujian WHERE status='aktif'"));
$jml_rfid    = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kartu_rfid"));
$jml_nilai   = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM ujian WHERE status='selesai'"));
$jml_sedang_ujian = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM ujian WHERE status='sedang_dikerjakan'"));

// --- DATA UNTUK MINI CHART ---

// Guru: aktif vs nonaktif
$guru_aktif = (int)mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='guru' AND status='aktif'"));
$guru_nonaktif = $jml_guru - $guru_aktif;

// Siswa: aktif vs nonaktif
$siswa_aktif = (int)mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='siswa' AND status='aktif'"));
$siswa_nonaktif = $jml_siswa - $siswa_aktif;

// Siswa per Kelas
$chart_siswa_kelas_labels = [];
$chart_siswa_kelas_data = [];
$q_sk = mysqli_query($conn, "SELECT k.nama_kelas, COUNT(s.id_siswa) as jumlah 
                              FROM kelas k LEFT JOIN siswa s ON k.id_kelas = s.id_kelas 
                              GROUP BY k.id_kelas ORDER BY k.nama_kelas ASC");
while ($r = mysqli_fetch_assoc($q_sk)) {
    $chart_siswa_kelas_labels[] = $r['nama_kelas'];
    $chart_siswa_kelas_data[] = (int)$r['jumlah'];
}

// Mapel: umum vs kejuruan
$mapel_umum = (int)mysqli_num_rows(mysqli_query($conn, "SELECT * FROM mapel WHERE jenis_mapel='umum'"));
$mapel_kejuruan = $jml_mapel - $mapel_umum;

// Soal per Mapel (top 6)
$chart_soal_labels = [];
$chart_soal_data = [];
$q_sm = mysqli_query($conn, "SELECT m.nama_mapel, COUNT(bs.id_soal) as jumlah 
                              FROM mapel m LEFT JOIN bank_soal bs ON m.id_mapel = bs.id_mapel 
                              GROUP BY m.id_mapel ORDER BY jumlah DESC LIMIT 6");
while ($r = mysqli_fetch_assoc($q_sm)) {
    $chart_soal_labels[] = $r['nama_mapel'];
    $chart_soal_data[] = (int)$r['jumlah'];
}

// Sesi: aktif vs nonaktif
$sesi_aktif = $jml_ujian_aktif;
$sesi_nonaktif = $jml_sesi - $sesi_aktif;

// Kelas per Jurusan
$chart_kelas_jurusan_labels = [];
$chart_kelas_jurusan_data = [];
$q_kj = mysqli_query($conn, "SELECT j.kode, COUNT(k.id_kelas) as jumlah 
                              FROM jurusan j LEFT JOIN kelas k ON j.id_jurusan = k.id_jurusan 
                              GROUP BY j.id_jurusan ORDER BY j.kode ASC");
while ($r = mysqli_fetch_assoc($q_kj)) {
    $chart_kelas_jurusan_labels[] = $r['kode'] ?: 'Lainnya';
    $chart_kelas_jurusan_data[] = (int)$r['jumlah'];
}

// Konsentrasi per Jurusan
$chart_kons_jurusan_labels = [];
$chart_kons_jurusan_data = [];
$q_kons = mysqli_query($conn, "SELECT j.kode, COUNT(kn.id_konsentrasi) as jumlah 
                                FROM jurusan j LEFT JOIN konsentrasi kn ON j.id_jurusan = kn.id_jurusan 
                                GROUP BY j.id_jurusan ORDER BY j.kode ASC");
while ($r = mysqli_fetch_assoc($q_kons)) {
    $chart_kons_jurusan_labels[] = $r['kode'] ?: 'Lainnya';
    $chart_kons_jurusan_data[] = (int)$r['jumlah'];
}

// RFID: aktif vs nonaktif
$rfid_aktif = (int)mysqli_num_rows(mysqli_query($conn, "SELECT kr.* FROM kartu_rfid kr JOIN users u ON kr.siswa_id IN (SELECT id_siswa FROM siswa WHERE id_user = u.id_user) WHERE u.status='aktif'"));
$rfid_nonaktif = $jml_rfid - $rfid_aktif;

// Nilai per mapel (top 6 rata-rata)
$chart_nilai_labels = [];
$chart_nilai_data = [];
$q_nilai = mysqli_query($conn, "SELECT m.nama_mapel, ROUND(AVG(u.nilai),1) as rata 
                                 FROM ujian u JOIN sesi_ujian su ON u.id_sesi = su.id_sesi 
                                 JOIN mapel m ON su.id_mapel = m.id_mapel 
                                 WHERE u.status='selesai' AND u.nilai IS NOT NULL 
                                 GROUP BY m.id_mapel ORDER BY rata DESC LIMIT 6");
while ($r = mysqli_fetch_assoc($q_nilai)) {
    $chart_nilai_labels[] = $r['nama_mapel'];
    $chart_nilai_data[] = (float)$r['rata'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root { 
            --sidebar-bg: #0f172a; 
            --accent: #3b82f6; 
            --sidebar-w: 260px;
        }
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

        #sidebarOverlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; }
        #sidebarOverlay.active { display: block; }
        body.mobile-sidebar-open { overflow: hidden !important; }

        @media (max-width: 768px) {
            #sidebar { margin-left: calc(var(--sidebar-w) * -1); }
            #sidebar.active { margin-left: 0; }
            .content-area { margin-left: 0 !important; width: 100% !important; }
            #sidebar.collapsed { margin-left: calc(var(--sidebar-w) * -1); }
        }

        /* ===== STAT + CHART CARDS ===== */
        .card-stat { 
            border: none; border-radius: 20px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.02); 
            transition: 0.3s; background: #fff;
            border: 1px solid #e2e8f0;
        }
        .card-stat:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .icon-box {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .chart-mini { max-height: 110px; }
    </style>
    <!-- Sidebar: grouping menu + mode mini -->
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=4">
    <script src="../assets/js/sidebar.js?v=4"></script>
</head>
<body>

<div id="sidebarOverlay"></div>

<?php include __DIR__ . '/_sidebar.php'; ?>

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
        <div class="mb-4">
            <h4 class="fw-bold text-dark">Ringkasan Data Sistem</h4>
            <p class="text-muted small">Berikut adalah ringkasan seluruh data yang tersedia di database CBT.</p>
        </div>

        <div class="row g-4">

            <!-- 1. GURU - Doughnut aktif/nonaktif -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">TOTAL GURU</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_guru; ?></h2>
                        </div>
                        <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-person-workspace"></i></div>
                    </div>
                    <div class="chart-mini"><canvas id="cGuru"></canvas></div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-success"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Aktif <?= $guru_aktif; ?></small>
                        <small class="text-danger"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Nonaktif <?= $guru_nonaktif; ?></small>
                    </div>
                </div>
            </div>

            <!-- 2. SISWA - Bar per kelas -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">TOTAL SISWA</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_siswa; ?></h2>
                        </div>
                        <div class="icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-people-fill"></i></div>
                    </div>
                    <div class="chart-mini"><canvas id="cSiswa"></canvas></div>
                    <small class="text-muted mt-2 d-block">Distribusi per kelas</small>
                </div>
            </div>

            <!-- 3. JURUSAN - Bar per jurusan -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">TOTAL JURUSAN</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_jurusan; ?></h2>
                        </div>
                        <div class="icon-box bg-opacity-10" style="color:#7c3aed;background:rgba(124,58,237,0.1);"><i class="bi bi-mortarboard-fill"></i></div>
                    </div>
                    <div class="chart-mini"><canvas id="cJurusan"></canvas></div>
                    <small class="text-muted mt-2 d-block">Kelas per jurusan</small>
                </div>
            </div>

            <!-- 4. KONSENTRASI - Bar per jurusan -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">TOTAL KONSENTRASI</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_konsentrasi; ?></h2>
                        </div>
                        <div class="icon-box bg-opacity-10" style="color:#db2777;background:rgba(219,39,119,0.1);"><i class="bi bi-diagram-3-fill"></i></div>
                    </div>
                    <div class="chart-mini"><canvas id="cKonsentrasi"></canvas></div>
                    <small class="text-muted mt-2 d-block">Per jurusan</small>
                </div>
            </div>

            <!-- 5. KELAS - Doughnut aktif login -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">JUMLAH KELAS</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_kelas; ?></h2>
                        </div>
                        <div class="icon-box bg-warning bg-opacity-10 text-warning"><i class="bi bi-house-door-fill"></i></div>
                    </div>
                    <div class="chart-mini"><canvas id="cKelas"></canvas></div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-success"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Login On <?= $siswa_aktif; ?></small>
                        <small class="text-secondary"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Off <?= $siswa_nonaktif; ?></small>
                    </div>
                </div>
            </div>

            <!-- 6. RUANG - Simple -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">TOTAL RUANG</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_ruang; ?></h2>
                        </div>
                        <div class="icon-box bg-info bg-opacity-10 text-info"><i class="bi bi-building"></i></div>
                    </div>
                    <div class="d-flex align-items-end justify-content-center" style="height:110px;">
                        <?php for ($i = 0; $i < min($jml_ruang, 8); $i++): ?>
                        <div style="width:20px;background:rgba(6,182,212,<?= 0.3 + ($i * 0.1); ?>);border-radius:4px 4px 0 0;margin:0 2px;height:<?= 30 + ($i * 10); ?>px;"></div>
                        <?php endfor; ?>
                    </div>
                    <small class="text-muted mt-2 d-block text-center">Ruang ujian tersedia</small>
                </div>
            </div>

            <!-- 7. MAPEL - Doughnut umum/kejuruan -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">TOTAL MAPEL</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_mapel; ?></h2>
                        </div>
                        <div class="icon-box bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-book-half"></i></div>
                    </div>
                    <div class="chart-mini"><canvas id="cMapel"></canvas></div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-primary"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Umum <?= $mapel_umum; ?></small>
                        <small style="color:#f59e0b;"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Kejuruan <?= $mapel_kejuruan; ?></small>
                    </div>
                </div>
            </div>

            <!-- 8. BANK SOAL - Horizontal bar per mapel -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">BANK SOAL</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_soal; ?></h2>
                        </div>
                        <div class="icon-box bg-dark bg-opacity-10 text-dark"><i class="bi bi-stack"></i></div>
                    </div>
                    <div class="chart-mini"><canvas id="cSoal"></canvas></div>
                    <small class="text-muted mt-2 d-block">Top mapel terbanyak</small>
                </div>
            </div>

            <!-- 9. SESI UJIAN - Doughnut aktif/nonaktif -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">SESI UJIAN</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_sesi; ?></h2>
                        </div>
                        <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-clipboard-check"></i></div>
                    </div>
                    <div class="chart-mini"><canvas id="cSesi"></canvas></div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-success"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Aktif <?= $sesi_aktif; ?></small>
                        <small class="text-secondary"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Nonaktif <?= $sesi_nonaktif; ?></small>
                    </div>
                </div>
            </div>

            <!-- 10. UJIAN AKTIF - Highlight -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100" style="border-color:#fca5a5;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">UJIAN AKTIF</h6>
                            <h2 class="fw-bold mb-0 text-danger"><?= $jml_ujian_aktif; ?></h2>
                        </div>
                        <div class="icon-box bg-danger bg-opacity-10 text-danger"><i class="bi bi-lightning-charge-fill"></i></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center" style="height:110px;">
                        <div class="text-center">
                            <div class="mb-2">
                                <span class="badge bg-danger bg-opacity-10 text-danger p-2 px-3 rounded-pill" style="font-size:0.85rem;">
                                    <i class="bi bi-broadcast me-1"></i><?= $jml_sedang_ujian; ?> sedang mengerjakan
                                </span>
                            </div>
                            <div>
                                <span class="badge bg-success bg-opacity-10 text-success p-2 px-3 rounded-pill" style="font-size:0.85rem;">
                                    <i class="bi bi-check-circle me-1"></i><?= $jml_nilai; ?> sudah selesai
                                </span>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted mt-2 d-block text-center">Status ujian saat ini</small>
                </div>
            </div>

            <!-- 11. KARTU RFID - Doughnut -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">KARTU RFID</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_rfid; ?></h2>
                        </div>
                        <div class="icon-box bg-info bg-opacity-10" style="color:#0891b2;"><i class="bi bi-cpu"></i></div>
                    </div>
                    <div class="chart-mini"><canvas id="cRfid"></canvas></div>
                    <small class="text-muted mt-2 d-block">Kartu terdaftar vs belum</small>
                </div>
            </div>

            <!-- 12. HASIL NILAI - Bar rata-rata per mapel -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">HASIL NILAI</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_nilai; ?></h2>
                        </div>
                        <div class="icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-graph-up-arrow"></i></div>
                    </div>
                    <div class="chart-mini"><canvas id="cNilai"></canvas></div>
                    <small class="text-muted mt-2 d-block">Rata-rata per mapel</small>
                </div>
            </div>

            <!-- 13. SEDANG UJIAN - Highlight -->
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-stat p-4 h-100" style="border-color:#fcd34d;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted small fw-bold mb-0">SEDANG UJIAN</h6>
                            <h2 class="fw-bold mb-0 text-warning"><?= $jml_sedang_ujian; ?></h2>
                        </div>
                        <div class="icon-box bg-warning bg-opacity-10 text-warning"><i class="bi bi-broadcast"></i></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center" style="height:110px;">
                        <?php if ($jml_sedang_ujian > 0): ?>
                        <div class="text-center">
                            <i class="bi bi-broadcast text-warning" style="font-size:3rem;animation:pulse 1.5s infinite;"></i>
                            <div class="mt-2"><small class="fw-bold text-warning"><?= $jml_sedang_ujian; ?> siswa sedang ujian</small></div>
                        </div>
                        <?php else: ?>
                        <div class="text-center">
                            <i class="bi bi-moon text-muted" style="font-size:3rem;"></i>
                            <div class="mt-2"><small class="text-muted">Tidak ada ujian berlangsung</small></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted mt-2 d-block text-center"><a href="monitoring.php" class="text-decoration-none">Monitoring <i class="bi bi-arrow-right"></i></a></small>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ===== SIDEBAR =====
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const content = document.getElementById('contentArea');
    const toggle = document.getElementById('toggleBtn');
    const body = document.body;

    function isMobile() { return window.innerWidth <= 768; }
    function desktopToggle() {
        const isOpen = !sidebar.classList.contains('collapsed');
        if (isOpen) { sidebar.classList.add('collapsed'); content.classList.add('expanded'); }
        else { sidebar.classList.remove('collapsed'); content.classList.remove('expanded'); }
    }
    function mobileOpen() { sidebar.classList.add('active'); overlay.classList.add('active'); body.classList.add('mobile-sidebar-open'); }
    function mobileClose() { sidebar.classList.remove('active'); overlay.classList.remove('active'); body.classList.remove('mobile-sidebar-open'); }

    toggle.addEventListener('click', function () {
        if (isMobile()) { sidebar.classList.contains('active') ? mobileClose() : mobileOpen(); } else { desktopToggle(); }
    });
    overlay.addEventListener('click', function () { mobileClose(); });
    document.querySelectorAll('#sidebar .nav-link').forEach(function (link) {
        link.addEventListener('click', function () { if (isMobile()) mobileClose(); });
    });
    window.addEventListener('resize', function () { if (!isMobile()) mobileClose(); });

    // ===== MINI CHARTS =====
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 10;
    Chart.defaults.plugins.legend.display = false;

    const doughnutOpts = {
        responsive: true, maintainAspectRatio: false,
        cutout: '65%',
        plugins: { legend: { display: false } }
    };

    const barOpts = {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { display: false, grid: { display: false } },
            y: { display: false, grid: { display: false }, beginAtZero: true }
        }
    };

    const hBarOpts = {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { display: false, grid: { display: false }, beginAtZero: true },
            y: { display: false, grid: { display: false } }
        }
    };

    // 1. Guru - Doughnut
    new Chart(document.getElementById('cGuru'), {
        type: 'doughnut',
        data: { labels: ['Aktif','Nonaktif'], datasets: [{ data: [<?= $guru_aktif; ?>, <?= $guru_nonaktif; ?>], backgroundColor: ['#10b981','#fecaca'], borderWidth: 0 }] },
        options: doughnutOpts
    });

    // 2. Siswa - Bar per kelas
    new Chart(document.getElementById('cSiswa'), {
        type: 'bar',
        data: { labels: <?= json_encode($chart_siswa_kelas_labels); ?>, datasets: [{ data: <?= json_encode($chart_siswa_kelas_data); ?>, backgroundColor: '#3b82f6', borderRadius: 4, maxBarThickness: 20 }] },
        options: barOpts
    });

    // 3. Jurusan - Bar kelas per jurusan
    new Chart(document.getElementById('cJurusan'), {
        type: 'bar',
        data: { labels: <?= json_encode($chart_kelas_jurusan_labels); ?>, datasets: [{ data: <?= json_encode($chart_kelas_jurusan_data); ?>, backgroundColor: '#7c3aed', borderRadius: 4, maxBarThickness: 24 }] },
        options: barOpts
    });

    // 4. Konsentrasi - Bar per jurusan
    new Chart(document.getElementById('cKonsentrasi'), {
        type: 'bar',
        data: { labels: <?= json_encode($chart_kons_jurusan_labels); ?>, datasets: [{ data: <?= json_encode($chart_kons_jurusan_data); ?>, backgroundColor: '#db2777', borderRadius: 4, maxBarThickness: 24 }] },
        options: barOpts
    });

    // 5. Kelas - Doughnut siswa login
    new Chart(document.getElementById('cKelas'), {
        type: 'doughnut',
        data: { labels: ['Login On','Login Off'], datasets: [{ data: [<?= $siswa_aktif; ?>, <?= $siswa_nonaktif; ?>], backgroundColor: ['#10b981','#e2e8f0'], borderWidth: 0 }] },
        options: doughnutOpts
    });

    // 7. Mapel - Doughnut umum/kejuruan
    new Chart(document.getElementById('cMapel'), {
        type: 'doughnut',
        data: { labels: ['Umum','Kejuruan'], datasets: [{ data: [<?= $mapel_umum; ?>, <?= $mapel_kejuruan; ?>], backgroundColor: ['#3b82f6','#f59e0b'], borderWidth: 0 }] },
        options: doughnutOpts
    });

    // 8. Soal - Horizontal bar
    new Chart(document.getElementById('cSoal'), {
        type: 'bar',
        data: { labels: <?= json_encode($chart_soal_labels); ?>, datasets: [{ data: <?= json_encode($chart_soal_data); ?>, backgroundColor: ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899'], borderRadius: 4, maxBarThickness: 14 }] },
        options: hBarOpts
    });

    // 9. Sesi - Doughnut
    new Chart(document.getElementById('cSesi'), {
        type: 'doughnut',
        data: { labels: ['Aktif','Nonaktif'], datasets: [{ data: [<?= $sesi_aktif; ?>, <?= $sesi_nonaktif; ?>], backgroundColor: ['#10b981','#e2e8f0'], borderWidth: 0 }] },
        options: doughnutOpts
    });

    // 11. RFID - Doughnut terdaftar vs belum
    new Chart(document.getElementById('cRfid'), {
        type: 'doughnut',
        data: { labels: ['Terdaftar','Belum'], datasets: [{ data: [<?= $jml_rfid; ?>, <?= max(0, $jml_siswa - $jml_rfid); ?>], backgroundColor: ['#0891b2','#e2e8f0'], borderWidth: 0 }] },
        options: doughnutOpts
    });

    // 12. Nilai - Bar rata-rata
    <?php if (!empty($chart_nilai_data)): ?>
    new Chart(document.getElementById('cNilai'), {
        type: 'bar',
        data: { labels: <?= json_encode($chart_nilai_labels); ?>, datasets: [{ data: <?= json_encode($chart_nilai_data); ?>, backgroundColor: '#10b981', borderRadius: 4, maxBarThickness: 20 }] },
        options: barOpts
    });
    <?php else: ?>
    new Chart(document.getElementById('cNilai'), {
        type: 'doughnut',
        data: { labels: ['Selesai','Belum'], datasets: [{ data: [<?= $jml_nilai; ?>, 1], backgroundColor: ['#10b981','#e2e8f0'], borderWidth: 0 }] },
        options: doughnutOpts
    });
    <?php endif; ?>
</script>
</body>
</html>