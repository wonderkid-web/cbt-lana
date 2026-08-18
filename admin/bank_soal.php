<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// Ambil ID User Login untuk ditulis di database (siapa yang input soal)
$id_user_login = $_SESSION['id_user'] ?? 0; 

// Ambil nama admin
$q_admin = mysqli_query($conn, "SELECT username FROM users WHERE id_user = '$id_user_login' LIMIT 1");
$admin_data = mysqli_fetch_assoc($q_admin);
$admin_name = $admin_data['username'] ?? 'Admin';

// --- LOGIKA PROSES DATA (CRUD) ---

// 1. Import Soal
if (isset($_POST['import_soal'])) {
    $file = $_FILES['file_soal']['tmp_name'];
    $id_mapel_import = (int)$_POST['id_mapel_import'];
    if (!is_uploaded_file($file)) {
        header("Location: bank_soal.php?msg=error_file"); exit;
    }

    $handle = fopen($file, "r");
    $row = 0;
    $success_count = 0;
    
    mysqli_begin_transaction($conn);
    try {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row == 0) { $row++; continue; } // Skip header
            if (count($data) < 8) continue;

            $pertanyaan     = mysqli_real_escape_string($conn, trim($data[0]));
            $opsi_a         = mysqli_real_escape_string($conn, trim($data[1]));
            $opsi_b         = mysqli_real_escape_string($conn, trim($data[2]));
            $opsi_c         = mysqli_real_escape_string($conn, trim($data[3]));
            $opsi_d         = mysqli_real_escape_string($conn, trim($data[4]));
            $opsi_e         = mysqli_real_escape_string($conn, trim($data[5]));
            $kunci          = mysqli_real_escape_string($conn, trim($data[6]));
            $nama_kelas_csv = '';
            if (count($data) >= 8) {
                $nama_kelas_csv = mysqli_real_escape_string($conn, trim($data[7]));
            }

            $id_kelas_valid = null;
            $angkatan_valid = null;
            if ($nama_kelas_csv !== '') {
                if (strpos($nama_kelas_csv, 'Seluruh Kelas ') === 0) {
                    $angkatan_valid = str_replace('Seluruh Kelas ', '', $nama_kelas_csv);
                    $id_kelas_valid = null;
                } else {
                    $q_cari_kelas = mysqli_query($conn, "SELECT id_kelas FROM kelas WHERE nama_kelas = '$nama_kelas_csv' LIMIT 1");
                    $k = mysqli_fetch_assoc($q_cari_kelas);
                    $id_kelas_valid = $k['id_kelas'] ?? null;
                }
            }
            
            $kelas_value = is_numeric($id_kelas_valid) ? $id_kelas_valid : 'NULL';
            $angkatan_value = $angkatan_valid ? "'$angkatan_valid'" : 'NULL';
            $sql = "INSERT INTO bank_soal (id_user, id_mapel, id_kelas, angkatan, pertanyaan, tipe_soal, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban) 
                    VALUES ('$id_user_login', '$id_mapel_import', $kelas_value, $angkatan_value, '$pertanyaan', 'pg', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$kunci')";
            if (mysqli_query($conn, $sql)) {
                $success_count++;
            }
        }
        mysqli_commit($conn);
        fclose($handle);
        header("Location: bank_soal.php?msg=import_sukses&jml=$success_count"); exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        fclose($handle);
        header("Location: bank_soal.php?msg=import_gagal"); exit;
    }
}

// 2. Tambah Soal
if (isset($_POST['tambah_soal'])) {
    $id_mapel   = (int)$_POST['id_mapel'];
    $id_kelas_input = $_POST['id_kelas'];
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $kunci      = mysqli_real_escape_string($conn, $_POST['kunci_jawaban']);
    $opsi_a     = mysqli_real_escape_string($conn, $_POST['opsi_a']);
    $opsi_b     = mysqli_real_escape_string($conn, $_POST['opsi_b']);
    $opsi_c     = mysqli_real_escape_string($conn, $_POST['opsi_c']);
    $opsi_d     = mysqli_real_escape_string($conn, $_POST['opsi_d']);
    $opsi_e     = mysqli_real_escape_string($conn, $_POST['opsi_e']);

    if (strpos($id_kelas_input, 'seluruh_') === 0) {
        $angkatan = str_replace('seluruh_', '', $id_kelas_input);
        $id_kelas = 'NULL';
        $angkatan_value = "'$angkatan'";
    } else {
        $id_kelas = (int)$id_kelas_input;
        $angkatan_value = 'NULL';
    }

    $sql = "INSERT INTO bank_soal (id_user, id_mapel, id_kelas, angkatan, pertanyaan, tipe_soal, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban) 
            VALUES ('$id_user_login', '$id_mapel', $id_kelas, $angkatan_value, '$pertanyaan', 'pg', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$kunci')";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: bank_soal.php?msg=ditambah"); exit;
    }
}

// 3. Edit Soal
if (isset($_POST['edit_soal'])) {
    $id_soal    = (int)$_POST['id_soal'];
    $id_mapel   = (int)$_POST['id_mapel'];
    $id_kelas_input = $_POST['id_kelas'];
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $kunci      = mysqli_real_escape_string($conn, $_POST['kunci_jawaban']);
    $opsi_a     = mysqli_real_escape_string($conn, $_POST['opsi_a']);
    $opsi_b     = mysqli_real_escape_string($conn, $_POST['opsi_b']);
    $opsi_c     = mysqli_real_escape_string($conn, $_POST['opsi_c']);
    $opsi_d     = mysqli_real_escape_string($conn, $_POST['opsi_d']);
    $opsi_e     = mysqli_real_escape_string($conn, $_POST['opsi_e']);

    if (strpos($id_kelas_input, 'seluruh_') === 0) {
        $angkatan = str_replace('seluruh_', '', $id_kelas_input);
        $id_kelas = 'NULL';
        $angkatan_value = "'$angkatan'";
    } else {
        $id_kelas = (int)$id_kelas_input;
        $angkatan_value = 'NULL';
    }

    $sql = "UPDATE bank_soal SET 
                id_mapel = '$id_mapel', 
                id_kelas = $id_kelas, 
                angkatan = $angkatan_value, 
                pertanyaan = '$pertanyaan', 
                opsi_a = '$opsi_a', opsi_b = '$opsi_b', opsi_c = '$opsi_c', opsi_d = '$opsi_d', opsi_e = '$opsi_e',
                kunci_jawaban = '$kunci' 
                WHERE id_soal = '$id_soal'";
    if (mysqli_query($conn, $sql)) {
        header("Location: bank_soal.php?msg=diedit"); exit;
    }
}

