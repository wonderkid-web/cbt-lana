<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- LOGIKA PROSES DATA ---

// 1. Tambah Jurusan
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_program']);
    $kode = strtoupper(mysqli_real_escape_string($conn, $_POST['kode']));
    mysqli_query($conn, "INSERT INTO jurusan (nama_program, kode) VALUES ('$nama', '$kode')");
    header("Location: jurusan.php?msg=disimpan");
    exit;
}

// 2. Edit Jurusan
if (isset($_POST['edit_jurusan'])) {
    $id = $_POST['id_jurusan'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_program']);
    $kode = strtoupper(mysqli_real_escape_string($conn, $_POST['kode']));
    mysqli_query($conn, "UPDATE jurusan SET nama_program = '$nama', kode = '$kode' WHERE id_jurusan = '$id'");
    header("Location: jurusan.php?msg=updated");
    exit;
}

// 3. Hapus Jurusan
if (isset($_GET['hapus']) && isset($_GET['id_jurusan'])) {
    $id = $_GET['id_jurusan'];
    mysqli_query($conn, "DELETE FROM jurusan WHERE id_jurusan = '$id'");
    header("Location: jurusan.php?msg=dihapus");
    exit;
}

// --- LOGIKA PENCARIAN ---
 $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
 $where_clause = "";
if (!empty($search)) {
    $where_clause = " WHERE (nama_program LIKE '%$search%' OR kode LIKE '%$search%') ";
}

// --- LOGIKA PAGINATION ---
 $batas = 10; 
 $halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
 $halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

 $query_hitung = mysqli_query($conn, "SELECT COUNT(*) as total FROM jurusan $where_clause");
 $res_hitung = mysqli_fetch_assoc($query_hitung);
 $total_data = $res_hitung['total'];
 $total_halaman = ceil($total_data / $batas);

 $query_data = mysqli_query($conn, "SELECT * FROM jurusan $where_clause ORDER BY nama_program ASC LIMIT $halaman_awal, $batas");

function generatePagination($halaman, $total_halaman, $link_search) {
    $pages = []; $maxVisible = 5;
    if ($total_halaman <= $maxVisible) { for ($i = 1; $i <= $total_halaman; $i++) $pages[] = $i; }
    else {
        $pages[] = 1; $rangeStart = max(2, $halaman - 1); $rangeEnd = min($total_halaman - 1, $halaman + 1);
        if ($halaman <= 3) { $rangeStart = 2; $rangeEnd = min($maxVisible - 1, $total_halaman - 1); } 
        elseif ($halaman >= $total_halaman - 2) { $rangeStart = max(2, $total_halaman - $maxVisible + 2); $rangeEnd = $total_halaman - 1; }
        if ($rangeStart > 2) $pages[] = '...';
        for ($i = $rangeStart; $i <= $rangeEnd; $i++) $pages[] = $i;
        if ($rangeEnd < $total_halaman - 1) $pages[] = '...';
        $pages[] = $total_halaman;
    }
    foreach ($pages as $p) {
        if ($p === '...') echo '<li class="page-item disabled"><span class="page-link px-1">...</span></li>';
        else { $active = ($halaman == $p) ? 'active' : ''; echo '<li class="page-item '.$active.'"><a class="page-link" href="?pagi='.$p.$link_search.'">'.$p.'</a></li>'; }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Jurusan | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="sweetalert_delete.js"></script>
    <style>
        :root { --sidebar-bg: #0f172a; --accent: #3b82f6; --sidebar-w: 260px; }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        #sidebar { width: var(--sidebar-w); min-width: var(--sidebar-w); max-width: var(--sidebar-w); background: var(--sidebar-bg); color: #fff; height: 100vh; position: fixed; top: 0; left: 0; overflow-y: auto; transition: margin-left 0.3s ease; z-index: 1050; }
        #sidebar::-webkit-scrollbar { width: 4px; } #sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
        #sidebar.collapsed { margin-left: calc(var(--sidebar-w) * -1); }
        .sidebar-header { padding: 25px; background: rgba(0,0,0,0.2); text-align: center; }
        .nav-link { color: rgba(255,255,255,0.7); padding: 12px 20px; display: flex; align-items: center; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: #fff; background: var(--accent); border-radius: 10px; margin: 0 10px; }
        .nav-link i { font-size: 1.2rem; margin-right: 15px; }
        .content-area { margin-left: var(--sidebar-w); width: calc(100% - var(--sidebar-w)); transition: margin-left 0.3s ease, width 0.3s ease; }
        .content-area.expanded { margin-left: 0; width: 100%; }
        .top-nav { background: #fff; padding: 15px 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        #sidebarOverlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1040; }
        #sidebarOverlay.active { display: block; } body.mobile-sidebar-open { overflow: hidden !important; }
        @media (max-width: 768px) {
            #sidebar { margin-left: calc(var(--sidebar-w) * -1); } #sidebar.active { margin-left: 0; }
            .content-area { margin-left: 0 !important; width: 100% !important; } #sidebar.collapsed { margin-left: calc(var(--sidebar-w) * -1); }
        }
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
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-0">Data Jurusan</h4>
                <p class="text-muted small mb-0">Kelola program keahlian di sekolah</p>
            </div>
            <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i> Tambah Jurusan
            </button>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i> Proses Berhasil: <?= str_replace('_', ' ', $_GET['msg']); ?>!
            </div>
        <?php endif; ?>

        <div class="card card-custom mb-4 p-3">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari berdasarkan nama jurusan atau kode..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-accent w-100 text-white shadow-sm" style="background: var(--accent);">Cari Data</button>
                </div>
            </form>
        </div>

        <div class="card card-custom overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="px-4 py-3 border-0" width="50px">No</th>
                            <th class="px-4 py-3 border-0">Nama Program Keahlian</th>
                            <th class="border-0">Kode</th>
                            <th class="text-center border-0" width="120px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php if(mysqli_num_rows($query_data) > 0): ?>
                            <?php while($d = mysqli_fetch_assoc($query_data)): ?>
                            <tr>
                                <td class="px-4"><?= $no++; ?></td>
                                <td class="px-4 fw-bold text-dark"><?= $d['nama_program']; ?></td>
                                <td><span class="badge bg-light text-dark border px-3" style="font-family: monospace;"><?= $d['kode']; ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info btn-action me-1" 
                                            data-bs-toggle="modal" data-bs-target="#modalEdit"
                                            data-id="<?= $d['id_jurusan']; ?>" data-nama="<?= $d['nama_program']; ?>" data-kode="<?= $d['kode']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="jurusan.php?hapus=1&id_jurusan=<?= $d['id_jurusan']; ?><?= !empty($search) ? '&search='.$search : '' ?>" 
                                       class="btn btn-sm btn-outline-danger" onclick="return confirmHapus('<?= htmlspecialchars(addslashes($d['nama_program'])); ?>', this.href)">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">Data tidak ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <small class="text-muted">Menampilkan <b><?= mysqli_num_rows($query_data); ?></b> dari <b><?= $total_data; ?></b> data</small>
                    <?php if($total_halaman > 1): ?>
                    <nav><ul class="pagination pagination-sm mb-0">
                        <?php $link_search = !empty($search) ? "&search=$search" : ""; ?>
                        <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?pagi=<?= ($halaman-1).$link_search; ?>">Prev</a></li>
                        <?php generatePagination($halaman, $total_halaman, $link_search); ?>
                        <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?pagi=<?= ($halaman+1).$link_search; ?>">Next</a></li>
                    </ul></nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Jurusan Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">NAMA PROGRAM KEAHLIAN</label>
                        <input type="text" name="nama_program" class="form-control" placeholder="Contoh: Rekayasa Perangkat Lunak" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">KODE JURUSAN</label>
                        <input type="text" name="kode" class="form-control text-uppercase" placeholder="Contoh: RPL" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary rounded-pill px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Data Jurusan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_jurusan" id="edit_id">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">NAMA PROGRAM KEAHLIAN</label>
                        <input type="text" name="nama_program" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">KODE JURUSAN</label>
                        <input type="text" name="kode" id="edit_kode" class="form-control text-uppercase" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_jurusan" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar = document.getElementById('sidebar'); const overlay = document.getElementById('sidebarOverlay');
    const content = document.getElementById('contentArea'); const toggle = document.getElementById('toggleBtn'); const body = document.body;
    function isMobile() { return window.innerWidth <= 768; }
    function desktopToggle() { const isOpen = !sidebar.classList.contains('collapsed'); if (isOpen) { sidebar.classList.add('collapsed'); content.classList.add('expanded'); } else { sidebar.classList.remove('collapsed'); content.classList.remove('expanded'); } }
    function mobileOpen() { sidebar.classList.add('active'); overlay.classList.add('active'); body.classList.add('mobile-sidebar-open'); }
    function mobileClose() { sidebar.classList.remove('active'); overlay.classList.remove('active'); body.classList.remove('mobile-sidebar-open'); }
    toggle.addEventListener('click', function () { if (isMobile()) { sidebar.classList.contains('active') ? mobileClose() : mobileOpen(); } else { desktopToggle(); } });
    overlay.addEventListener('click', function () { mobileClose(); });
    document.querySelectorAll('#sidebar .nav-link').forEach(function (link) { link.addEventListener('click', function () { if (isMobile()) mobileClose(); }); });
    window.addEventListener('resize', function () { if (!isMobile()) mobileClose(); });

    const modalEdit = document.getElementById('modalEdit');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('edit_id').value = button.getAttribute('data-id');
            document.getElementById('edit_nama').value = button.getAttribute('data-nama');
            document.getElementById('edit_kode').value = button.getAttribute('data-kode');
        });
    }
</script>
</body>
</html>