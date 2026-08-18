<?php
require_once '../config/check_admin.php';
require_once '../config/database.php';

$id_user_login = (int)($_SESSION['id_user'] ?? 0);

// ===========================================================================
// LOGIKA PROSES DATA (CRUD)
// ===========================================================================

// 1. Tambah Sesi Ujian
if (isset($_POST['tambah_sesi'])) {
    $nama_ujian = mysqli_real_escape_string($conn, $_POST['nama_ujian']);
    $id_mapel   = (int)$_POST['id_mapel'];
    $jenis_ujian = mysqli_real_escape_string($conn, $_POST['jenis_ujian']);
    $tgl_mulai  = mysqli_real_escape_string($conn, $_POST['tgl_mulai']);
    $durasi     = (int)$_POST['durasi'];
    $token      = strtoupper(substr(md5(rand(100000,999999) . time()), 0, 5));
    $status     = 'nonaktif';

    $sql = "INSERT INTO sesi_ujian (nama_ujian, jenis_ujian, id_mapel, tgl_mulai, durasi, token, status)
            VALUES ('$nama_ujian', '$jenis_ujian', '$id_mapel', '$tgl_mulai', '$durasi', '$token', '$status')";

    if (mysqli_query($conn, $sql)) {
        $id_sesi_baru = mysqli_insert_id($conn);
        // Simpan kelas yang dipilih ke sesi_ujian_kelas
        $kelas_arr = $_POST['kelas'] ?? [];
        foreach ($kelas_arr as $id_kelas) {
            $id_kelas = (int)$id_kelas;
            if ($id_kelas > 0) {
                mysqli_query($conn, "INSERT INTO sesi_ujian_kelas (id_sesi, id_kelas) VALUES ('$id_sesi_baru', '$id_kelas')");
            }
        }
        header("Location: sesi_ujian.php?msg=ditambah");
        exit;
    }
}

// 2. Edit Sesi Ujian
if (isset($_POST['edit_sesi'])) {
    $id_sesi     = (int)$_POST['id_sesi'];
    $nama_ujian  = mysqli_real_escape_string($conn, $_POST['nama_ujian']);
    $id_mapel    = (int)$_POST['id_mapel'];
    $jenis_ujian = mysqli_real_escape_string($conn, $_POST['jenis_ujian']);
    $tgl_mulai   = mysqli_real_escape_string($conn, $_POST['tgl_mulai']);
    $durasi      = (int)$_POST['durasi'];

    $sql = "UPDATE sesi_ujian SET
                nama_ujian = '$nama_ujian',
                jenis_ujian = '$jenis_ujian',
                id_mapel = '$id_mapel',
                tgl_mulai = '$tgl_mulai',
                durasi = '$durasi'
            WHERE id_sesi = '$id_sesi'";

    if (mysqli_query($conn, $sql)) {
        // Update kelas: hapus lama, insert baru
        mysqli_query($conn, "DELETE FROM sesi_ujian_kelas WHERE id_sesi = '$id_sesi'");
        $kelas_arr = $_POST['kelas'] ?? [];
        foreach ($kelas_arr as $id_kelas) {
            $id_kelas = (int)$id_kelas;
            if ($id_kelas > 0) {
                mysqli_query($conn, "INSERT INTO sesi_ujian_kelas (id_sesi, id_kelas) VALUES ('$id_sesi', '$id_kelas')");
            }
        }
        header("Location: sesi_ujian.php?msg=diedit");
        exit;
    }
}

// 3. Hapus Sesi Ujian
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Hapus relasi kelas dulu
    mysqli_query($conn, "DELETE FROM sesi_ujian_kelas WHERE id_sesi = '$id'");
    mysqli_query($conn, "DELETE FROM sesi_ujian WHERE id_sesi = '$id'");
    header("Location: sesi_ujian.php?msg=dihapus");
    exit;
}

// 4. Toggle Status (Aktif/Nonaktif)
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $q = mysqli_query($conn, "SELECT status FROM sesi_ujian WHERE id_sesi = '$id'");
    $s = mysqli_fetch_assoc($q);
    $new_status = ($s['status'] === 'aktif') ? 'nonaktif' : 'aktif';
    mysqli_query($conn, "UPDATE sesi_ujian SET status = '$new_status' WHERE id_sesi = '$id'");
    header("Location: sesi_ujian.php?msg=status_diubah");
    exit;
}

// 5. Generate Token Baru
if (isset($_GET['regen_token'])) {
    $id = (int)$_GET['regen_token'];
    $new_token = strtoupper(substr(md5(rand(100000,999999) . time()), 0, 5));
    mysqli_query($conn, "UPDATE sesi_ujian SET token = '$new_token' WHERE id_sesi = '$id'");
    header("Location: sesi_ujian.php?msg=token_baru&token=$new_token");
    exit;
}

// ===========================================================================
// PAGINATION & FILTER
// ===========================================================================
$batas = 10;
$halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;
$sebelum = $halaman - 1;
$sesudah = $halaman + 1;

// Filter status
$filter_status = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$where_status = "";
if ($filter_status == 'aktif') $where_status = " AND su.status = 'aktif'";
elseif ($filter_status == 'nonaktif') $where_status = " AND su.status = 'nonaktif'";

