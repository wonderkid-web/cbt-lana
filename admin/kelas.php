<?php
require_once '../config/check_admin.php';
require_once '../config/database.php';

// --- LOGIKA PROSES DATA (CRUD) ---

// 1. Tambah Kelas
if (isset($_POST['tambah_kelas'])) {
    $nama_kelas   = mysqli_real_escape_string($conn, trim($_POST['nama_kelas']));
    $id_jurusan   = (int)$_POST['id_jurusan'];
    $tingkat      = mysqli_real_escape_string($conn, $_POST['tingkat']);
    $id_konsentrasi_input = $_POST['id_konsentrasi'] ?? '';

    // Validasi
    if (empty($nama_kelas) || $id_jurusan == 0 || empty($tingkat)) {
        header("Location: kelas.php?msg=error_field");
        exit;
    }

    // Kelas X tidak boleh punya konsentrasi
    $id_konsentrasi = 'NULL';
    if ($tingkat !== 'X' && !empty($id_konsentrasi_input)) {
        $id_konsentrasi = (int)$id_konsentrasi_input;
    }

    $sql = "INSERT INTO kelas (nama_kelas, id_jurusan, id_konsentrasi, tingkat) 
            VALUES ('$nama_kelas', '$id_jurusan', $id_konsentrasi, '$tingkat')";

    if (mysqli_query($conn, $sql)) {
        header("Location: kelas.php?msg=ditambah");
    } else {
        header("Location: kelas.php?msg=gagal");
    }
    exit;
}

// 2. Edit Kelas
if (isset($_POST['edit_kelas'])) {
    $id_kelas     = (int)$_POST['id_kelas'];
    $nama_kelas   = mysqli_real_escape_string($conn, trim($_POST['nama_kelas']));
    $id_jurusan   = (int)$_POST['id_jurusan'];
    $tingkat      = mysqli_real_escape_string($conn, $_POST['tingkat']);
    $id_konsentrasi_input = $_POST['id_konsentrasi'] ?? '';

    // Validasi
    if (empty($nama_kelas) || $id_jurusan == 0 || empty($tingkat)) {
        header("Location: kelas.php?msg=error_field");
        exit;
    }

    // Kelas X tidak boleh punya konsentrasi
    $id_konsentrasi = 'NULL';
    if ($tingkat !== 'X' && !empty($id_konsentrasi_input)) {
        $id_konsentrasi = (int)$id_konsentrasi_input;
    }

    $sql = "UPDATE kelas SET 
                nama_kelas = '$nama_kelas', 
                id_jurusan = '$id_jurusan', 
                id_konsentrasi = $id_konsentrasi, 
                tingkat = '$tingkat'
            WHERE id_kelas = '$id_kelas'";

    if (mysqli_query($conn, $sql)) {
        header("Location: kelas.php?msg=diedit");
    } else {
        header("Location: kelas.php?msg=gagal");
    }
    exit;
}

// 3. Hapus Kelas
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kelas WHERE id_kelas = '$id'");
    header("Location: kelas.php?msg=dihapus");
    exit;
}

