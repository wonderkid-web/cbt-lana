<?php ob_start(); ?>
<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../login.php");
    exit;
}

$id_user = (int)$_SESSION['id_user'];

// Ambil Data Guru
$query_guru = mysqli_query($conn, "SELECT guru.*, mapel.nama_mapel, mapel.id_mapel as id_mapel_guru
                                     FROM guru 
                                     LEFT JOIN mapel ON guru.id_mapel = mapel.id_mapel 
                                     WHERE guru.id_user = '$id_user'");
$data_guru = mysqli_fetch_assoc($query_guru);
$id_mapel_guru = (int)($data_guru['id_mapel_guru'] ?? 0);

// Stats - ALL using id_mapel NOT id_user for bank_soal
$q_soal = mysqli_query($conn, "SELECT COUNT(*) as total FROM bank_soal WHERE id_mapel = '$id_mapel_guru'");
$total_soal = ($q_soal) ? mysqli_fetch_assoc($q_soal)['total'] : 0;

if ($id_mapel_guru > 0) {
    $q_aktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian WHERE id_mapel = '$id_mapel_guru' AND status = 'aktif'");
    $total_aktif = mysqli_fetch_assoc($q_aktif)['total'];
} else { $total_aktif = 0; }

$q_siswa = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa");
$total_siswa = mysqli_fetch_assoc($q_siswa)['total'];

if ($id_mapel_guru > 0) {
    $q_nilai = mysqli_query($conn, "SELECT COUNT(*) as total, ROUND(AVG(u.nilai),1) as rata_rata
                                     FROM ujian u JOIN sesi_ujian su ON u.id_sesi = su.id_sesi 
                                     WHERE su.id_mapel = '$id_mapel_guru' AND u.status = 'selesai'");
    $nilai_data = mysqli_fetch_assoc($q_nilai);
    $total_nilai = $nilai_data['total'];
    $rata_rata_mapel = $nilai_data['rata_rata'] ?: 0;
} else { $total_nilai = 0; $rata_rata_mapel = 0; }

// 5 Ujian Terbaru
if ($id_mapel_guru > 0) {
    $q_terbaru = mysqli_query($conn, "SELECT su.nama_ujian, k.nama_kelas, su.tgl_mulai, su.status,
                                              (SELECT COUNT(*) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status='selesai') as peserta,
                                              (SELECT ROUND(AVG(u.nilai),1) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status='selesai') as rata
                                       FROM sesi_ujian su
                                       LEFT JOIN kelas k ON su.id_kelas = k.id_kelas
                                       WHERE su.id_mapel = '$id_mapel_guru'
                                       ORDER BY su.tgl_mulai DESC LIMIT 5");
} else { $q_terbaru = null; }

// Grade distribution
if ($id_mapel_guru > 0) {
    $q_grade = mysqli_query($conn, "SELECT 
        SUM(CASE WHEN u.nilai >= 90 THEN 1 ELSE 0 END) as grade_a,
        SUM(CASE WHEN u.nilai >= 80 AND u.nilai < 90 THEN 1 ELSE 0 END) as grade_b,
        SUM(CASE WHEN u.nilai >= 70 AND u.nilai < 80 THEN 1 ELSE 0 END) as grade_c,
        SUM(CASE WHEN u.nilai >= 60 AND u.nilai < 70 THEN 1 ELSE 0 END) as grade_d,
        SUM(CASE WHEN u.nilai < 60 THEN 1 ELSE 0 END) as grade_e
        FROM ujian u JOIN sesi_ujian su ON u.id_sesi = su.id_sesi 
        WHERE su.id_mapel = '$id_mapel_guru' AND u.status = 'selesai'");
    $grade_data = mysqli_fetch_assoc($q_grade);
} else { $grade_data = ['grade_a'=>0,'grade_b'=>0,'grade_c'=>0,'grade_d'=>0,'grade_e'=>0]; }

// Routing
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$allowed_pages = ['home', 'bank_soal', 'jadwal_ujian', 'monitoring', 'nilai_siswa'];
if (!in_array($page, $allowed_pages)) $page = 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { 
            --sidebar-bg: #0f172a; 
            --accent: #3b82f6; 
            --sidebar-w: 260px;
        }
        body { 
            background-color: #f1f5f9; 
            font-family: 'Inter', sans-serif; 
            overflow-x: hidden;
        }

        /* ===== SIDEBAR (DESKTOP) ===== */
        #sidebar { 
            width: var(--sidebar-w);
            min-width: var(--sidebar-w);
            max-width: var(--sidebar-w);
            background: var(--sidebar-bg); 
            color: #fff; 
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            overflow-x: hidden;
            transition: margin-left 0.3s ease;
            z-index: 1050;
        }
        /* Scrollbar tipis */
        #sidebar::-webkit-scrollbar { width: 4px; }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }

        /* Desktop: sidebar tertutup → geser ke kiri, konten jadi full */
        #sidebar.collapsed {
            margin-left: calc(var(--sidebar-w) * -1);
        }

        .sidebar-header { padding: 25px; background: rgba(0,0,0,0.2); text-align: center; }
        
        .nav-link { 
            color: rgba(255,255,255,0.7); 
            padding: 12px 20px; 
            display: flex; 
            align-items: center; 
            transition: 0.3s;
        }
        .nav-link:hover, .nav-link.active { 
            color: #fff; 
            background: var(--accent); 
            border-radius: 10px; 
            margin: 0 10px; 
        }
        .nav-link i { font-size: 1.2rem; margin-right: 15px; }

        /* ===== CONTENT AREA ===== */
        .content-area {
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w));
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        /* Saat sidebar tertutup di desktop → konten full */
        .content-area.expanded {
            margin-left: 0;
            width: 100%;
        }

        .top-nav { 
            background: #fff; 
            padding: 15px 25px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        }

        /* ===== OVERLAY GELAP (HANYA MOBILE) ===== */
        #sidebarOverlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }
        #sidebarOverlay.active {
            display: block;
        }

        /* Kunci scroll body saat sidebar terbuka di MOBILE saja */
        body.mobile-sidebar-open {
            overflow: hidden !important;
        }

        /* ===== RESPONSIVE MOBILE ===== */
        @media (max-width: 768px) {
            /* Mobile: sidebar selalu tersembunyi default */
            #sidebar { 
                margin-left: calc(var(--sidebar-w) * -1);
            }
            /* Mobile: class active = tampilkan */
            #sidebar.active {
                margin-left: 0;
            }
            /* Mobile: konten selalu full, tidak bergeser */
            .content-area {
                margin-left: 0 !important;
                width: 100% !important;
            }
            /* Jangan pakai class collapsed di mobile */
            #sidebar.collapsed {
                margin-left: calc(var(--sidebar-w) * -1);
            }
        }

        /* ===== CARDS & COMPONENTS ===== */
        .card-stat { 
            border: none; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.02); 
            border: 1px solid #e2e8f0; 
        }
        .stat-card { 
            border-radius: 16px; 
            border: 1px solid #e2e8f0; 
            background: #fff; 
            padding: 20px; 
            transition: transform 0.2s; 
        }
        .stat-card:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.06); 
        }
        .icon-box, .stat-icon { 
            width: 48px; 
            height: 48px; 
            border-radius: 14px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .card-custom { 
            border: none; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.02); 
            border: 1px solid #e2e8f0; 
        }
        .stat-value { 
            font-size: 1.6rem; 
            font-weight: 800; 
            line-height: 1; 
        }
        .stat-label { 
            font-size: 0.75rem; 
            color: #64748b; 
            margin-top: 4px; 
        }
        .grade-bar { 
            height: 28px; 
            border-radius: 8px; 
            display: flex; 
            align-items: center; 
            padding: 0 12px; 
            font-weight: 700; 
            font-size: 0.82rem; 
        }
    </style>
    <!-- Sidebar: grouping menu + mode mini -->
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=4">
    <script src="../assets/js/sidebar.js?v=4"></script>
