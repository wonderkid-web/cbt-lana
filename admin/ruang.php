<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- LOGIKA PAGINATION ---
$limit = 5; // Maksimal 5 baris per halaman
$halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
$mulai = ($halaman > 1) ? ($halaman * $limit) - $limit : 0;

$result_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM ruang_ujian");
$total_data = mysqli_fetch_assoc($result_total)['total'];
$total_halaman = ceil($total_data / $limit);

// --- CRUD LOGIC ---
if (isset($_POST['tambah_ruang'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_ruang']);
    mysqli_query($conn, "INSERT INTO ruang_ujian (nama_ruang) VALUES ('$nama')");
    header("Location: ruang.php?msg=ditambah");
    exit;
}

if (isset($_POST['edit_ruang'])) {
    $id = $_POST['id_ruang'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_ruang']);
    mysqli_query($conn, "UPDATE ruang_ujian SET nama_ruang = '$nama' WHERE id_ruang = '$id'");
    header("Location: ruang.php?msg=diedit");
    exit;
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "UPDATE siswa SET id_ruang = NULL WHERE id_ruang = '$id'");
    mysqli_query($conn, "DELETE FROM ruang_ujian WHERE id_ruang = '$id'");
    header("Location: ruang.php?msg=dihapus");
    exit;
}

// Query Data dengan Limit - Diperbaiki untuk pengurutan natural
$query_ruang = mysqli_query($conn, "SELECT r.*, 
    (SELECT COUNT(*) FROM siswa s WHERE s.id_ruang = r.id_ruang) as total_isi 
    FROM ruang_ujian r 
    ORDER BY LENGTH(r.nama_ruang) ASC, r.nama_ruang ASC 
    LIMIT $mulai, $limit");

function generatePagination($halaman, $total_halaman, $link_search) {
    $pages = [];
    $maxVisible = 5;
    if ($total_halaman <= $maxVisible) { for ($i = 1; $i <= $total_halaman; $i++) $pages[] = $i; }
    else {
        $pages[] = 1;
        $rangeStart = max(2, $halaman - 1);
        $rangeEnd = min($total_halaman - 1, $halaman + 1);
        if ($halaman <= 3) { $rangeStart = 2; $rangeEnd = min($maxVisible - 1, $total_halaman - 1); }
        elseif ($halaman >= $total_halaman - 2) { $rangeStart = max(2, $total_halaman - $maxVisible + 2); $rangeEnd = $total_halaman - 1; }
        if ($rangeStart > 2) $pages[] = '...';
        for ($i = $rangeStart; $i <= $rangeEnd; $i++) $pages[] = $i;
        if ($rangeEnd < $total_halaman - 1) $pages[] = '...';
        $pages[] = $total_halaman;
    }
    foreach ($pages as $p) {
        if ($p === '...') { echo '<li class="page-item disabled"><span class="page-link px-1">...</span></li>'; }
        else { $active = ($halaman == $p) ? 'active' : ''; echo '<li class="page-item ' . $active . '"><a class="page-link" href="?pagi=' . $p . $link_search . '">' . $p . '</a></li>'; }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Ruang Ujian | SMK Putra Anda</title>
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
        .nav-link { color: rgba(255,255,255,0.7); padding: 12px 20px; display: flex; align-items: center; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: #fff; background: var(--accent); border-radius: 10px; margin: 0 10px; }
        .nav-link i { font-size: 1.2rem; margin-right: 15px; }
        .content-area {
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w));
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
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
        .progress { height: 8px; border-radius: 10px; }
        .dropdown-item:hover { background-color: #f8f9fa; color: var(--accent); }
    </style>
    <!-- Sidebar: grouping menu + mode mini -->
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=4">
    <script src="../assets/js/sidebar.js?v=4"></script>
</head>
<body>

<div id="sidebarOverlay"></div>
<div class="d-flex">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <div class="content-area" id="contentArea">
        <nav class="top-nav d-flex justify-content-between align-items-center">
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
            <div class="mb-4">
                <h4 class="fw-bold text-dark mb-1">Data Manajemen Ruangan</h4>
                <p class="text-muted small">Kelola kapasitas dan distribusi ruang ujian siswa.</p>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Data Berhasil <strong><?= $_GET['msg']; ?></strong>!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card card-custom p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>Tambah Ruangan</h6>
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted mb-1">NAMA RUANGAN</label>
                                <input type="text" name="nama_ruang" class="form-control" placeholder="Contoh: Ruang 1" required>
                            </div>
                            <button type="submit" name="tambah_ruang" class="btn btn-primary w-100 rounded-pill">Simpan</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card card-custom overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="px-4 py-3 border-0">Nama Ruangan</th>
                                        <th class="border-0">Kapasitas (Maks 20)</th>
                                        <th class="text-center border-0">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($r = mysqli_fetch_assoc($query_ruang)): 
                                        $persen = min(($r['total_isi'] / 20) * 100, 100);
                                        $warna_progres = ($r['total_isi'] >= 20) ? 'bg-danger' : 'bg-success';
                                    ?>
                                    <tr>
                                        <td class="px-4 fw-bold"><?= htmlspecialchars($r['nama_ruang']); ?></td>
                                        <td>
                                            <div class="d-flex justify-content-between mb-1" style="font-size: 0.75rem;">
                                                <span class="fw-bold"><?= $r['total_isi']; ?>/20</span>
                                                <span class="text-muted"><?= round($persen); ?>%</span>
                                            </div>
                                            <div class="progress"><div class="progress-bar <?= $warna_progres; ?>" style="width: <?= $persen; ?>%"></div></div>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-info btn-action me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $r['id_ruang']; ?>"><i class="bi bi-pencil"></i></button>
                                            <a href="?hapus=<?= $r['id_ruang']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirmHapus('<?= htmlspecialchars(addslashes($r['nama_ruang'])); ?>', this.href)"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editModal<?= $r['id_ruang']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0">
                                                <form action="" method="POST">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h6 class="modal-title">Edit Nama Ruangan</h6>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <input type="hidden" name="id_ruang" value="<?= $r['id_ruang']; ?>">
                                                        <label class="small fw-bold mb-1">NAMA RUANGAN</label>
                                                        <input type="text" name="nama_ruang" class="form-control" value="<?= htmlspecialchars($r['nama_ruang']); ?>" required>
                                                    </div>
                                                    <div class="modal-footer border-0">
                                                        <button type="submit" name="edit_ruang" class="btn btn-primary px-4 rounded-pill">Update</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer bg-white border-0 py-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                                <small class="text-muted">Menampilkan <b><?= mysqli_num_rows($query_ruang); ?></b> dari <b><?= $total_data; ?></b> ruang</small>
                                <?php if($total_halaman > 0): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?pagi=<?= $halaman-1; ?>">Prev</a></li>
                                        <?php generatePagination($halaman, $total_halaman, ""); ?>
                                        <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?pagi=<?= $halaman+1; ?>">Next</a></li>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
    function desktopToggle() {
        const isOpen = !sidebar.classList.contains('collapsed');
        if (isOpen) { sidebar.classList.add('collapsed'); content.classList.add('expanded'); }
        else { sidebar.classList.remove('collapsed'); content.classList.remove('expanded'); }
    }
    function mobileOpen() { sidebar.classList.add('active'); overlay.classList.add('active'); body.classList.add('mobile-sidebar-open'); }
    function mobileClose() { sidebar.classList.remove('active'); overlay.classList.remove('active'); body.classList.remove('mobile-sidebar-open'); }
    toggle.addEventListener('click', function () {
        if (isMobile()) { sidebar.classList.contains('active') ? mobileClose() : mobileOpen(); }
        else { desktopToggle(); }
    });
    overlay.addEventListener('click', function () { mobileClose(); });
    document.querySelectorAll('#sidebar .nav-link').forEach(function (link) { link.addEventListener('click', function () { if (isMobile()) mobileClose(); }); });
    window.addEventListener('resize', function () { if (!isMobile()) mobileClose(); });
</script>
</body>
</html>