// 4. Import Kelas dari CSV
if (isset($_POST['import_kelas'])) {
    $file = $_FILES['file_csv']['tmp_name'];
    if (!is_uploaded_file($file)) {
        header("Location: kelas.php?msg=error_file");
        exit;
    }

    $handle = fopen($file, "r");
    $row = 0;
    $success = 0;
    $skip = 0;

    mysqli_begin_transaction($conn);
    try {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $row++;
            if ($row == 1) continue; // Skip header

            $nama_kelas_csv = mysqli_real_escape_string($conn, trim($data[0] ?? ''));
            $tingkat_csv    = mysqli_real_escape_string($conn, trim($data[1] ?? ''));
            $jurusan_csv    = mysqli_real_escape_string($conn, trim($data[2] ?? ''));
            $konsentrasi_csv = mysqli_real_escape_string($conn, trim($data[3] ?? ''));

            if (empty($nama_kelas_csv) || empty($tingkat_csv) || empty($jurusan_csv)) {
                $skip++;
                continue;
            }

            // Validasi tingkat
            $tingkat_valid = in_array($tingkat_csv, ['X', 'XI', 'XII']) ? $tingkat_csv : 'X';

            // Cari ID Jurusan
            $q_jurusan = mysqli_query($conn, "SELECT id_jurusan FROM jurusan WHERE nama_program = '$jurusan_csv' OR kode = '$jurusan_csv' LIMIT 1");
            $j = mysqli_fetch_assoc($q_jurusan);
            if (!$j) { $skip++; continue; }
            $id_jurusan_valid = (int)$j['id_jurusan'];

            // Cari ID Konsentrasi (hanya untuk XI/XII)
            $id_konsentrasi_valid = 'NULL';
            if ($tingkat_valid !== 'X' && !empty($konsentrasi_csv)) {
                $q_kons = mysqli_query($conn, "SELECT id_konsentrasi FROM konsentrasi WHERE (nama_konsentrasi = '$konsentrasi_csv' OR kode = '$konsentrasi_csv') AND id_jurusan = '$id_jurusan_valid' LIMIT 1");
                $kn = mysqli_fetch_assoc($q_kons);
                if ($kn) {
                    $id_konsentrasi_valid = (int)$kn['id_konsentrasi'];
                }
            }

            // Cek apakah nama_kelas sudah ada
            $q_cek = mysqli_query($conn, "SELECT id_kelas FROM kelas WHERE nama_kelas = '$nama_kelas_csv' LIMIT 1");
            if (mysqli_num_rows($q_cek) > 0) {
                $skip++;
                continue;
            }

            $sql = "INSERT INTO kelas (nama_kelas, id_jurusan, id_konsentrasi, tingkat) 
                    VALUES ('$nama_kelas_csv', '$id_jurusan_valid', $id_konsentrasi_valid, '$tingkat_valid')";
            if (mysqli_query($conn, $sql)) {
                $success++;
            } else {
                $skip++;
            }
        }
        mysqli_commit($conn);
        fclose($handle);
        header("Location: kelas.php?msg=import_sukses&jml=$success&skip=$skip");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        fclose($handle);
        header("Location: kelas.php?msg=import_gagal");
        exit;
    }
}

// --- PAGINATION ---
$limit = 5;
$halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
$mulai = ($halaman > 1) ? ($halaman * $limit) - $limit : 0;

// Filter
$filter_tingkat = isset($_GET['filter_tingkat']) ? mysqli_real_escape_string($conn, $_GET['filter_tingkat']) : '';
$filter_jurusan = isset($_GET['filter_jurusan']) ? (int)$_GET['filter_jurusan'] : 0;

// Build WHERE clause
$where = "WHERE 1=1";
if (!empty($filter_tingkat)) {
    $where .= " AND k.tingkat = '$filter_tingkat'";
}
if ($filter_jurusan > 0) {
    $where .= " AND k.id_jurusan = '$filter_jurusan'";
}

// Hitung total
$query_hitung = mysqli_query($conn, "SELECT COUNT(*) as total FROM kelas k $where");
$total_rows = mysqli_fetch_assoc($query_hitung)['total'];
$total_halaman = $total_rows ? ceil($total_rows / $limit) : 1;

// Main Query - JOIN jurusan dan konsentrasi
$query_kelas = mysqli_query($conn, "SELECT k.*, j.nama_program, j.kode AS kode_jurusan, kn.nama_konsentrasi, kn.kode AS kode_konsentrasi, k.tingkat
                                     FROM kelas k
                                     LEFT JOIN jurusan j ON k.id_jurusan = j.id_jurusan
                                     LEFT JOIN konsentrasi kn ON k.id_konsentrasi = kn.id_konsentrasi
                                     $where
                                     ORDER BY j.kode ASC, k.tingkat ASC, k.nama_kelas ASC
                                     LIMIT $mulai, $limit");

$nomor = $mulai + 1;

// --- Ambil data Jurusan untuk dropdown ---
$query_jurusan = mysqli_query($conn, "SELECT * FROM jurusan ORDER BY kode ASC, nama_program ASC");

// --- Ambil semua data Konsentrasi untuk JS ---
$konsentrasi_data = [];
$query_konsentrasi = mysqli_query($conn, "SELECT kn.*, j.nama_program 
                                          FROM konsentrasi kn 
                                          LEFT JOIN jurusan j ON kn.id_jurusan = j.id_jurusan 
                                          ORDER BY j.kode ASC, kn.nama_konsentrasi ASC");
while ($kn = mysqli_fetch_assoc($query_konsentrasi)) {
    $konsentrasi_data[] = $kn;
}

// --- Helper: generatePagination ---
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
        else { $active = ($halaman == $p) ? 'active' : ''; echo '<li class="page-item ' . $active . '"><a class="page-link rounded-pill mx-1" href="?pagi=' . $p . $link_search . '">' . $p . '</a></li>'; } }
}