</head>
<body>

<!-- Overlay gelap (hanya aktif di mobile) -->
<div id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-header">
        <h5 class="fw-bold mb-0">SMK PUTRA ANDA BINJAI</h5>
        <small class="opacity-50">Guru</small>
    </div>
    <ul class="nav flex-column p-2 mt-3">
        <li class="mb-2"><a href="?page=home" class="nav-link <?= $page == 'home' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="mb-2"><a href="?page=bank_soal" class="nav-link <?= $page == 'bank_soal' ? 'active' : ''; ?>"><i class="bi bi-file-earmark-text"></i> Bank Soal</a></li>
        <li class="mb-2"><a href="?page=jadwal_ujian" class="nav-link <?= $page == 'jadwal_ujian' ? 'active' : ''; ?>"><i class="bi bi-calendar-check"></i> Jadwal Ujian</a></li>
        <li class="mb-2"><a href="?page=monitoring" class="nav-link <?= $page == 'monitoring' ? 'active' : ''; ?>"><i class="bi bi-eye"></i> Monitoring</a></li>
        <li class="mb-2"><a href="?page=nilai_siswa" class="nav-link <?= $page == 'nilai_siswa' ? 'active' : ''; ?>"><i class="bi bi-bar-chart-line"></i> Nilai Siswa</a></li>
        <hr class="mx-3 opacity-25 text-white">
        <li><a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
    </ul>