// 4. Hapus Soal
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM bank_soal WHERE id_soal = '$id'");
    header("Location: bank_soal.php?msg=dihapus"); exit;
}
// 5. Hapus Semua Soal per Mapel
if (isset($_GET['hapus_semua'])) {
    $id_mapel_hapus = (int)$_GET['hapus_semua'];
    mysqli_query($conn, "DELETE FROM bank_soal WHERE id_mapel = '$id_mapel_hapus'");
    header("Location: bank_soal.php?msg=semua_dihapus"); exit;
}

// 6. Hapus Soal Terpilih (Bulk)
if (isset($_POST['hapus_terpilih'])) {
    $id_mapel_list = $_POST['id_mapel_list'] ?? [];
    if (!empty($id_mapel_list)) {
        mysqli_begin_transaction($conn);
        try {
            foreach ($id_mapel_list as $id_m) {
                $id_m = (int)$id_m;
                mysqli_query($conn, "DELETE FROM bank_soal WHERE id_mapel = '$id_m'");
            }
            mysqli_commit($conn);
            header("Location: bank_soal.php?msg=terpilih_dihapus&jml=" . count($id_mapel_list)); exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
        }
    }
    header("Location: bank_soal.php"); exit;
}

// --- PAGINATION & FILTER ---
$limit = 10;
$halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
$mulai = ($halaman > 1) ? ($halaman * $limit) - $limit : 0;

$filter_mapel = isset($_GET['filter_mapel']) ? (int)$_GET['filter_mapel'] : '';
$filter_jenis = isset($_GET['filter_jenis']) ? $_GET['filter_jenis'] : '';

// Build WHERE clauses
$where_mapel = $filter_mapel ? " AND bs.id_mapel = '$filter_mapel'" : "";
$where_jenis = "";
if ($filter_jenis === 'umum') {
    $where_jenis = " AND m.jenis_mapel = 'umum'";
} elseif ($filter_jenis === 'kejuruan') {
    $where_jenis = " AND m.jenis_mapel = 'kejuruan'";
}

$view_mapel_id = isset($_GET['view_mapel']) ? (int)$_GET['view_mapel'] : 0;
$current_mapel_name = '';
$current_mapel_jenis = '';
$current_mapel_jurusan = '';

// Hitung total soal untuk info
$query_total_questions = mysqli_query($conn, "SELECT COUNT(*) as total FROM bank_soal bs JOIN mapel m ON bs.id_mapel = m.id_mapel WHERE 1=1 $where_mapel $where_jenis");
$total_questions = mysqli_fetch_assoc($query_total_questions)['total'];