// Build filter link for pagination
$link_filter = '';
if (!empty($filter_tingkat)) $link_filter .= '&filter_tingkat=' . urlencode($filter_tingkat);
if ($filter_jurusan > 0) $link_filter .= '&filter_jurusan=' . $filter_jurusan;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kelas | Admin SMK Putra Anda</title>
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

        body.mobile-sidebar-open {
            overflow: hidden !important;
        }

        /* ===== RESPONSIVE MOBILE ===== */
        @media (max-width: 768px) {
            #sidebar { 
                margin-left: calc(var(--sidebar-w) * -1);
            }
            #sidebar.active {
                margin-left: 0;
            }
            .content-area {
                margin-left: 0 !important;
                width: 100% !important;
            }
            #sidebar.collapsed {
                margin-left: calc(var(--sidebar-w) * -1);
            }
        }

        /* ===== CARDS & COMPONENTS ===== */
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }

        /* Badge Tingkat */
        .badge-tingkat-X { background: #3b82f6; color: #fff; }
        .badge-tingkat-XI { background: #16a34a; color: #fff; }
        .badge-tingkat-XII { background: #f59e0b; color: #fff; }

        /* Table tweaks */
        .table th { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 700; }
        .table td { font-size: 0.85rem; vertical-align: middle; }
    </style>
    <!-- Sidebar: grouping menu + mode mini -->
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=4">
    <script src="../assets/js/sidebar.js?v=4"></script>
</head>
<body>

<!-- Overlay gelap -->
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
        <div class="alert alert-<?= (in_array($_GET['msg'], ['gagal','error_field','error_file','import_gagal'])) ? 'danger' : 'success'; ?> border-0 shadow-sm mb-4 d-flex align-items-center">
            <i class="bi bi-<?= (in_array($_GET['msg'], ['gagal','error_field','error_file','import_gagal'])) ? 'exclamation-triangle-fill' : 'check-circle-fill'; ?> me-2"></i>
            <?php 
            $msgs = [
                'ditambah'      => 'Kelas berhasil ditambahkan!',
                'diedit'        => 'Data kelas berhasil diperbarui!',
                'dihapus'       => 'Kelas berhasil dihapus!',
                'import_sukses' => 'Berhasil mengimpor <b>'.($_GET['jml'] ?? 0).'</b> kelas.' . (($_GET['skip'] ?? 0) > 0 ? ' (' . $_GET['skip'] . ' baris dilewati/duplikat)' : ''),
                'import_gagal'  => 'Gagal mengimpor kelas. Pastikan format CSV benar.',
                'error_file'    => 'File tidak ditemukan. Silakan coba lagi.',
                'error_field'   => 'Harap isi semua field yang wajib (Nama Kelas, Jurusan, Tingkat).',
                'gagal'         => 'Gagal menyimpan data. Periksa kembali.',
            ];
            echo $msgs[$_GET['msg']] ?? 'Operasi berhasil.';
            ?>
        </div>
        <?php endif; ?>

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-building me-2"></i>Kelola Kelas</h5>
                <p class="text-muted small mb-0">Total: <strong><?= $total_rows; ?></strong> kelas</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImport">
                    <i class="bi bi-file-earmark-excel me-1"></i> Import
                </button>
                <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Kelas
                </button>
            </div>
        </div>

        <!-- TABEL KELAS -->
        <div class="card card-custom overflow-hidden">
            <!-- Filter Toolbar -->
            <div class="p-3 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <form method="GET" class="d-flex align-items-center gap-2 w-100" style="min-width: 0;">
                    <label class="small fw-bold text-muted mb-0">Filter:</label>
                    <select name="filter_tingkat" class="form-select form-select-sm" style="min-width: 120px;" onchange="this.form.submit()">
                        <option value="">Semua Tingkat</option>
                        <option value="X" <?= ($filter_tingkat === 'X') ? 'selected' : ''; ?>>X</option>
                        <option value="XI" <?= ($filter_tingkat === 'XI') ? 'selected' : ''; ?>>XI</option>
                        <option value="XII" <?= ($filter_tingkat === 'XII') ? 'selected' : ''; ?>>XII</option>
                    </select>
                    <select name="filter_jurusan" class="form-select form-select-sm" style="min-width: 180px;" onchange="this.form.submit()">
                        <option value="">Semua Jurusan</option>
                        <?php mysqli_data_seek($query_jurusan, 0); ?>
                        <?php while ($j = mysqli_fetch_assoc($query_jurusan)): ?>
                            <option value="<?= $j['id_jurusan']; ?>" <?= ($filter_jurusan == $j['id_jurusan']) ? 'selected' : ''; ?>><?= htmlspecialchars($j['nama_program']); ?> (<?= htmlspecialchars($j['kode']); ?>)</option>
                        <?php endwhile; ?>
                    </select>
                    <?php if (!empty($filter_tingkat) || $filter_jurusan > 0): ?>
                        <a href="kelas.php" class="btn btn-sm btn-outline-secondary rounded-pill">
                            <i class="bi bi-x-lg me-1"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-3 py-3 text-center" width="5%">No</th>
                            <th width="20%">Nama Kelas</th>
                            <th class="text-center" width="8%">Tingkat</th>
                            <th width="22%">Jurusan</th>
                            <th width="22%">Konsentrasi</th>
                            <th class="text-center" width="12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_rows > 0):
                            $n = $nomor;
                            while ($k = mysqli_fetch_assoc($query_kelas)):
                                $tingkat = $k['tingkat'];
                                $tingkat_badge = "badge-tingkat-" . ($tingkat ?: 'X');
                        ?>
                        <tr>
                            <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($k['nama_kelas']); ?></div>
                                <small class="text-muted"><?= htmlspecialchars($k['kode_jurusan'] ?? ''); ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $tingkat_badge; ?> rounded-pill px-3 py-2" style="font-size:0.82rem; font-weight:700;">
                                    <?= htmlspecialchars($tingkat ?: '-'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="fw-medium text-dark"><?= htmlspecialchars($k['nama_program'] ?? '-'); ?></span>
                            </td>
                            <td>
                                <?php if (!empty($k['nama_konsentrasi'])): ?>
                                    <span class="fw-medium text-dark"><?= htmlspecialchars($k['nama_konsentrasi']); ?></span>
                                    <small class="text-muted d-block">(<?= htmlspecialchars($k['kode_konsentrasi'] ?? ''); ?>)</small>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-info edit-btn" 
                                        data-id="<?= $k['id_kelas']; ?>"
                                        data-nama="<?= htmlspecialchars($k['nama_kelas']); ?>"
                                        data-jurusan="<?= $k['id_jurusan']; ?>"
                                        data-konsentrasi="<?= $k['id_konsentrasi'] ?? ''; ?>"
                                        data-tingkat="<?= htmlspecialchars($tingkat); ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="kelas.php?hapus=<?= $k['id_kelas']; ?><?= $link_filter; ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirmHapus('<?= htmlspecialchars(addslashes($k['nama_kelas'])); ?>', this.href)">
                                    <i class="bi bi-trash3"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                <strong>Belum ada data kelas</strong><br>
                                <small>Klik "Tambah Kelas" atau "Import" untuk menambahkan kelas baru.</small>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if ($total_halaman > 1): ?>
            <div class="card-footer bg-white border-0 py-3">
                <nav>
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <?php 
                        $sebelum = $halaman - 1;
                        $sesudah = $halaman + 1;
                        ?>
                        <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link rounded-pill px-3 me-2" href="?pagi=<?= $sebelum; ?><?= $link_filter; ?>"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        <?php generatePagination($halaman, $total_halaman, $link_filter); ?>
                        <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                            <a class="page-link rounded-pill px-3 ms-2" href="?pagi=<?= $sesudah; ?><?= $link_filter; ?>"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
                <div class="text-center mt-2">
                    <small class="text-muted">Halaman <b><?= $halaman; ?></b> dari <b><?= $total_halaman; ?></b></small>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info Cards -->
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <div class="card card-custom p-3 d-flex align-items-center gap-3" style="border-left: 4px solid #3b82f6;">
                    <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width:42px;height:42px;background:#dbeafe;color:#2563eb;">
                        <i class="bi bi-building fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark" style="font-size:1.2rem;"><?= $total_rows; ?></div>
                        <small class="text-muted">Total Kelas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <?php 
                mysqli_data_seek($query_jurusan, 0);
                $total_jurusan = mysqli_num_rows($query_jurusan);
                ?>
                <div class="card card-custom p-3 d-flex align-items-center gap-3" style="border-left: 4px solid #16a34a;">
                    <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width:42px;height:42px;background:#dcfce7;color:#16a34a;">
                        <i class="bi bi-diagram-3 fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark" style="font-size:1.2rem;"><?= $total_jurusan; ?></div>
                        <small class="text-muted">Program Keahlian</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom p-3 d-flex align-items-center gap-3" style="border-left: 4px solid #f59e0b;">
                    <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width:42px;height:42px;background:#fef3c7;color:#d97706;">
                        <i class="bi bi-collection fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark" style="font-size:1.2rem;"><?= count($konsentrasi_data); ?></div>
                        <small class="text-muted">Konsentrasi Keahlian</small>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- end container-fluid -->
</div><!-- end content-area -->

<!-- ========== MODAL TAMBAH KELAS ========== -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form action="kelas.php" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Kelas Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">NAMA KELAS <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kelas" class="form-control" placeholder="Contoh: X TKJ 1, XI RPL 2" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">TINGKAT <span class="text-danger">*</span></label>
                        <select name="tingkat" class="form-select" id="tambah_tingkat" required>
                            <option value="">-- Pilih Tingkat --</option>
                            <option value="X">X (Sepuluh)</option>
                            <option value="XI">XI (Sebelas)</option>
                            <option value="XII">XII (Duabelas)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">JURUSAN <span class="text-danger">*</span></label>
                        <select name="id_jurusan" class="form-select" id="tambah_jurusan" required>
                            <option value="">-- Pilih Jurusan --</option>
                            <?php mysqli_data_seek($query_jurusan, 0); ?>
                            <?php while ($j = mysqli_fetch_assoc($query_jurusan)): ?>
                                <option value="<?= $j['id_jurusan']; ?>"><?= htmlspecialchars($j['nama_program']); ?> (<?= htmlspecialchars($j['kode']); ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3" id="tambah_konsentrasi_wrapper">
                    <label class="small fw-bold text-muted mb-1">KONSENTRASI KEAHLIAN</label>
                    <select name="id_konsentrasi" class="form-select" id="tambah_konsentrasi">
                        <option value="">-- Pilih Jurusan Dulu --</option>
                    </select>
                    <small class="text-muted" id="tambah_konsentrasi_hint">
                        <i class="bi bi-info-circle me-1"></i>Pilih jurusan terlebih dahulu untuk menampilkan konsentrasi.
                    </small>
                </div>
                <div class="alert alert-info small py-2">
                    <i class="bi bi-lightbulb me-1"></i> 
                    <strong>Kelas X</strong> hanya menggunakan Program Keahlian (tidak ada Konsentrasi).<br>
                    <strong>Kelas XI & XII</strong> menggunakan Konsentrasi Keahlian.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_kelas" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-save me-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL EDIT KELAS ========== -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form action="kelas.php" method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="id_kelas" id="edit_id">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Kelas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">NAMA KELAS <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kelas" id="edit_nama" class="form-control" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">TINGKAT <span class="text-danger">*</span></label>
                        <select name="tingkat" class="form-select" id="edit_tingkat" required>
                            <option value="">-- Pilih Tingkat --</option>
                            <option value="X">X (Sepuluh)</option>
                            <option value="XI">XI (Sebelas)</option>
                            <option value="XII">XII (Duabelas)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">JURUSAN <span class="text-danger">*</span></label>
                        <select name="id_jurusan" class="form-select" id="edit_jurusan" required>
                            <option value="">-- Pilih Jurusan --</option>
                            <?php mysqli_data_seek($query_jurusan, 0); ?>
                            <?php while ($j = mysqli_fetch_assoc($query_jurusan)): ?>
                                <option value="<?= $j['id_jurusan']; ?>"><?= htmlspecialchars($j['nama_program']); ?> (<?= htmlspecialchars($j['kode']); ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3" id="edit_konsentrasi_wrapper">
                    <label class="small fw-bold text-muted mb-1">KONSENTRASI KEAHLIAN</label>
                    <select name="id_konsentrasi" class="form-select" id="edit_konsentrasi">
                        <option value="">-- Pilih Jurusan Dulu --</option>
                    </select>
                    <small class="text-muted" id="edit_konsentrasi_hint">
                        <i class="bi bi-info-circle me-1"></i>Pilih jurusan terlebih dahulu untuk menampilkan konsentrasi.
                    </small>
                </div>
                <div class="alert alert-info small py-2">
                    <i class="bi bi-lightbulb me-1"></i> 
                    <strong>Kelas X</strong> hanya menggunakan Program Keahlian (tidak ada Konsentrasi).<br>
                    <strong>Kelas XI & XII</strong> menggunakan Konsentrasi Keahlian.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit_kelas" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL IMPORT CSV ========== -->
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="kelas.php" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title"><i class="bi bi-file-earmark-excel me-2"></i>Import Kelas dari CSV</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning small py-2 mb-3">
                    <strong>Format CSV (4 kolom, pisah koma):</strong>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-bordered mb-0 small">
                            <thead class="bg-light">
                                <tr>
                                    <th>Nama Kelas</th>
                                    <th>Tingkat</th>
                                    <th>Jurusan</th>
                                    <th>Konsentrasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>X TKJ 1</td>
                                    <td>X</td>
                                    <td>Teknik Komputer dan Jaringan</td>
                                    <td>(kosongkan)</td>
                                </tr>
                                <tr>
                                    <td>XI TKJ 1</td>
                                    <td>XI</td>
                                    <td>Teknik Komputer dan Jaringan</td>
                                    <td>Teknik Jaringan</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <strong>Konsentrasi:</strong> Kosongkan untuk Kelas X. Cari berdasarkan nama atau kode konsentrasi.<br>
                        <strong>Jurusan:</strong> Cari berdasarkan nama_program atau kode jurusan.<br>
                        Baris pertama = Header (akan dilewati). Kelas duplikat akan dilewati.
                    </small>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">FILE CSV</label>
                    <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="import_kelas" class="btn btn-success rounded-pill px-4">
                    <i class="bi bi-upload me-1"></i> Import
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ============================
    // DATA KONSENTRASI (dari PHP)
    // ============================
    const konsentrasiData = <?= json_encode($konsentrasi_data); ?>;

    // ============================
    // SIDEBAR TOGGLE
    // ============================
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebarOverlay');
    const content    = document.getElementById('contentArea');
    const toggle     = document.getElementById('toggleBtn');
    const body       = document.body;

    function isMobile() {
        return window.innerWidth <= 768;
    }

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
        if (isMobile()) {
            sidebar.classList.contains('active') ? mobileClose() : mobileOpen();
        } else {
            desktopToggle();
        }
    });

    overlay.addEventListener('click', function () {
        mobileClose();
    });

    document.querySelectorAll('#sidebar .nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobile()) mobileClose();
        });
    });

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            mobileClose();
        }
    });

    // ============================
    // KONSENTRASI DROPDOWN LOGIC
    // ============================
    
    /**
     * Populate konsentrasi dropdown based on selected jurusan
     * @param {string} dropdownId - The select element ID
     * @param {int} jurusanId - The selected jurusan ID
     * @param {string} selectedKonsentrasiId - Pre-selected konsentrasi ID (for edit)
     */
    function populateKonsentrasi(dropdownId, jurusanId, selectedKonsentrasiId) {
        const select = document.getElementById(dropdownId);
        if (!select) return;

        // Clear existing options
        select.innerHTML = '';

        if (!jurusanId) {
            select.innerHTML = '<option value="">-- Pilih Jurusan Dulu --</option>';
            select.disabled = true;
            return;
        }

        // Filter konsentrasi by jurusan
        const filtered = konsentrasiData.filter(function(k) {
            return parseInt(k.id_jurusan) === parseInt(jurusanId);
        });

        if (filtered.length === 0) {
            select.innerHTML = '<option value="">-- Tidak Ada Konsentrasi --</option>';
            select.disabled = true;
            return;
        }

        select.disabled = false;
        select.innerHTML = '<option value="">-- Pilih Konsentrasi --</option>';

        filtered.forEach(function(kn) {
            const option = document.createElement('option');
            option.value = kn.id_konsentrasi;
            option.textContent = kn.nama_konsentrasi + ' (' + kn.kode + ')';
            if (selectedKonsentrasiId && parseInt(kn.id_konsentrasi) === parseInt(selectedKonsentrasiId)) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    /**
     * Toggle konsentrasi dropdown visibility based on tingkat
     * @param {string} tingkat - X, XI, or XII
     * @param {string} wrapperId - The wrapper div ID
     * @param {string} dropdownId - The select element ID
     * @param {string} hintId - The hint text ID
     */
    function toggleKonsentrasiByTingkat(tingkat, wrapperId, dropdownId, hintId) {
        const wrapper = document.getElementById(wrapperId);
        const dropdown = document.getElementById(dropdownId);
        const hint = document.getElementById(hintId);
        if (!wrapper) return;

        if (tingkat === 'X') {
            // Kelas X: hide konsentrasi
            wrapper.style.display = 'none';
            if (dropdown) dropdown.value = '';
        } else {
            // Kelas XI/XII: show konsentrasi
            wrapper.style.display = 'block';
        }
    }

    // ============================
    // TAMBAH MODAL EVENTS
    // ============================
    const tambahJurusan = document.getElementById('tambah_jurusan');
    const tambahTingkat = document.getElementById('tambah_tingkat');

    tambahJurusan.addEventListener('change', function () {
        const jurusanId = this.value;
        const tingkat = tambahTingkat.value;
        if (tingkat !== 'X') {
            populateKonsentrasi('tambah_konsentrasi', jurusanId, null);
            document.getElementById('tambah_konsentrasi_hint').style.display = (jurusanId && tingkat !== 'X') ? 'none' : 'block';
        }
    });

    tambahTingkat.addEventListener('change', function () {
        const tingkat = this.value;
        toggleKonsentrasiByTingkat(tingkat, 'tambah_konsentrasi_wrapper', 'tambah_konsentrasi', 'tambah_konsentrasi_hint');
        
        if (tingkat !== 'X') {
            populateKonsentrasi('tambah_konsentrasi', tambahJurusan.value, null);
            document.getElementById('tambah_konsentrasi_hint').style.display = (tambahJurusan.value) ? 'none' : 'block';
        }
    });

    // Reset modal on close
    document.getElementById('modalTambah').addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        document.getElementById('tambah_konsentrasi_wrapper').style.display = 'block';
        document.getElementById('tambah_konsentrasi').innerHTML = '<option value="">-- Pilih Jurusan Dulu --</option>';
        document.getElementById('tambah_konsentrasi').disabled = true;
        document.getElementById('tambah_konsentrasi_hint').style.display = 'block';
    });

    // ============================
    // EDIT MODAL EVENTS
    // ============================
    const editJurusan = document.getElementById('edit_jurusan');
    const editTingkat = document.getElementById('edit_tingkat');

    // Populate edit modal when button clicked
    document.querySelectorAll('.edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_nama').value = this.dataset.nama;
            editJurusan.value = this.dataset.jurusan;
            editTingkat.value = this.dataset.tingkat;

            const konsentrasiId = this.dataset.konsentrasi;
            const tingkat = this.dataset.tingkat;

            // Handle konsentrasi display
            toggleKonsentrasiByTingkat(tingkat, 'edit_konsentrasi_wrapper', 'edit_konsentrasi', 'edit_konsentrasi_hint');
            
            if (tingkat !== 'X' && this.dataset.jurusan) {
                populateKonsentrasi('edit_konsentrasi', this.dataset.jurusan, konsentrasiId);
                document.getElementById('edit_konsentrasi_hint').style.display = 'none';
            } else {
                document.getElementById('edit_konsentrasi_hint').style.display = 'block';
            }
        });
    });

    editJurusan.addEventListener('change', function () {
        const jurusanId = this.value;
        const tingkat = editTingkat.value;
        if (tingkat !== 'X') {
            populateKonsentrasi('edit_konsentrasi', jurusanId, null);
            document.getElementById('edit_konsentrasi_hint').style.display = (jurusanId && tingkat !== 'X') ? 'none' : 'block';
        }
    });

    editTingkat.addEventListener('change', function () {
        const tingkat = this.value;
        toggleKonsentrasiByTingkat(tingkat, 'edit_konsentrasi_wrapper', 'edit_konsentrasi', 'edit_konsentrasi_hint');
        
        if (tingkat !== 'X') {
            populateKonsentrasi('edit_konsentrasi', editJurusan.value, null);
            document.getElementById('edit_konsentrasi_hint').style.display = (editJurusan.value) ? 'none' : 'block';
        }
    });
</script>
</body>
</html>