// Filter jenis mapel
$filter_jenis = isset($_GET['filter_jenis']) ? $_GET['filter_jenis'] : '';
$where_jenis = "";
if ($filter_jenis === 'umum') $where_jenis = " AND m.jenis_mapel = 'umum'";
elseif ($filter_jenis === 'kejuruan') $where_jenis = " AND m.jenis_mapel = 'kejuruan'";

// Hitung total
$q_count = mysqli_query($conn, "SELECT COUNT(*) as total
                                FROM sesi_ujian su
                                LEFT JOIN mapel m ON su.id_mapel = m.id_mapel
                                WHERE 1=1 $where_status $where_jenis");
$jumlah_data = mysqli_fetch_assoc($q_count)['total'];
$total_halaman = ceil($jumlah_data / $batas);

// Query utama - dengan jenis_mapel dan jurusan
$q_sesi = mysqli_query($conn, "SELECT su.*,
                                       m.nama_mapel,
                                       m.jenis_mapel,
                                       m.id_jurusan AS mapel_id_jurusan,
                                       j.nama_program,
                                       (SELECT GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ', ')
                                        FROM sesi_ujian_kelas suk JOIN kelas k ON suk.id_kelas = k.id_kelas
                                        WHERE suk.id_sesi = su.id_sesi) as nama_kelas,
                                       (SELECT COUNT(*) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'selesai') as sudah_selesai,
                                       (SELECT COUNT(*) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'sedang_dikerjakan') as sedang_kerja,
                                       (SELECT COUNT(DISTINCT s.id_user) FROM siswa s JOIN sesi_ujian_kelas suk ON s.id_kelas = suk.id_kelas WHERE suk.id_sesi = su.id_sesi) as total_siswa_kelas,
                                       (SELECT ROUND(AVG(u.nilai),1) FROM ujian u WHERE u.id_sesi = su.id_sesi AND u.status = 'selesai') as rata_nilai
                                FROM sesi_ujian su
                                LEFT JOIN mapel m ON su.id_mapel = m.id_mapel
                                LEFT JOIN jurusan j ON m.id_jurusan = j.id_jurusan
                                WHERE 1=1 $where_status $where_jenis
                                ORDER BY su.tgl_mulai DESC
                                LIMIT $halaman_awal, $batas");

// Statistik
$q_total_all = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian su LEFT JOIN mapel m ON su.id_mapel = m.id_mapel WHERE 1=1 $where_jenis");
$total_all_sesi = mysqli_fetch_assoc($q_total_all)['total'];

$q_total_aktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian su LEFT JOIN mapel m ON su.id_mapel = m.id_mapel WHERE su.status = 'aktif' $where_jenis");
$total_aktif = mysqli_fetch_assoc($q_total_aktif)['total'];

$q_total_nonaktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian su LEFT JOIN mapel m ON su.id_mapel = m.id_mapel WHERE su.status = 'nonaktif' $where_jenis");
$total_nonaktif = mysqli_fetch_assoc($q_total_nonaktif)['total'];

// ===========================================================================
// DATA UNTUK DROPDOWN
// ===========================================================================

// Ambil semua mapel, kelompokkan berdasarkan jenis_mapel
$mapel_umum = [];
$mapel_kejuruan = [];
$q_mapel_all = mysqli_query($conn, "SELECT m.*, j.kode AS kode_jurusan, j.nama_program
                                     FROM mapel m
                                     LEFT JOIN jurusan j ON m.id_jurusan = j.id_jurusan
                                     ORDER BY m.jenis_mapel, m.nama_mapel");
while ($m = mysqli_fetch_assoc($q_mapel_all)) {
    if ($m['jenis_mapel'] === 'umum') {
        $mapel_umum[] = $m;
    } else {
        $mapel_kejuruan[] = $m;
    }
}

// Build mapelData for JS (all mapel with jenis_mapel and id_jurusan)
$mapelData = [];
$q_mapel_js = mysqli_query($conn, "SELECT id_mapel, nama_mapel, jenis_mapel, id_jurusan FROM mapel ORDER BY id_mapel");
while ($m = mysqli_fetch_assoc($q_mapel_js)) {
    $mapelData[] = [
        'id_mapel' => (int)$m['id_mapel'],
        'nama_mapel' => $m['nama_mapel'],
        'jenis_mapel' => $m['jenis_mapel'],
        'id_jurusan' => $m['id_jurusan'] ? (int)$m['id_jurusan'] : null
    ];
}

// Ambil semua kelas dengan jurusan info untuk JS
$kelas_for_js = [];
$q_kelas_full = mysqli_query($conn, "SELECT k.*, j.kode AS kode_jurusan, j.nama_program AS nama_jurusan
                                      FROM kelas k
                                      LEFT JOIN jurusan j ON k.id_jurusan = j.id_jurusan
                                      ORDER BY j.kode, FIELD(k.tingkat, 'X', 'XI', 'XII'), k.nama_kelas");
while ($k = mysqli_fetch_assoc($q_kelas_full)) {
    $kelas_for_js[] = [
        'id_kelas' => (int)$k['id_kelas'],
        'nama_kelas' => $k['nama_kelas'],
        'tingkat' => $k['tingkat'],
        'id_jurusan' => $k['id_jurusan'] ? (int)$k['id_jurusan'] : null,
        'kode_jurusan' => $k['kode_jurusan']
    ];
}

// Ambil semua kelas untuk edit modal (ambil kelas berdasarkan sesi)
$q_all_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
$all_kelas = [];
while ($k = mysqli_fetch_assoc($q_all_kelas)) {
    $all_kelas[] = $k;
}

// Pagination helper
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
        else { $active = ($halaman == $p) ? 'active' : ''; echo '<li class="page-item ' . $active . '"><a class="page-link" href="?pagi=' . $p . $link_search . '">' . $p . '</a></li>'; } }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi Ujian - Admin | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="sweetalert_delete.js"></script>
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
        #sidebar::-webkit-scrollbar { width: 4px; }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
        #sidebar.collapsed { margin-left: calc(var(--sidebar-w) * -1); }
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
        .content-area.expanded {
            margin-left: 0;
            width: 100%;
        }
        .top-nav {
            background: #fff;
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        /* ===== OVERLAY (MOBILE) ===== */
        #sidebarOverlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }
        #sidebarOverlay.active { display: block; }
        body.mobile-sidebar-open { overflow: hidden !important; }

        /* ===== RESPONSIVE MOBILE ===== */
        @media (max-width: 768px) {
            #sidebar { margin-left: calc(var(--sidebar-w) * -1); }
            #sidebar.active { margin-left: 0; }
            .content-area { margin-left: 0 !important; width: 100% !important; }
            #sidebar.collapsed { margin-left: calc(var(--sidebar-w) * -1); }
        }

        /* ===== CARDS & COMPONENTS ===== */
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .stat-card { border-radius: 16px; border: 1px solid #e2e8f0; background: #fff; padding: 20px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
        .stat-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; }
        .stat-value { font-size: 1.6rem; font-weight: 800; line-height: 1; }
        .stat-label { font-size: 0.75rem; color: #64748b; margin-top: 4px; }

        /* ===== KELAS CHECKBOX GROUP ===== */
        .kelas-checkbox-group {
            max-height: 320px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            background: #f8fafc;
        }
        .kelas-checkbox-group::-webkit-scrollbar { width: 6px; }
        .kelas-checkbox-group::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 6px; }
        .kelas-checkbox-group::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 6px; }
        .kelas-checkbox-group::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .kelas-checkbox-group .jurusan-group-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: #334155;
            padding: 6px 8px;
            margin: 6px 0 4px 0;
            background: #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .kelas-checkbox-group .tingkat-group-title {
            font-size: 0.72rem;
            font-weight: 600;
            color: #64748b;
            padding: 4px 8px;
            margin: 4px 0;
            border-left: 3px solid var(--accent);
        }
        .kelas-checkbox-group .form-check {
            padding: 3px 8px 3px 28px;
            margin-bottom: 1px;
            border-radius: 6px;
            transition: background 0.15s;
        }
        .kelas-checkbox-group .form-check:hover { background: #e2e8f0; }
        .kelas-checkbox-group .form-check-label {
            font-size: 0.8rem;
            color: #334155;
            cursor: pointer;
        }

        /* ===== QUICK SELECT BUTTONS ===== */
        .quick-select-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            font-size: 0.72rem;
            font-weight: 600;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s;
        }
        .quick-select-btn:hover {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }
        .quick-select-btn.active-qs {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        /* ===== BADGE STYLES ===== */
        .badge-umum {
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 0.68rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .badge-kejuruan {
            background: #ffedd5;
            color: #c2410c;
            font-size: 0.68rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
        }

        /* ===== JURUSAN FILTER INFO ===== */
        .jurusan-filter-info {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 1px solid #f59e0b;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.78rem;
            color: #92400e;
            display: none;
            align-items: center;
            gap: 8px;
        }
        .jurusan-filter-info.show { display: flex; }

        /* ===== MODAL BODY SCROLLABLE ===== */
        .modal-body-kelas { max-height: 400px; overflow-y: auto; }
    </style>
    <!-- Sidebar: grouping menu + mode mini -->
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=4">
    <script src="../assets/js/sidebar.js?v=4"></script>
</head>
<body>

<!-- Overlay gelap (mobile) -->
<div id="sidebarOverlay"></div>

<!-- Sidebar -->
<?php include __DIR__ . '/_sidebar.php'; ?>

<!-- Konten Utama -->
<div class="content-area" id="contentArea">
    <nav class="top-nav d-flex justify-content-between align-items-center sticky-top">
        <button class="btn btn-light" id="toggleBtn">
            <i class="bi bi-list fs-4"></i>
        </button>
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

        <!-- ALERT MESSAGE -->
        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-<?= (in_array($_GET['msg'], ['gagal','error'])) ? 'danger' : 'success'; ?> border-0 shadow-sm mb-4 d-flex align-items-center">
            <i class="bi bi-<?= (in_array($_GET['msg'], ['gagal','error'])) ? 'exclamation-triangle-fill' : 'check-circle-fill'; ?> me-2"></i>
            <?php
            $msgs = [
                'ditambah'    => 'Sesi ujian berhasil ditambahkan!',
                'diedit'      => 'Sesi ujian berhasil diperbarui!',
                'dihapus'     => 'Sesi ujian berhasil dihapus!',
                'status_diubah' => 'Status sesi ujian berhasil diubah!',
                'token_baru'  => 'Token baru berhasil digenerate: <strong>' . strtoupper($_GET['token'] ?? '') . '</strong>',
                'gagal'       => 'Gagal menyimpan data. Periksa kembali input.',
            ];
            echo $msgs[$_GET['msg']] ?? 'Operasi berhasil.';
            ?>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-0">Manajemen Sesi Ujian</h5>
                <p class="text-muted small mb-0">Kelola sesi ujian, token, dan penugasan kelas</p>
            </div>
            <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i> Tambah Sesi
            </button>
        </div>

        <!-- Statistik Cards -->
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
                            <div class="stat-label">MAPEL KEJURUAN</div>
                            <div class="stat-value mt-1" style="color:#c2410c;"><?= count($mapel_kejuruan); ?></div>
                        </div>
                        <div class="stat-icon" style="background:#ffedd5;color:#c2410c;"><i class="bi bi-mortarboard"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card card-custom p-3 mb-4">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="small fw-bold text-muted"><i class="bi bi-funnel me-1"></i> Status:</span>
                <div class="btn-group btn-group-sm">
                    <a href="?filter=semua&filter_jenis=<?= $filter_jenis; ?>" class="btn <?= ($filter_status == 'semua') ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-3">Semua</a>
                    <a href="?filter=aktif&filter_jenis=<?= $filter_jenis; ?>" class="btn <?= ($filter_status == 'aktif') ? 'btn-success' : 'btn-outline-success'; ?> rounded-pill px-3">Aktif</a>
                    <a href="?filter=nonaktif&filter_jenis=<?= $filter_jenis; ?>" class="btn <?= ($filter_status == 'nonaktif') ? 'btn-secondary' : 'btn-outline-secondary'; ?> rounded-pill px-3">Nonaktif</a>
                </div>
                <span class="small fw-bold text-muted ms-md-3"><i class="bi bi-book me-1"></i> Jenis:</span>
                <div class="btn-group btn-group-sm">
                    <a href="?filter=<?= $filter_status; ?>&filter_jenis=" class="btn <?= ($filter_jenis == '') ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-3">Semua</a>
                    <a href="?filter=<?= $filter_status; ?>&filter_jenis=umum" class="btn <?= ($filter_jenis == 'umum') ? 'btn-info' : 'btn-outline-info'; ?> rounded-pill px-3">Umum</a>
                    <a href="?filter=<?= $filter_status; ?>&filter_jenis=kejuruan" class="btn <?= ($filter_jenis == 'kejuruan') ? 'btn-warning' : 'btn-outline-warning'; ?> rounded-pill px-3">Kejuruan</a>
                </div>
            </div>
        </div>

        <!-- TABEL SESI UJIAN -->
        <div class="card card-custom overflow-hidden">
            <div class="card-header bg-white border-bottom px-4 pt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-table me-2"></i>Daftar Sesi Ujian</h6>
                    <span class="badge bg-light text-muted border px-3 py-2" style="font-size:0.78rem;">
                        <?= $jumlah_data; ?> sesi
                    </span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-3 py-3 text-center" width="5%">No</th>
                            <th width="18%">Nama Ujian</th>
                            <th width="14%">Mapel</th>
                            <th class="text-center" width="8%">Jenis</th>
                            <th class="text-center" width="8%">Kelas</th>
                            <th class="text-center d-none d-md-table-cell" width="10%">Tanggal & Jam</th>
                            <th class="text-center" width="6%">Durasi</th>
                            <th class="text-center" width="7%">Token</th>
                            <th class="text-center d-none d-md-table-cell" width="8%">Peserta</th>
                            <th class="text-center d-none d-lg-table-cell" width="7%">Rata-rata</th>
                            <th class="text-center" width="8%">Status</th>
                            <th class="text-center" width="8%">Aksi</th>
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

                                // Jenis mapel badge
                                $jenis_mapel = $s['jenis_mapel'] ?? 'umum';
                                if ($jenis_mapel === 'kejuruan') {
                                    $jenis_badge = '<span class="badge-kejuruan"><i class="bi bi-mortarboard-fill me-1"></i>Kejuruan</span>';
                                } else {
                                    $jenis_badge = '<span class="badge-umum"><i class="bi bi-book-fill me-1"></i>Umum</span>';
                                }
                        ?>
                        <tr>
                            <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                            <td>
                                <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($s['nama_ujian']); ?></div>
                                <?php if ($jenis_mapel === 'kejuruan' && $s['nama_program']): ?>
                                <small class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($s['nama_program']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark" style="font-size:0.82rem;"><?= htmlspecialchars($s['nama_mapel']); ?></div>
                                <div class="mt-1"><?= $jenis_badge; ?></div>
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
                                    $max_show = 3;
                                    $shown = 0;
                                    foreach ($kelas_arr as $kn):
                                        $kn = trim($kn);
                                        if (empty($kn)) continue;
                                        if ($shown < $max_show):
                                            $shown++;
                                    ?>
                                        <span class="badge bg-info bg-opacity-10 text-info" style="font-size:0.68rem;"><?= htmlspecialchars($kn); ?></span>
                                    <?php else: ?>
                                        <?php if ($shown == $max_show): $shown++; ?>
                                            <span class="badge bg-light text-muted" style="font-size:0.68rem;">+<?= count($kelas_arr) - $max_show; ?></span>
                                        <?php endif; break; ?>
                                    <?php endif; endforeach; ?>
                                </div>
                            </td>
                            <td class="text-center d-none d-md-table-cell">
                                <div class="text-dark" style="font-size:0.82rem;"><?= $tgl; ?></div>
                                <small class="text-muted"><?= $jam; ?> WIB</small>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-dark" style="font-size:0.85rem;"><?= $s['durasi']; ?></span>
                                <small class="text-muted"> mnt</small>
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
                                <a href="?toggle_status=<?= $s['id_sesi']; ?>&filter=<?= $filter_status; ?>&filter_jenis=<?= $filter_jenis; ?>&pagi=<?= $halaman; ?>" class="badge <?= $status_class; ?> px-3 py-2 rounded-pill text-decoration-none" style="font-size:0.75rem;" title="Klik untuk toggle status">
                                    <i class="bi <?= $status_icon; ?> me-1"></i><?= $status_text; ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button class="btn btn-sm btn-outline-info text-primary edit-btn"
                                            data-id="<?= $s['id_sesi']; ?>"
                                            data-nama="<?= htmlspecialchars($s['nama_ujian']); ?>"
                                            data-mapel="<?= $s['id_mapel']; ?>"
                                            data-jenis_ujian="<?= $s['jenis_ujian']; ?>"
                                            data-tgl="<?= $s['tgl_mulai']; ?>"
                                            data-durasi="<?= $s['durasi']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalEdit" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?regen_token=<?= $s['id_sesi']; ?>&filter=<?= $filter_status; ?>&filter_jenis=<?= $filter_jenis; ?>&pagi=<?= $halaman; ?>" class="btn btn-sm btn-outline-warning" title="Generate Token Baru" onclick="return confirmAksi('Generate Token Baru?', 'Token lama akan diganti dengan token baru.<br>Siswa yang belum mulai ujian harus menggunakan token baru.', this.href, 'Ya, Generate', '#f59e0b')">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </a>
                                    <a href="?hapus=<?= $s['id_sesi']; ?>" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="return confirmHapus('<?= htmlspecialchars(addslashes($s['nama_ujian'])); ?>', this.href)">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="12" class="text-center text-muted py-5">
                                <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-25"></i>
                                <strong>Belum ada sesi ujian</strong><br>
                                <small>Klik "Tambah Sesi" untuk membuat sesi ujian baru.</small>
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
                            <a class="page-link rounded-pill px-3 me-2" href="?filter=<?= $filter_status; ?>&filter_jenis=<?= $filter_jenis; ?>&pagi=<?= $sebelum; ?>"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        <?php
                        $link_search = "&filter=$filter_status&filter_jenis=$filter_jenis";
                        generatePagination($halaman, $total_halaman, $link_search);
                        ?>
                        <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                            <a class="page-link rounded-pill px-3 ms-2" href="?filter=<?= $filter_status; ?>&filter_jenis=<?= $filter_jenis; ?>&pagi=<?= $sesudah; ?>"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
                <div class="text-center mt-2"><small class="text-muted">Halaman <b><?= $halaman; ?></b> dari <b><?= $total_halaman; ?></b></small></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===========================================================================
     MODAL TAMBAH SESI UJIAN
     =========================================================================== -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="sesi_ujian.php" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Sesi Ujian</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="small fw-bold text-muted mb-1">NAMA UJIAN</label>
                        <input type="text" name="nama_ujian" class="form-control" placeholder="cth: UTS Matematika Kelas X" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-1">JENIS UJIAN</label>
                        <select name="jenis_ujian" class="form-select">
                            <option value="uts">UTS</option>
                            <option value="uas">UAS</option>
                            <option value="uh">UH</option>
                            <option value="quiz">Quiz</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold text-muted mb-1">MATA PELAJARAN</label>
                        <select name="id_mapel" id="tambah_id_mapel" class="form-select" required>
                            <option value="">-- Pilih Mapel --</option>
                            <?php if (!empty($mapel_umum)): ?>
                            <optgroup label="📘 Mata Pelajaran Umum">
                                <?php foreach ($mapel_umum as $m): ?>
                                <option value="<?= $m['id_mapel']; ?>"><?= htmlspecialchars($m['nama_mapel']); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                            <?php if (!empty($mapel_kejuruan)): ?>
                            <optgroup label="📗 Mata Pelajaran Kejuruan">
                                <?php foreach ($mapel_kejuruan as $m): ?>
                                <option value="<?= $m['id_mapel']; ?>"><?= htmlspecialchars($m['nama_mapel']); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="small fw-bold text-muted mb-1">TANGGAL & JAM MULAI</label>
                        <input type="datetime-local" name="tgl_mulai" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <label class="small fw-bold text-muted mb-1">DURASI (menit)</label>
                        <input type="number" name="durasi" class="form-control" min="5" max="300" value="60" required>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="small fw-bold text-muted mb-0">PILIH KELAS</label>
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="quick-select-btn" onclick="quickSelectTingkat('X', 'tambah')">Kelas X</button>
                                <button type="button" class="quick-select-btn" onclick="quickSelectTingkat('XI', 'tambah')">Kelas XI</button>
                                <button type="button" class="quick-select-btn" onclick="quickSelectTingkat('XII', 'tambah')">Kelas XII</button>
                                <button type="button" class="quick-select-btn" onclick="quickSelectAll('tambah')">Semua</button>
                                <button type="button" class="quick-select-btn" onclick="quickSelectNone('tambah')">Hapus Semua</button>
                                <button type="button" class="quick-select-btn" id="btnTambahJurusanFilter" style="display:none;" onclick="quickSelectJurusan('tambah')">
                                    <i class="bi bi-mortarboard"></i> <span id="btnTambahJurusanLabel">Jurusan</span>
                                </button>
                            </div>
                        </div>
                        <!-- Jurusan filter info -->
                        <div class="jurusan-filter-info mb-2" id="infoTambahJurusan">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>Mapel <strong id="infoTambahMapelLabel"></strong> hanya untuk jurusan <strong id="infoTambahJurusanName"></strong>. Kelas di luar jurusan ini disembunyikan.</span>
                        </div>
                        <!-- Checkbox kelas -->
                        <div class="kelas-checkbox-group" id="tambah_kelas_container">
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-arrow-up-circle me-1"></i> Pilih mata pelajaran terlebih dahulu
                            </div>
                        </div>
                        <input type="hidden" name="kelas_selected_count" id="tambah_kelas_count" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_sesi" class="btn btn-primary rounded-pill px-4" id="btnTambahSubmit" disabled>
                    <i class="bi bi-save me-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================================
     MODAL EDIT SESI UJIAN
     =========================================================================== -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="sesi_ujian.php" method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="id_sesi" id="edit_id_sesi">
            <div class="modal-header bg-warning border-0">
                <h5 class="modal-title text-dark"><i class="bi bi-pencil-square me-2"></i>Edit Sesi Ujian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="small fw-bold text-muted mb-1">NAMA UJIAN</label>
                        <input type="text" name="nama_ujian" id="edit_nama_ujian" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-1">JENIS UJIAN</label>
                        <select name="jenis_ujian" id="edit_jenis_ujian" class="form-select">
                            <option value="uts">UTS</option>
                            <option value="uas">UAS</option>
                            <option value="uh">UH</option>
                            <option value="quiz">Quiz</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold text-muted mb-1">MATA PELAJARAN</label>
                        <select name="id_mapel" id="edit_id_mapel" class="form-select" required>
                            <option value="">-- Pilih Mapel --</option>
                            <?php if (!empty($mapel_umum)): ?>
                            <optgroup label="📘 Mata Pelajaran Umum">
                                <?php foreach ($mapel_umum as $m): ?>
                                <option value="<?= $m['id_mapel']; ?>"><?= htmlspecialchars($m['nama_mapel']); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                            <?php if (!empty($mapel_kejuruan)): ?>
                            <optgroup label="📗 Mata Pelajaran Kejuruan">
                                <?php foreach ($mapel_kejuruan as $m): ?>
                                <option value="<?= $m['id_mapel']; ?>"><?= htmlspecialchars($m['nama_mapel']); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="small fw-bold text-muted mb-1">TANGGAL & JAM MULAI</label>
                        <input type="datetime-local" name="tgl_mulai" id="edit_tgl_mulai" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <label class="small fw-bold text-muted mb-1">DURASI (menit)</label>
                        <input type="number" name="durasi" id="edit_durasi" class="form-control" min="5" max="300" required>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="small fw-bold text-muted mb-0">PILIH KELAS</label>
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="quick-select-btn" onclick="quickSelectTingkat('X', 'edit')">Kelas X</button>
                                <button type="button" class="quick-select-btn" onclick="quickSelectTingkat('XI', 'edit')">Kelas XI</button>
                                <button type="button" class="quick-select-btn" onclick="quickSelectTingkat('XII', 'edit')">Kelas XII</button>
                                <button type="button" class="quick-select-btn" onclick="quickSelectAll('edit')">Semua</button>
                                <button type="button" class="quick-select-btn" onclick="quickSelectNone('edit')">Hapus Semua</button>
                                <button type="button" class="quick-select-btn" id="btnEditJurusanFilter" style="display:none;" onclick="quickSelectJurusan('edit')">
                                    <i class="bi bi-mortarboard"></i> <span id="btnEditJurusanLabel">Jurusan</span>
                                </button>
                            </div>
                        </div>
                        <!-- Jurusan filter info -->
                        <div class="jurusan-filter-info mb-2" id="infoEditJurusan">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>Mapel <strong id="infoEditMapelLabel"></strong> hanya untuk jurusan <strong id="infoEditJurusanName"></strong>. Kelas di luar jurusan ini disembunyikan.</span>
                        </div>
                        <!-- Checkbox kelas -->
                        <div class="kelas-checkbox-group" id="edit_kelas_container">
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-arrow-up-circle me-1"></i> Pilih mata pelajaran terlebih dahulu
                            </div>
                        </div>
                        <input type="hidden" name="kelas_selected_count" id="edit_kelas_count" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit_sesi" class="btn btn-warning rounded-pill px-4 text-dark" id="btnEditSubmit" disabled>
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================================
     JAVASCRIPT
     =========================================================================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===========================================================================
// DATA DARI PHP
// ===========================================================================
const mapelData = <?= json_encode($mapelData, JSON_UNESCAPED_UNICODE); ?>;
const kelasData = <?= json_encode($kelas_for_js, JSON_UNESCAPED_UNICODE); ?>;

// State for edit modal
let editSelectedKelas = [];

// ===========================================================================
// SIDEBAR TOGGLE
// ===========================================================================
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const content = document.getElementById('contentArea');
const toggle  = document.getElementById('toggleBtn');
const body    = document.body;

function isMobile() { return window.innerWidth <= 768; }

function desktopToggle() {
    const isOpen = !sidebar.classList.contains('collapsed');
    if (isOpen) {
        sidebar.classList.add('collapsed');
        content.classList.add('expanded');
    } else {
        sidebar.classList.remove('collapsed');
        content.classList.remove('expanded');
    }
}

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

toggle.addEventListener('click', function() {
    if (isMobile()) {
        sidebar.classList.contains('active') ? mobileClose() : mobileOpen();
    } else {
        desktopToggle();
    }
});

overlay.addEventListener('click', mobileClose);
document.querySelectorAll('#sidebar .nav-link').forEach(function(link) {
    link.addEventListener('click', function() { if (isMobile()) mobileClose(); });
});
window.addEventListener('resize', function() { if (!isMobile()) mobileClose(); });

// ===========================================================================
// KELAS CHECKBOX RENDERING & FILTERING
// ===========================================================================

function getMapelInfo(mapelId) {
    return mapelData.find(m => m.id_mapel === parseInt(mapelId));
}

function renderKelasCheckboxes(containerId, mapelId, selectedIds = []) {
    const container = document.getElementById(containerId);
    if (!container) return;

    // Reset container
    container.innerHTML = '';

    const mapelInfo = getMapelInfo(mapelId);
    let filteredKelas = kelasData;

    // If mapel is kejuruan, filter kelas by jurusan
    let isKejuruan = false;
    let jurusanId = null;
    let jurusanName = '';

    if (mapelInfo && mapelInfo.jenis_mapel === 'kejuruan' && mapelInfo.id_jurusan) {
        isKejuruan = true;
        jurusanId = mapelInfo.id_jurusan;
        filteredKelas = kelasData.filter(k => k.id_jurusan === jurusanId);
        jurusanName = filteredKelas.length > 0 ? filteredKelas[0].kode_jurusan : 'Jurusan';
    }

    // Update jurusan filter info and button
    const prefix = containerId.includes('tambah') ? 'Tambah' : 'Edit';
    const infoEl = document.getElementById('info' + prefix + 'Jurusan');
    const btnEl = document.getElementById('btn' + prefix + 'JurusanFilter');
    const mapelLabelEl = document.getElementById('info' + prefix + 'MapelLabel');
    const jurusanNameEl = document.getElementById('info' + prefix + 'JurusanName');
    const btnLabelEl = document.getElementById('btn' + prefix + 'JurusanLabel');

    if (isKejuruan && infoEl) {
        infoEl.classList.add('show');
        mapelLabelEl.textContent = mapelInfo.nama_mapel;
        jurusanNameEl.textContent = jurusanName;
        btnEl.style.display = 'inline-flex';
        btnLabelEl.textContent = 'Pilih ' + jurusanName;
    } else if (infoEl) {
        infoEl.classList.remove('show');
        btnEl.style.display = 'none';
    }

    if (filteredKelas.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3"><i class="bi bi-exclamation-circle me-1"></i> Tidak ada kelas tersedia</div>';
        updateSubmitButton(containerId);
        return;
    }

    // Group by jurusan then by tingkat
    let groups = {};
    filteredKelas.forEach(k => {
        let jurusanKey = k.kode_jurusan || 'Tanpa Jurusan';
        let tingkatKey = k.tingkat || 'Lainnya';
        if (!groups[jurusanKey]) groups[jurusanKey] = {};
        if (!groups[jurusanKey][tingkatKey]) groups[jurusanKey][tingkatKey] = [];
        groups[jurusanKey][tingkatKey].push(k);
    });

    // If kejuruan, only one jurusan - group directly by tingkat
    let html = '';
    const jurusanKeys = Object.keys(groups).sort();

    jurusanKeys.forEach((jk, jIdx) => {
        const tingkatKeys = Object.keys(groups[jk]).sort((a, b) => {
            const order = { 'X': 0, 'XI': 1, 'XII': 2 };
            return (order[a] || 9) - (order[b] || 9);
        });

        // Jurusan group title (only for umum mapel with multiple jurusan)
        if (!isKejuruan) {
            html += `<div class="jurusan-group-title"><i class="bi bi-building"></i> ${jk}</div>`;
        }

        tingkatKeys.forEach(tk => {
            html += `<div class="tingkat-group-title">Tingkat ${tk}</div>`;
            groups[jk][tk].forEach(k => {
                const checked = selectedIds.includes(k.id_kelas) ? 'checked' : '';
                html += `<div class="form-check">
                    <input class="form-check-input kelas-cb-${containerId}" type="checkbox" value="${k.id_kelas}" id="kelas_${containerId}_${k.id_kelas}" name="kelas[]" ${checked} onchange="updateSubmitButton('${containerId}')">
                    <label class="form-check-label" for="kelas_${containerId}_${k.id_kelas}">${k.nama_kelas}</label>
                </div>`;
            });
        });
    });

    container.innerHTML = html;
    updateSubmitButton(containerId);
}

function updateSubmitButton(containerId) {
    const prefix = containerId.includes('tambah') ? 'tambah' : 'edit';
    const checkboxes = document.querySelectorAll('.kelas-cb-' + containerId + ':checked');
    const count = checkboxes.length;
    const countEl = document.getElementById(prefix + '_kelas_count');
    const submitBtn = document.getElementById('btn' + prefix.charAt(0).toUpperCase() + prefix.slice(1) + 'Submit');
    if (countEl) countEl.value = count;
    if (submitBtn) submitBtn.disabled = (count === 0);
}

// ===========================================================================
// QUICK SELECT FUNCTIONS
// ===========================================================================

// FIX: Gunakan containerId lengkap agar selector CSS cocok dengan class di HTML
function getVisibleCheckboxes(prefix) {
    const containerId = prefix + '_kelas_container';
    return document.querySelectorAll('.kelas-cb-' + containerId + ':not(:disabled)');
}

function quickSelectTingkat(tingkat, prefix) {
    const containerId = prefix + '_kelas_container';
    const cbs = getVisibleCheckboxes(prefix);
    let checkedCount = 0;
    cbs.forEach(cb => {
        const id = parseInt(cb.value);
        const kelas = kelasData.find(k => k.id_kelas === id);
        if (kelas && kelas.tingkat === tingkat) {
            cb.checked = true;
            checkedCount++;
        }
    });
    updateSubmitButton(containerId);
    showToast(`Dipilih ${checkedCount} kelas ${tingkat}`);
}

function quickSelectAll(prefix) {
    const containerId = prefix + '_kelas_container';
    const cbs = getVisibleCheckboxes(prefix);
    cbs.forEach(cb => cb.checked = true);
    updateSubmitButton(containerId);
    showToast(`Semua ${cbs.length} kelas dipilih`);
}

function quickSelectNone(prefix) {
    const containerId = prefix + '_kelas_container';
    const cbs = getVisibleCheckboxes(prefix);
    cbs.forEach(cb => cb.checked = false);
    updateSubmitButton(containerId);
    showToast('Semua pilihan kelas dihapus');
}

function quickSelectJurusan(prefix) {
    // When kejuruan mapel is selected, all visible checkboxes are already the same jurusan
    // So this selects all of them
    quickSelectAll(prefix);
}

// ===========================================================================
// TOAST NOTIFICATION
// ===========================================================================
function showToast(message) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: message,
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true
    });
}

// ===========================================================================
// MAPEL DROPDOWN CHANGE HANDLERS
// ===========================================================================

document.getElementById('tambah_id_mapel').addEventListener('change', function() {
    const mapelId = this.value;
    if (mapelId) {
        renderKelasCheckboxes('tambah_kelas_container', mapelId);
    } else {
        document.getElementById('tambah_kelas_container').innerHTML = '<div class="text-center text-muted py-3"><i class="bi bi-arrow-up-circle me-1"></i> Pilih mata pelajaran terlebih dahulu</div>';
        document.getElementById('btnTambahSubmit').disabled = true;
        document.getElementById('infoTambahJurusan').classList.remove('show');
        document.getElementById('btnTambahJurusanFilter').style.display = 'none';
    }
});

document.getElementById('edit_id_mapel').addEventListener('change', function() {
    const mapelId = this.value;
    if (mapelId) {
        renderKelasCheckboxes('edit_kelas_container', mapelId, editSelectedKelas);
    } else {
        document.getElementById('edit_kelas_container').innerHTML = '<div class="text-center text-muted py-3"><i class="bi bi-arrow-up-circle me-1"></i> Pilih mata pelajaran terlebih dahulu</div>';
        document.getElementById('btnEditSubmit').disabled = true;
        document.getElementById('infoEditJurusan').classList.remove('show');
        document.getElementById('btnEditJurusanFilter').style.display = 'none';
    }
});

// ===========================================================================
// EDIT MODAL POPULATE
// ===========================================================================

document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        document.getElementById('edit_id_sesi').value = id;
        document.getElementById('edit_nama_ujian').value = this.dataset.nama;
        document.getElementById('edit_id_mapel').value = this.dataset.mapel;
        document.getElementById('edit_jenis_ujian').value = this.dataset.jenis_ujian;
        document.getElementById('edit_tgl_mulai').value = this.dataset.tgl;
        document.getElementById('edit_durasi').value = this.dataset.durasi;

        // Fetch current kelas for this sesi via AJAX
        fetch(`get_sesi_kelas.php?id=${id}`)
            .then(r => r.json())
            .then(data => {
                editSelectedKelas = data.kelas_ids || [];
                const mapelId = this.dataset.mapel;
                if (mapelId) {
                    renderKelasCheckboxes('edit_kelas_container', mapelId, editSelectedKelas);
                }
            })
            .catch(() => {
                editSelectedKelas = [];
                const mapelId = this.dataset.mapel;
                if (mapelId) {
                    renderKelasCheckboxes('edit_kelas_container', mapelId);
                }
            });
    });
});

// ===========================================================================
// PREVENT SUBMIT WITHOUT KELAS
// ===========================================================================
document.querySelector('#modalTambah form').addEventListener('submit', function(e) {
    const cbs = document.querySelectorAll('.kelas-cb-tambah_kelas_container:checked');
    if (cbs.length === 0) {
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih minimal satu kelas!' });
    }
});

document.querySelector('#modalEdit form').addEventListener('submit', function(e) {
    const cbs = document.querySelectorAll('.kelas-cb-edit_kelas_container:checked');
    if (cbs.length === 0) {
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih minimal satu kelas!' });
    }
});
</script>
</body>
</html>