if ($view_mapel_id) {
    // View soal detail for specific mapel
    $query_mapel_info = mysqli_query($conn, "SELECT m.*, j.nama_program, j.kode AS kode_jurusan 
                                             FROM mapel m 
                                             LEFT JOIN jurusan j ON m.id_jurusan = j.id_jurusan 
                                             WHERE m.id_mapel = '$view_mapel_id' LIMIT 1");
    $mapel_row = mysqli_fetch_assoc($query_mapel_info);
    $current_mapel_name = $mapel_row['nama_mapel'] ?? '';
    $current_mapel_jenis = $mapel_row['jenis_mapel'] ?? 'umum';
    $current_mapel_jurusan = $mapel_row['kode_jurusan'] ?? '';

    $query_hitung = mysqli_query($conn, "SELECT COUNT(*) as total FROM bank_soal WHERE id_mapel = '$view_mapel_id'");
    $total_rows = mysqli_fetch_assoc($query_hitung)['total'];
    $query_soal = mysqli_query($conn, "SELECT bs.*, m.nama_mapel, m.jenis_mapel, k.nama_kelas, 
                                              CASE WHEN bs.angkatan IS NOT NULL THEN CONCAT('Seluruh Kelas ', bs.angkatan) ELSE k.nama_kelas END AS display_kelas
                                        FROM bank_soal bs
                                        LEFT JOIN mapel m ON bs.id_mapel = m.id_mapel
                                        LEFT JOIN kelas k ON bs.id_kelas = k.id_kelas
                                        WHERE bs.id_mapel = '$view_mapel_id'
                                        ORDER BY bs.id_soal DESC
                                        LIMIT $mulai, $limit");
} else {
    // Mapel list view
    $query_hitung = mysqli_query($conn, "SELECT COUNT(DISTINCT m.id_mapel) as total
                                         FROM mapel m
                                         JOIN bank_soal bs ON bs.id_mapel = m.id_mapel
                                         WHERE 1=1 $where_mapel $where_jenis");
    $total_rows = mysqli_fetch_assoc($query_hitung)['total'];
    $query_soal = mysqli_query($conn, "SELECT m.id_mapel, m.nama_mapel, m.jenis_mapel, m.id_jurusan, j.kode AS kode_jurusan, j.nama_program, COUNT(bs.id_soal) AS total_soal
                                        FROM mapel m
                                        JOIN bank_soal bs ON bs.id_mapel = m.id_mapel
                                        LEFT JOIN jurusan j ON m.id_jurusan = j.id_jurusan
                                        WHERE 1=1 $where_mapel $where_jenis
                                        GROUP BY m.id_mapel, m.nama_mapel, m.jenis_mapel, m.id_jurusan, j.kode, j.nama_program
                                        ORDER BY m.jenis_mapel ASC, total_soal DESC
                                        LIMIT $mulai, $limit");
}
$total_halaman = $total_rows ? ceil($total_rows / $limit) : 1;

// --- DATA UNTUK DROPDOWN ---

// Mapel data grouped by jenis_mapel
$mapel_umum = [];
$mapel_kejuruan = [];
$query_mapel_all = mysqli_query($conn, "SELECT m.*, j.nama_program, j.kode AS kode_jurusan 
                                        FROM mapel m 
                                        LEFT JOIN jurusan j ON m.id_jurusan = j.id_jurusan 
                                        ORDER BY m.jenis_mapel ASC, m.nama_mapel ASC");
while ($m = mysqli_fetch_assoc($query_mapel_all)) {
    if ($m['jenis_mapel'] === 'kejuruan') {
        $mapel_kejuruan[] = $m;
    } else {
        $mapel_umum[] = $m;
    }
}

// Kelas data grouped by jurusan for dynamic dropdown
$kelas_by_jurusan = [];
$kelas_all = [];
$query_all_kelas = mysqli_query($conn, "SELECT k.*, j.kode AS kode_jurusan, j.nama_program AS nama_jurusan 
                                         FROM kelas k 
                                         LEFT JOIN jurusan j ON k.id_jurusan = j.id_jurusan 
                                         ORDER BY j.kode ASC, k.nama_kelas ASC");
while ($k = mysqli_fetch_assoc($query_all_kelas)) {
    $kelas_all[] = $k;
    $jurusan_id = $k['id_jurusan'] ?? 0;
    $jurusan_key = $k['kode_jurusan'] ?? 'tanpa_jurusan';
    if (!isset($kelas_by_jurusan[$jurusan_key])) {
        $kelas_by_jurusan[$jurusan_key] = [
            'id_jurusan' => $jurusan_id,
            'nama' => $k['nama_jurusan'] ?? $jurusan_key,
            'kelas' => []
        ];
    }
    $kelas_by_jurusan[$jurusan_key]['kelas'][] = $k;
}

// Build kelas options for dropdown (all kelas, grouped by jurusan)
$kelas_options = [];
$kelas_options[] = ['id' => 'seluruh_X', 'nama' => 'Seluruh Kelas X', 'tingkat' => 'X'];
$kelas_options[] = ['id' => 'seluruh_XI', 'nama' => 'Seluruh Kelas XI', 'tingkat' => 'XI'];
$kelas_options[] = ['id' => 'seluruh_XII', 'nama' => 'Seluruh Kelas XII', 'tingkat' => 'XII'];
foreach ($kelas_all as $k) {
    $kelas_options[] = ['id' => $k['id_kelas'], 'nama' => $k['nama_kelas'], 'tingkat' => $k['tingkat'] ?? '', 'id_jurusan' => $k['id_jurusan'] ?? 0];
}

// Build mapel data for JS (jenis_mapel + id_jurusan mapping)
$mapel_js_data = [];
foreach ($mapel_umum as $m) {
    $mapel_js_data[] = ['id' => $m['id_mapel'], 'jenis' => 'umum', 'id_jurusan' => null];
}
foreach ($mapel_kejuruan as $m) {
    $mapel_js_data[] = ['id' => $m['id_mapel'], 'jenis' => 'kejuruan', 'id_jurusan' => (int)$m['id_jurusan']];
}

// Build kelas data for JS (grouped by jurusan for dynamic filtering)
$kelas_js_by_jurusan = [];
foreach ($kelas_by_jurusan as $key => $group) {
    $kelas_js_by_jurusan[] = [
        'id_jurusan' => $group['id_jurusan'],
        'kode' => $key,
        'nama' => $group['nama'],
        'kelas' => array_map(function($k) {
            return ['id' => $k['id_kelas'], 'nama' => $k['nama_kelas'], 'tingkat' => $k['tingkat'] ?? ''];
        }, $group['kelas'])
    ];
}

// Kelas data for JS (all kelas, for umum mapel)
$kelas_js_all = [];
foreach ($kelas_all as $k) {
    $kelas_js_all[] = [
        'id' => $k['id_kelas'], 
        'nama' => $k['nama_kelas'], 
        'tingkat' => $k['tingkat'] ?? '', 
        'id_jurusan' => $k['id_jurusan'] ?? 0,
        'nama_jurusan' => $k['nama_jurusan'] ?? 'Lainnya'
    ];
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
        else { $active = ($halaman == $p) ? 'active' : ''; echo '<li class="page-item ' . $active . '"><a class="page-link" href="?pagi=' . $p . $link_search . '">' . $p . '</a></li>'; } }
}

// Helper for filter link
$filter_link = '';
if ($filter_mapel) $filter_link .= "&filter_mapel=$filter_mapel";
if ($filter_jenis) $filter_link .= "&filter_jenis=$filter_jenis";
if ($view_mapel_id) $filter_link .= "&view_mapel=$view_mapel_id";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Soal Admin | SMK Putra Anda Binjai</title>
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

        /* ===== SIDEBAR ===== */
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
        .content-area.expanded { margin-left: 0; width: 100%; }
        .top-nav { 
            background: #fff; 
            padding: 15px 25px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        }

        /* ===== OVERLAY ===== */
        #sidebarOverlay {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040;
        }
        #sidebarOverlay.active { display: block; }
        body.mobile-sidebar-open { overflow: hidden !important; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            #sidebar { margin-left: calc(var(--sidebar-w) * -1); }
            #sidebar.active { margin-left: 0; }
            .content-area { margin-left: 0 !important; width: 100% !important; }
            #sidebar.collapsed { margin-left: calc(var(--sidebar-w) * -1); }
        }

        /* ===== CARDS ===== */
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }

        /* ===== BADGES ===== */
        .badge-umum { background: rgba(59,130,246,0.1); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); }
        .badge-kejuruan { background: rgba(245,158,11,0.1); color: #d97706; border: 1px solid rgba(245,158,11,0.3); }

        /* ===== MODAL SELECT ===== */
        .modal .form-select optgroup { font-weight: 700; color: #334155; }
        .modal .form-select optgroup option { font-weight: 400; color: #475569; padding-left: 1.2rem; }
    </style>
    <!-- Sidebar: grouping menu + mode mini -->
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=4">
    <script src="../assets/js/sidebar.js?v=4"></script>
</head>
<body>

<!-- Overlay -->
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
    <div class="alert alert-<?= (in_array($_GET['msg'], ['import_gagal','error_file'])) ? 'danger' : 'success'; ?> border-0 shadow-sm mb-4 d-flex align-items-center">
        <i class="bi bi-<?= (in_array($_GET['msg'], ['import_gagal','error_file'])) ? 'exclamation-triangle-fill' : 'check-circle-fill'; ?> me-2"></i>
        <?php 
         $msgs = [
    'ditambah' => 'Soal berhasil ditambahkan!',
    'diedit' => 'Soal berhasil diperbarui!',
    'dihapus' => 'Soal berhasil dihapus!',
    'semua_dihapus' => 'Semua soal pada mapel tersebut berhasil dihapus!',
    'terpilih_dihapus' => 'Berhasil menghapus soal dari <b>'.($_GET['jml'] ?? 0).'</b> mata pelajaran terpilih.',
    'import_sukses' => 'Berhasil mengimpor <b>'.($_GET['jml'] ?? 0).'</b> soal.',
    'import_gagal' => 'Gagal mengimpor soal. Pastikan format CSV benar.',
    'error_file' => 'File tidak ditemukan. Silakan coba lagi.',
];
        echo $msgs[$_GET['msg']] ?? 'Operasi berhasil.';
        ?>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-0">Bank Soal</h5>
            <p class="text-muted small mb-0">
                Total: <strong><?= $total_questions; ?></strong> soal di database
                <?php if ($view_mapel_id): ?>
                    &bull; Melihat: <strong><?= htmlspecialchars($current_mapel_name); ?></strong>
                    <span class="badge <?= ($current_mapel_jenis === 'kejuruan') ? 'badge-kejuruan' : 'badge-umum'; ?> rounded-pill px-2 py-1 ms-1" style="font-size:0.68rem;"><?= ucfirst($current_mapel_jenis); ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImport">
                <i class="bi bi-file-earmark-excel me-1"></i> Import CSV
            </button>
            <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i> Tambah Soal
            </button>
        </div>
    </div>

    <!-- TABEL SOAL -->
    <div class="card card-custom overflow-hidden">

        <?php if ($view_mapel_id): ?>
        <!-- Filter Toolbar untuk View Soal -->
        <div class="p-3 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 w-100 justify-content-end">
                <a href="bank_soal.php<?= $filter_link ? '?' . ltrim($filter_link, '&') . '&view_mapel=' : '?'; ?>" 
                   class="btn btn-sm btn-outline-secondary rounded-pill" onclick="this.href=this.href.replace(/&view_mapel=\d+/,'').replace(/\?view_mapel=\d+/,'?')">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Mapel
                </a>
            </div>
        </div>
       <?php else: ?>
<!-- Filter Toolbar + Bulk Action -->
<div class="p-3 bg-light border-bottom">
    <form method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-2" id="filterForm">
        <label class="small fw-bold text-muted mb-0 me-1"><i class="bi bi-funnel me-1"></i>Filter:</label>
        <select name="filter_jenis" class="form-select form-select-sm" style="min-width:140px;" onchange="this.form.submit(); document.getElementById('filterMapel').value='';">
            <option value="">Semua Jenis</option>
            <option value="umum" <?= ($filter_jenis === 'umum') ? 'selected' : ''; ?>>📘 Umum</option>
            <option value="kejuruan" <?= ($filter_jenis === 'kejuruan') ? 'selected' : ''; ?>>📗 Kejuruan</option>
        </select>
        <select name="filter_mapel" class="form-select form-select-sm" style="min-width:220px;" onchange="this.form.submit();" id="filterMapel">
            <option value="">Semua Mapel</option>
            <?php if (!empty($mapel_umum)): ?>
            <optgroup label="📘 Mata Pelajaran Umum">
                <?php foreach ($mapel_umum as $m): ?>
                    <option value="<?= $m['id_mapel']; ?>" <?= ($filter_mapel == $m['id_mapel']) ? 'selected' : ''; ?>><?= htmlspecialchars($m['nama_mapel']); ?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
            <?php if (!empty($mapel_kejuruan)): ?>
            <optgroup label="📗 Mata Pelajaran Kejuruan">
                <?php foreach ($mapel_kejuruan as $m): ?>
                    <option value="<?= $m['id_mapel']; ?>" <?= ($filter_mapel == $m['id_mapel']) ? 'selected' : ''; ?>><?= htmlspecialchars($m['nama_mapel']); ?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
        </select>
        <?php if ($filter_mapel || $filter_jenis): ?>
        <a href="bank_soal.php" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="bi bi-x-lg me-1"></i> Reset
        </a>
        <?php endif; ?>
    </form>
    <form method="POST" id="bulkForm" class="d-flex flex-wrap align-items-center gap-2">
        <label class="small fw-bold text-muted mb-0 me-1"><i class="bi bi-check2-square me-1"></i>Pilihan:</label>
        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="toggleSelectAll()">
            <i class="bi bi-check-all me-1"></i> Pilih Semua
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" id="btnHapusTerpilih" disabled onclick="hapusTerpilih()">
            <i class="bi bi-trash3 me-1"></i> Hapus Soal Terpilih
        </button>
        <span class="text-muted small" id="countTerpilih"></span>
    </form>
</div>
<?php endif; ?>
                </select>

                <?php if ($filter_mapel || $filter_jenis): ?>
                <a href="bank_soal.php" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-x-lg me-1"></i> Reset
                </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card-header bg-white border-bottom-0 pt-3 px-4">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-table me-2"></i>
                    <?php if ($view_mapel_id): ?>
                        Daftar Soal: <?= htmlspecialchars($current_mapel_name); ?>
                        <span class="badge <?= ($current_mapel_jenis === 'kejuruan') ? 'badge-kejuruan' : 'badge-umum'; ?> rounded-pill px-2 py-1 ms-1" style="font-size:0.68rem;">
                            <?= ucfirst($current_mapel_jenis); ?><?php if ($current_mapel_jenis === 'kejuruan' && $current_mapel_jurusan): ?> &bull; <?= htmlspecialchars($current_mapel_jurusan); ?><?php endif; ?>
                        </span>
                    <?php else: ?>
                        Daftar Mata Pelajaran
                    <?php endif; ?>
                </h6>
                <span class="badge bg-light text-muted border px-3 py-2" style="font-size:0.78rem;">
                    <?php if ($view_mapel_id): ?>
                        <?= $total_rows; ?> soal
                    <?php else: ?>
                        <?= $total_rows; ?> mapel
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <?php if ($view_mapel_id): ?>
                <!-- THEAD: Soal Detail View -->
                <thead class="bg-light">
                    <tr>
                        <th class="text-center" width="5%">No</th>
                        <th width="40%">Pertanyaan</th>
                        <th class="text-center" width="8%">Kunci</th>
                        <th class="text-center d-none d-md-table-cell" width="15%">Kelas</th>
                        <th class="text-center" width="12%">Aksi</th>
                    </tr>
                </thead>
                <?php else: ?>
                <!-- THEAD: Mapel List View -->
<thead class="bg-light">
    <tr>
        <th class="text-center" width="4%"><input type="checkbox" id="checkAll" onclick="toggleCheckAll(this)"></th>
        <th class="text-center" width="5%">No</th>
        <th width="35%">Mata Pelajaran</th>
        <th class="text-center" width="12%">Jenis</th>
        <th class="text-center" width="10%">Jumlah Soal</th>
        <th class="text-center" width="20%">Aksi</th>
    </tr>
</thead>
                <?php endif; ?>
                <tbody>
                    <?php if ($total_rows > 0):
                        $n = ($halaman > 1) ? ($halaman * $limit) - $limit + 1 : 1;
                        while ($s = mysqli_fetch_assoc($query_soal)):
                            if ($view_mapel_id):
                    ?>
                    <!-- ROW: Soal Detail -->
                    <tr>
                        <?php $nomor_soal = $n++; ?>
                        <td class="text-center px-3 fw-bold text-muted"><?= $nomor_soal; ?></td>
                        <td>
                            <div class="fw-medium text-dark" style="font-size:0.85rem; max-width:450px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($s['pertanyaan']); ?>">
                                <?= htmlspecialchars(mb_substr($s['pertanyaan'], 0, 100)) . (mb_strlen($s['pertanyaan']) > 100 ? '...' : ''); ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <code class="bg-primary bg-opacity-10 text-primary px-2 py-1 rounded fw-bold" style="font-size:0.85rem;"><?= strtoupper($s['kunci_jawaban']); ?></code>
                        </td>
                        <td class="text-center d-none d-md-table-cell">
                            <span class="badge bg-secondary text-white rounded-pill" style="font-size:0.72rem;"><?= htmlspecialchars($s['display_kelas'] ?? '-'); ?></span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-info edit-btn" 
                                    data-id="<?= $s['id_soal']; ?>"
                                    data-mapel="<?= $s['id_mapel']; ?>"
                                    data-pertanyaan="<?= htmlspecialchars($s['pertanyaan']); ?>"
                                    data-kelas="<?= $s['id_kelas'] ?? ''; ?>"
                                    data-angkatan="<?= $s['angkatan'] ?? ''; ?>"
                                    data-a="<?= htmlspecialchars($s['opsi_a']); ?>"
                                    data-b="<?= htmlspecialchars($s['opsi_b']); ?>"
                                    data-c="<?= htmlspecialchars($s['opsi_c']); ?>"
                                    data-d="<?= htmlspecialchars($s['opsi_d']); ?>"
                                    data-e="<?= htmlspecialchars($s['opsi_e']); ?>"
                                    data-kunci="<?= $s['kunci_jawaban']; ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalEdit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="bank_soal.php?view_mapel=<?= $view_mapel_id; ?>&hapus=<?= $s['id_soal']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirmHapus('Soal Nomor <?= $nomor_soal; ?>', this.href)">
                                <i class="bi bi-trash3"></i>
                            </a>
                        </td>
                    </tr>
                    <?php else: ?>
                   <!-- ROW: Mapel List -->
<tr>
    <td class="text-center px-3">
        <input type="checkbox" class="check-mapel" name="id_mapel_list[]" value="<?= $s['id_mapel']; ?>" onchange="updateCountTerpilih()">
    </td>
    <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
    <td>
        <div class="fw-bold text-dark" style="font-size:0.85rem;">
            <?= htmlspecialchars($s['nama_mapel']); ?>
        </div>
        <?php if ($s['jenis_mapel'] === 'kejuruan' && !empty($s['kode_jurusan'])): ?>
        <small class="text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($s['kode_jurusan']); ?> - <?= htmlspecialchars($s['nama_program'] ?? ''); ?></small>
        <?php endif; ?>
    </td>
    <td class="text-center">
        <?php if ($s['jenis_mapel'] === 'umum'): ?>
        <span class="badge badge-umum rounded-pill px-3 py-1" style="font-size:0.75rem;">
            <i class="bi bi-book me-1"></i>Umum
        </span>
        <?php else: ?>
        <span class="badge badge-kejuruan rounded-pill px-3 py-1" style="font-size:0.75rem;">
            <i class="bi bi-wrench-adjustable me-1"></i>Kejuruan
        </span>
        <?php endif; ?>
    </td>
    <td class="text-center">
        <span class="badge bg-primary text-white rounded-pill px-3 py-2" style="font-size:0.85rem;"><?= $s['total_soal']; ?> soal</span>
    </td>
    <td class="text-center">
        <div class="d-flex gap-1 justify-content-center">
            <a href="bank_soal.php?view_mapel=<?= $s['id_mapel']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                <i class="bi bi-eye me-1"></i> Lihat Soal
            </a>
            <a href="bank_soal.php?hapus_semua=<?= $s['id_mapel']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirmHapus('Semua Soal <?= htmlspecialchars(addslashes($s['nama_mapel'])); ?>', this.href)">
                <i class="bi bi-trash3"></i>
            </a>
        </div>
    </td>
</tr>
                    <?php endif; ?>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="<?= $view_mapel_id ? '5' : '5'; ?>" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                            <strong>
                                <?php if ($view_mapel_id): ?>
                                    Belum ada soal di mapel ini
                                <?php else: ?>
                                    Belum ada data mapel
                                <?php endif; ?>
                            </strong><br>
                            <small>
                                <?php if ($view_mapel_id): ?>
                                    Klik "Tambah Soal" atau "Import CSV" untuk menambahkan soal baru.
                                <?php else: ?>
                                    Belum ada mata pelajaran yang memiliki soal.
                                <?php endif; ?>
                            </small>
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
                    <?php 
                    $sebelum = $halaman - 1;
                    $sesudah = $halaman + 1;
                    $pagi_link = $view_mapel_id ? "&view_mapel=$view_mapel_id" : "";
                    $pagi_link .= $filter_link;
                    ?>
                    <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill px-3 me-2" href="?pagi=<?= $sebelum; ?><?= $pagi_link; ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    <?php generatePagination($halaman, $total_halaman, $pagi_link); ?>
                    <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill px-3 ms-2" href="?pagi=<?= $sesudah; ?><?= $pagi_link; ?>"><i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
            <div class="text-center mt-2"><small class="text-muted">Halaman <b><?= $halaman; ?></b> dari <b><?= $total_halaman; ?></b></small></div>
        </div>
        <?php endif; ?>
    </div>

    </div><!-- end container-fluid -->
</div><!-- end content-area -->

<!-- ========== MODAL IMPORT CSV ========== -->
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="bank_soal.php" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title"><i class="bi bi-file-earmark-excel me-2"></i>Import Soal dari CSV</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">MATA PELAJAJARAN TUJUAN</label>
                    <select name="id_mapel_import" class="form-select" id="importMapelSelect">
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
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">FILE CSV</label>
                    <input type="file" name="file_soal" accept=".csv" class="form-control" required>
                </div>
                <div class="alert alert-info small py-2 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Format CSV:</strong> Pertanyaan, Opsi A, Opsi B, Opsi C, Opsi D, Opsi E, Kunci Jawaban, Kelas (opsional)
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="import_soal" class="btn btn-success rounded-pill px-4">
                    <i class="bi bi-upload me-1"></i> Import
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL TAMBAH SOAL ========== -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="bank_soal.php" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Soal Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">MATA PELAJARAN</label>
                    <select name="id_mapel" class="form-select" id="tambahMapel" onchange="onMapelChange('tambahMapel', 'tambahKelas')">
                        <option value="">-- Pilih Mapel --</option>
                        <?php if (!empty($mapel_umum)): ?>
                        <optgroup label="📘 Mata Pelajaran Umum">
                            <?php foreach ($mapel_umum as $m): ?>
                                <option value="<?= $m['id_mapel']; ?>" data-jenis="umum" data-jurusan=""><?= htmlspecialchars($m['nama_mapel']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($mapel_kejuruan)): ?>
                        <optgroup label="📗 Mata Pelajaran Kejuruan">
                            <?php foreach ($mapel_kejuruan as $m): ?>
                                <option value="<?= $m['id_mapel']; ?>" data-jenis="kejuruan" data-jurusan="<?= $m['id_jurusan']; ?>"><?= htmlspecialchars($m['nama_mapel']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">PERTANYAAN</label>
                    <textarea name="pertanyaan" class="form-control" rows="4" placeholder="Tulis pertanyaan di sini..." required></textarea>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-12">
                        <label class="small fw-bold text-muted mb-1">KELAS</label>
                        <select name="id_kelas" class="form-select" id="tambahKelas">
                            <option value="">-- Semua Kelas --</option>
                            <option value="seluruh_X">Seluruh Kelas X</option>
                            <option value="seluruh_XI">Seluruh Kelas XI</option>
                            <option value="seluruh_XII">Seluruh Kelas XII</option>
                            <?php foreach ($kelas_all as $k): ?>
                                <option value="<?= $k['id_kelas']; ?>" data-jurusan="<?= $k['id_jurusan'] ?? 0; ?>"><?= htmlspecialchars($k['nama_kelas']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" id="tambahKelasHint"><i class="bi bi-lightbulb me-1"></i>Pilih mapel terlebih dahulu untuk memfilter kelas sesuai jurusan.</small>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI A</label>
                        <input type="text" name="opsi_a" class="form-control form-control-sm" placeholder="Jawaban opsi A">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI B</label>
                        <input type="text" name="opsi_b" class="form-control form-control-sm" placeholder="Jawaban opsi B">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI C</label>
                        <input type="text" name="opsi_c" class="form-control form-control-sm" placeholder="Jawaban opsi C">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI D</label>
                        <input type="text" name="opsi_d" class="form-control form-control-sm" placeholder="Jawaban opsi D">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI E (Opsional)</label>
                        <input type="text" name="opsi_e" class="form-control form-control-sm" placeholder="Jawaban opsi E">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">KUNCI JAWABAN</label>
                        <select name="kunci_jawaban" class="form-select form-select-sm">
                            <option value="">-- Pilih --</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_soal" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-save me-1"></i> Simpan Soal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL EDIT SOAL ========== -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="bank_soal.php" method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="id_soal" id="edit_id">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Soal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">MATA PELAJARAN</label>
                    <select name="id_mapel" class="form-select" id="editMapel" onchange="onMapelChange('editMapel', 'editKelas')">
                        <option value="">-- Pilih Mapel --</option>
                        <?php if (!empty($mapel_umum)): ?>
                        <optgroup label="📘 Mata Pelajaran Umum">
                            <?php foreach ($mapel_umum as $m): ?>
                                <option value="<?= $m['id_mapel']; ?>" data-jenis="umum" data-jurusan=""><?= htmlspecialchars($m['nama_mapel']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($mapel_kejuruan)): ?>
                        <optgroup label="📗 Mata Pelajaran Kejuruan">
                            <?php foreach ($mapel_kejuruan as $m): ?>
                                <option value="<?= $m['id_mapel']; ?>" data-jenis="kejuruan" data-jurusan="<?= $m['id_jurusan']; ?>"><?= htmlspecialchars($m['nama_mapel']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">PERTANYAAN</label>
                    <textarea name="pertanyaan" id="edit_pertanyaan" class="form-control" rows="4" required></textarea>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-12">
                        <label class="small fw-bold text-muted mb-1">KELAS</label>
                        <select name="id_kelas" id="editKelas" class="form-select">
                            <option value="">-- Semua Kelas --</option>
                            <option value="seluruh_X">Seluruh Kelas X</option>
                            <option value="seluruh_XI">Seluruh Kelas XI</option>
                            <option value="seluruh_XII">Seluruh Kelas XII</option>
                            <?php foreach ($kelas_all as $k): ?>
                                <option value="<?= $k['id_kelas']; ?>" data-jurusan="<?= $k['id_jurusan'] ?? 0; ?>"><?= htmlspecialchars($k['nama_kelas']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" id="editKelasHint"><i class="bi bi-lightbulb me-1"></i>Pilih mapel terlebih dahulu untuk memfilter kelas sesuai jurusan.</small>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI A</label>
                        <input type="text" name="opsi_a" id="edit_a" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI B</label>
                        <input type="text" name="opsi_b" id="edit_b" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI C</label>
                        <input type="text" name="opsi_c" id="edit_c" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI D</label>
                        <input type="text" name="opsi_d" id="edit_d" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI E</label>
                        <input type="text" name="opsi_e" id="edit_e" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">KUNCI JAWABAN</label>
                        <select name="kunci_jawaban" id="edit_kunci" class="form-select form-select-sm">
                            <option value="">-- Pilih --</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit_soal" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-save me-1"></i> Update Soal
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== SIDEBAR TOGGLE =====
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

toggle.addEventListener('click', function () {
    if (isMobile()) { sidebar.classList.contains('active') ? mobileClose() : mobileOpen(); }
    else { desktopToggle(); }
});
overlay.addEventListener('click', function () { mobileClose(); });
document.querySelectorAll('#sidebar .nav-link').forEach(function (link) {
    link.addEventListener('click', function () { if (isMobile()) mobileClose(); });
});
window.addEventListener('resize', function () { if (!isMobile()) mobileClose(); });

// ===== DATA FROM PHP =====
const mapelData = <?= json_encode($mapel_js_data); ?>;
const kelasAll  = <?= json_encode($kelas_js_all); ?>;
const kelasByJurusan = <?= json_encode($kelas_js_by_jurusan); ?>;

/**
 * Filter kelas dropdown berdasarkan jenis_mapel dan id_jurusan dari mapel yang dipilih.
 * Jika umum → tampilkan semua kelas (grouped by jurusan).
 * Jika kejuruan → tampilkan hanya kelas dari jurusan tersebut + seluruh tingkat.
 */
function onMapelChange(mapelSelectId, kelasSelectId) {
    const mapelSelect = document.getElementById(mapelSelectId);
    const kelasSelect = document.getElementById(kelasSelectId);
    const hintEl = document.getElementById(kelasSelectId + 'Hint');
    const selectedOpt = mapelSelect.options[mapelSelect.selectedIndex];
    const mapelId = parseInt(mapelSelect.value) || 0;

    // Find mapel data
    const mapelInfo = mapelData.find(m => m.id === mapelId);
    
    // Remember current kelas selection
    const prevKelas = kelasSelect.value;

    // Build new options
    let html = '<option value="">-- Semua Kelas --</option>';

    if (!mapelInfo) {
        // No mapel selected - show all kelas
        html += buildKelasOptions(kelasAll, 'Semua kelas');
        if (hintEl) hintEl.innerHTML = '<i class="bi bi-lightbulb me-1"></i>Pilih mapel terlebih dahulu untuk memfilter kelas sesuai jurusan.';
    } else if (mapelInfo.jenis === 'umum') {
        // Umum → all kelas
        html += buildKelasOptions(kelasAll, 'Semua jurusan');
        if (hintEl) hintEl.innerHTML = '<i class="bi bi-book me-1"></i>Mapel <strong>Umum</strong> — soal berlaku untuk semua kelas di semua jurusan.';
    } else {
        // Kejuruan → only kelas from that jurusan
        const jurusanGroup = kelasByJurusan.find(j => j.id_jurusan === mapelInfo.id_jurusan);
        if (jurusanGroup && jurusanGroup.kelas.length > 0) {
            // Add seluruh tingkat options only if there are kelas in that tingkat
            const hasX  = jurusanGroup.kelas.some(k => k.tingkat === 'X');
            const hasXI = jurusanGroup.kelas.some(k => k.tingkat === 'XI');
            const hasXII = jurusanGroup.kelas.some(k => k.tingkat === 'XII');
            if (hasX) html += '<option value="seluruh_X">Seluruh Kelas X (' + jurusanGroup.nama + ')</option>';
            if (hasXI) html += '<option value="seluruh_XI">Seluruh Kelas XI (' + jurusanGroup.nama + ')</option>';
            if (hasXII) html += '<option value="seluruh_XII">Seluruh Kelas XII (' + jurusanGroup.nama + ')</option>';
            
            jurusanGroup.kelas.forEach(k => {
                html += '<option value="' + k.id + '">' + k.nama + '</option>';
            });
            if (hintEl) hintEl.innerHTML = '<i class="bi bi-wrench-adjustable me-1"></i>Mapel <strong>Kejuruan</strong> — hanya kelas dari jurusan <strong>' + jurusanGroup.nama + '</strong>.';
        } else {
            html += '<option value="" disabled>Tidak ada kelas untuk jurusan ini</option>';
            if (hintEl) hintEl.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>Tidak ada kelas terdaftar untuk jurusan ini.';
        }
    }

    kelasSelect.innerHTML = html;

    // Restore previous selection if still valid
    const newOptions = Array.from(kelasSelect.options).map(o => o.value);
    if (newOptions.includes(prevKelas)) {
        kelasSelect.value = prevKelas;
    }
}

function buildKelasOptions(kelasList, label) {
    let html = '';
    // Seluruh Kelas X / XI / XII (untuk mapel umum, berlaku SEMUA jurusan)
    let hasX = false, hasXI = false, hasXII = false;
    kelasList.forEach(k => {
        if (k.tingkat === 'X') hasX = true;
        if (k.tingkat === 'XI') hasXI = true;
        if (k.tingkat === 'XII') hasXII = true;
    });
    if (hasX) html += '<option value="seluruh_X">Seluruh Kelas X (Semua Jurusan)</option>';
    if (hasXI) html += '<option value="seluruh_XI">Seluruh Kelas XI (Semua Jurusan)</option>';
    if (hasXII) html += '<option value="seluruh_XII">Seluruh Kelas XII (Semua Jurusan)</option>';
    html += '<option value="" disabled>─────────────────</option>';
    // Group by jurusan
    let grouped = {};
    kelasList.forEach(k => {
        let jurKey = k.id_jurusan || 'lainnya';
        if (!grouped[jurKey]) grouped[jurKey] = [];
        grouped[jurKey].push(k);
    });
    // Sort keys by jurusan name
    let sortedKeys = Object.keys(grouped).sort();
    sortedKeys.forEach(jurKey => {
        let kelasGroup = grouped[jurKey];
        html += '<optgroup label="' + kelasGroup[0].nama_jurusan + '">';
        kelasGroup.forEach(k => {
            html += '<option value="' + k.id + '">' + k.nama + '</option>';
        });
        html += '</optgroup>';
    });
    return html;
}

// ===== EDIT MODAL POPULATION =====
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('edit_id').value          = this.dataset.id;
        document.getElementById('edit_pertanyaan').value   = this.dataset.pertanyaan;
        document.getElementById('edit_a').value            = this.dataset.a;
        document.getElementById('edit_b').value            = this.dataset.b;
        document.getElementById('edit_c').value            = this.dataset.c;
        document.getElementById('edit_d').value            = this.dataset.d;
        document.getElementById('edit_e').value            = this.dataset.e;
        document.getElementById('edit_kunci').value        = this.dataset.kunci;

        // Set mapel and trigger kelas filter
        const editMapel = document.getElementById('editMapel');
        editMapel.value = this.dataset.mapel;
        onMapelChange('editMapel', 'editKelas');

        // Set kelas after filter is applied
        setTimeout(() => {
            const kelasVal = this.dataset.kelas;
            const angkatanVal = this.dataset.angkatan;
            const editKelas = document.getElementById('editKelas');
            
            if (angkatanVal) {
                editKelas.value = 'seluruh_' + angkatanVal;
            } else if (kelasVal) {
                editKelas.value = kelasVal;
            }
        }, 50);
    });
});

// ===== KELAS DROPDOWN GROUPED BY JURUSAN (initial population) =====
function populateKelasGrouped(selectId, kelasList) {
    // For initial display, group by jurusan using the data-jurusan attribute
    // This is called only for initial load; onMapelChange handles dynamic filtering
}
// ===== BULK DELETE =====
function toggleCheckAll(source) {
    const checkboxes = document.querySelectorAll('.check-mapel');
    checkboxes.forEach(cb => {
        cb.checked = source.checked;
    });
    updateCountTerpilih();
}

function toggleSelectAll() {
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.check-mapel');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => { cb.checked = !allChecked; });
    checkAll.checked = !allChecked;
    updateCountTerpilih();
}

function updateCountTerpilih() {
    const checked = document.querySelectorAll('.check-mapel:checked').length;
    const btn = document.getElementById('btnHapusTerpilih');
    const countEl = document.getElementById('countTerpilih');
    btn.disabled = (checked === 0);
    countEl.textContent = checked > 0 ? checked + ' mapel dipilih' : '';
}

function hapusTerpilih() {
    const checked = document.querySelectorAll('.check-mapel:checked');
    if (checked.length === 0) return;
    
    const count = checked.length;
    Swal.fire({
        title: 'Hapus Semua Soal?',
        html: `Anda akan menghapus <b>semua soal</b> dari <b>${count}</b> mata pelajaran terpilih.<br><small class="text-danger">Tindakan ini tidak dapat dibatalkan!</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus Semua!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('bulkForm');
            // Move checked checkboxes into the form
            checked.forEach(cb => {
                cb.name = 'id_mapel_list[]';
                form.appendChild(cb);
            });
            // Add hidden action
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'hapus_terpilih';
            input.value = '1';
            form.appendChild(input);
            form.submit();
        }
    });
}
</script>
</body>
</html>