</nav>

<!-- Konten Utama -->
<div class="content-area" id="contentArea">
    <nav class="top-nav d-flex justify-content-between align-items-center sticky-top">
        <button class="btn btn-light" id="toggleBtn">
            <i class="bi bi-list fs-4"></i>
        </button>
        <div class="dropdown">
            <a class="text-decoration-none text-dark dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                <div class="text-end me-2 d-none d-md-block">
                    <small class="text-muted d-block" style="font-size: 10px;">Guru</small>
                    <span class="fw-bold" style="font-size: 14px;"><?= htmlspecialchars($data_guru['nama_guru']); ?></span>
                </div>
                <i class="bi bi-person-circle fs-3 text-primary"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-left me-2"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <?php
        switch ($page) {
            case 'bank_soal':
                if(file_exists('bank_soal.php')) include 'bank_soal.php';
                else echo "<div class='alert alert-warning'>File bank_soal.php belum dibuat.</div>";
                break;
            case 'jadwal_ujian':
                if(file_exists('jadwal_ujian.php')) include 'jadwal_ujian.php';
                else echo "<div class='alert alert-warning'>File jadwal_ujian.php belum dibuat.</div>";
                break;
            case 'monitoring':
                if(file_exists('monitoring.php')) include 'monitoring.php';
                else echo "<div class='alert alert-warning'>File monitoring.php belum dibuat.</div>";
                break;
            case 'nilai_siswa':
                if(file_exists('nilai_siswa.php')) include 'nilai_siswa.php';
                else echo "<div class='alert alert-warning'>File nilai_siswa.php belum dibuat.</div>";
                break;
            default:
        ?>

        <!-- ==================== DASHBOARD HOME ==================== -->
        <!-- Welcome -->
        <div class="card card-stat p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Selamat Datang, <?= htmlspecialchars($data_guru['nama_guru']); ?>!</h4>
                    <p class="text-muted small mb-0">
                        Guru Mata Pelajaran <strong><?= htmlspecialchars($data_guru['nama_mapel'] ?? '-'); ?></strong>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="?page=bank_soal" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-plus-lg me-1"></i> Buat Soal
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistik Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">SOAL SAYA</div>
                            <div class="stat-value text-primary mt-1"><?= $total_soal; ?></div>
                        </div>
                        <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-stack"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">UJIAN AKTIF</div>
                            <div class="stat-value text-success mt-1"><?= $total_aktif; ?></div>
                        </div>
                        <div class="icon-box" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-lightning-charge-fill"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">NILAI MASUK</div>
                            <div class="stat-value text-info mt-1"><?= $total_nilai; ?></div>
                        </div>
                        <div class="icon-box" style="background:#dbeafe;color:#2563eb;"><i class="bi bi-graph-up-arrow"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">RATA-RATA</div>
                            <div class="stat-value mt-1" style="color:#d97706;"><?= $rata_rata_mapel; ?></div>
                        </div>
                        <div class="icon-box" style="background:#fef3c7;color:#d97706;"><i class="bi bi-trophy"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Distribusi Grade -->
            <div class="col-lg-4">
                <div class="card card-stat p-4 h-100">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-pie-chart me-2"></i>Distribusi Grade</h6>
                    <?php if ($total_nilai > 0): ?>
                    <?php 
                    $grades = [
                        ['A', $grade_data['grade_a'] ?? 0, '#16a34a', '#dcfce7'],
                        ['B', $grade_data['grade_b'] ?? 0, '#2563eb', '#dbeafe'],
                        ['C', $grade_data['grade_c'] ?? 0, '#d97706', '#fef3c7'],
                        ['D', $grade_data['grade_d'] ?? 0, '#ea580c', '#ffedd5'],
                        ['E', $grade_data['grade_e'] ?? 0, '#dc2626', '#fee2e2'],
                    ];
                    $max_grade = max(array_column($grades, 1));
                    foreach ($grades as $g): 
                        $pct = $total_nilai > 0 ? round(($g[1] / $total_nilai) * 100) : 0;
                        $bar_pct = $max_grade > 0 ? round(($g[1] / $max_grade) * 100) : 0;
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="grade-bar" style="background:<?= $g[3]; ?>; color:<?= $g[2]; ?>; width:36px; flex-shrink:0; justify-content:center;"><?= $g[0]; ?></div>
                        <div class="flex-grow-1">
                            <div style="background:<?= $g[3]; ?>; height:8px; border-radius:4px; overflow:hidden;">
                                <div style="background:<?= $g[2]; ?>; height:100%; width:<?= $bar_pct; ?>%; border-radius:4px; transition:width 0.5s;"></div>
                            </div>
                        </div>
                        <div class="text-end" style="min-width:60px;">
                            <span class="fw-bold text-dark" style="font-size:0.9rem;"><?= $g[1]; ?></span>
                            <span class="text-muted small">(<?=$pct?>%)</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-pie-chart fs-1 d-block mb-2 opacity-25"></i>
                        <small>Belum ada data nilai</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ujian Terbaru -->
            <div class="col-lg-8">
                <div class="card card-stat h-100">
                    <div class="card-header bg-white border-bottom px-4 pt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2"></i>Ujian Terbaru</h6>
                            <a href="?page=nilai_siswa" class="text-primary text-decoration-none" style="font-size:0.82rem;">Lihat Semua <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="small text-muted" style="font-size:0.75rem;">Ujian</th>
                                    <th class="small text-muted" style="font-size:0.75rem;">Kelas</th>
                                    <th class="small text-muted text-center" style="font-size:0.75rem;">Tanggal</th>
                                    <th class="small text-muted text-center" style="font-size:0.75rem;">Peserta</th>
                                    <th class="small text-muted text-center" style="font-size:0.75rem;">Rata-rata</th>
                                    <th class="small text-muted text-center" style="font-size:0.75rem;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($q_terbaru && mysqli_num_rows($q_terbaru) > 0):
                                    while ($u = mysqli_fetch_assoc($q_terbaru)):
                                        $status_class = ($u['status'] == 'aktif') ? 'bg-success' : 'bg-secondary';
                                ?>
                                <tr>
                                    <td class="fw-bold text-dark" style="font-size:0.85rem;"><?= $u['nama_ujian']; ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $u['nama_kelas'] ?? '-'; ?></span></td>
                                    <td class="text-center small text-muted"><?= date('d M Y', strtotime($u['tgl_mulai'])); ?></td>
                                    <td class="text-center fw-bold"><?= $u['peserta']; ?></td>
                                    <td class="text-center fw-bold" style="color:<?= ($u['rata'] >= 70) ? '#16a34a' : '#dc2626'; ?>;"><?= $u['rata'] ?: '-'; ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $status_class; ?> px-2 py-1 rounded-pill" style="font-size:0.72rem;"><?= strtoupper($u['status']); ?></span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2 opacity-25"></i><small>Belum ada data ujian</small></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="row g-3">
            <div class="col-md-4">
                <a href="?page=bank_soal" class="text-decoration-none">
                    <div class="stat-card d-flex align-items-center gap-3" style="border-left: 4px solid #3b82f6;">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary flex-shrink-0"><i class="bi bi-file-earmark-plus"></i></div>
                        <div>
                            <div class="fw-bold text-dark">Kelola Bank Soal</div>
                            <small class="text-muted">Buat, edit, import soal</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="?page=monitoring" class="text-decoration-none">
                    <div class="stat-card d-flex align-items-center gap-3" style="border-left: 4px solid #16a34a;">
                        <div class="icon-box flex-shrink-0" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-eye"></i></div>
                        <div>
                            <div class="fw-bold text-dark">Monitoring Ujian</div>
                            <small class="text-muted">Pantau ujian berlangsung</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="?page=nilai_siswa" class="text-decoration-none">
                    <div class="stat-card d-flex align-items-center gap-3" style="border-left: 4px solid #d97706;">
                        <div class="icon-box flex-shrink-0" style="background:#fef3c7;color:#d97706;"><i class="bi bi-journal-check"></i></div>
                        <div>
                            <div class="fw-bold text-dark">Rekap Nilai</div>
                            <small class="text-muted">Lihat hasil ujian siswa</small>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <?php break; } ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebarOverlay');
    const content    = document.getElementById('contentArea');
    const toggle     = document.getElementById('toggleBtn');
    const body       = document.body;

    // Cek apakah tampilan mobile
    function isMobile() {
        return window.innerWidth <= 768;
    }

    // ==================
    // DESKTOP: Toggle collapse/expand (tanpa overlay)
    // ==================
    function desktopToggle() {
        const isOpen = !sidebar.classList.contains('collapsed');

        if (isOpen) {
            // Sidebar tutup → konten jadi full
            sidebar.classList.add('collapsed');
            content.classList.add('expanded');
        } else {
            // Sidebar buka kembali
            sidebar.classList.remove('collapsed');
            content.classList.remove('expanded');
        }
    }

    // ==================
    // MOBILE: Overlay + sidebar slide
    // ==================
    function mobileOpen() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        body.classList.add('mobile-sidebar-open');
    }

    function mobileClose() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        body.classList.remove('mobile-sidebar-open');
    }

    // ==================
    // TOGGLE UTAMA
    // ==================
    toggle.addEventListener('click', function () {
        if (isMobile()) {
            sidebar.classList.contains('active') ? mobileClose() : mobileOpen();
        } else {
            desktopToggle();
        }
    });

    // Klik overlay gelap → tutup (mobile only)
    overlay.addEventListener('click', function () {
        mobileClose();
    });

    // Klik menu di sidebar → auto tutup (mobile only)
    document.querySelectorAll('#sidebar .nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobile()) mobileClose();
        });
    });

    // Resize: reset state saat pindah ukuran layar
    window.addEventListener('resize', function () {
        if (!isMobile()) {
            // Kalau pindah ke desktop, bersihkan state mobile
            mobileClose();
        }
    });

    // Real-time kick (cek status akun guru)
    function cekStatusAkun() {
        fetch('../config/check_guru.php')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'nonaktif' || data.status === 'logout') {
                    clearInterval(intervalId);
                    Swal.fire({
                        icon: 'error', title: 'AKSES DITOLAK!',
                        text: 'Akun Anda telah dinonaktifkan oleh Administrator.',
                        timer: 3000, showConfirmButton: false,
                        allowOutsideClick: false, backdrop: `rgba(0,0,123,0.4)`
                    }).then(() => { window.location.href = '../login.php'; });
                }
            }).catch(e => console.error('Gagal cek status:', e));
    }
    const intervalId = setInterval(cekStatusAkun, 2000);
</script>
</body>
</html>