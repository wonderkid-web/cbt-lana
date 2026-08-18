<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- LOGIKA PROSES DATA ---

// 2. Tambah Kartu RFID
if (isset($_POST['tambah'])) {
    $siswa_id = $_POST['siswa_id'];
    $uid = strtoupper(mysqli_real_escape_string($conn, trim($_POST['uid_rfid'])));

    // Cek apakah UID sudah dipakai siswa lain
    $cek_uid = mysqli_query($conn, "SELECT id FROM kartu_rfid WHERE uid_rfid = '$uid'");
    if (mysqli_num_rows($cek_uid) > 0) {
        header("Location: kartu_rfid.php?msg=uid_duplikat");
        exit;
    }

    mysqli_query($conn, "INSERT INTO kartu_rfid (siswa_id, uid_rfid, status, tanggal_aktivasi) VALUES ('$siswa_id', '$uid', 'aktif', CURDATE())");
    header("Location: kartu_rfid.php?msg=disimpan");
    exit;
}

// 3. Edit Kartu RFID
if (isset($_POST['edit_kartu'])) {
    $id_kartu = $_POST['id_kartu'];
    $uid = strtoupper(mysqli_real_escape_string($conn, trim($_POST['uid_rfid'])));

    // Cek apakah UID baru dipakai kartu lain
    $cek_uid = mysqli_query($conn, "SELECT id FROM kartu_rfid WHERE uid_rfid = '$uid' AND id != '$id_kartu'");
    if (mysqli_num_rows($cek_uid) > 0) {
        header("Location: kartu_rfid.php?msg=uid_duplikat");
        exit;
    }

    mysqli_query($conn, "UPDATE kartu_rfid SET uid_rfid = '$uid' WHERE id = '$id_kartu'");
    header("Location: kartu_rfid.php?msg=updated");
    exit;
}

// 4. Hapus Kartu RFID
if (isset($_GET['hapus']) && isset($_GET['id_kartu'])) {
    $id_kartu = $_GET['id_kartu'];
    mysqli_query($conn, "DELETE FROM kartu_rfid WHERE id = '$id_kartu'");
    header("Location: kartu_rfid.php?msg=dihapus");
    exit;
}

// --- LOGIKA PENCARIAN ---
 $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
 $where_clause = "";
if (!empty($search)) {
    $where_clause = " WHERE (s.nama_siswa LIKE '%$search%' OR s.nisn LIKE '%$search%' OR kr.uid_rfid LIKE '%$search%') ";
}

// --- LOGIKA PAGINATION ---
 $batas = 10; 
 $halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
 $halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

 $query_hitung = mysqli_query($conn, "SELECT COUNT(*) as total FROM kartu_rfid kr JOIN siswa s ON kr.siswa_id = s.id_siswa $where_clause");
 $res_hitung = mysqli_fetch_assoc($query_hitung);
 $total_data = $res_hitung['total'];
 $total_halaman = ceil($total_data / $batas);

 $query_kartu = mysqli_query($conn, "SELECT kr.*, s.nama_siswa, s.nisn, u.status as status_user 
    FROM kartu_rfid kr 
    JOIN siswa s ON kr.siswa_id = s.id_siswa 
    JOIN users u ON s.id_user = u.id_user 
    $where_clause
    ORDER BY s.nama_siswa ASC 
    LIMIT $halaman_awal, $batas");

// Query untuk dropdown siswa yang BELUM punya kartu
 $query_siswa_tanpa_kartu = mysqli_query($conn, "
    SELECT s.id_siswa, s.nisn, s.nama_siswa 
    FROM siswa s 
    LEFT JOIN kartu_rfid kr ON s.id_siswa = kr.siswa_id 
    WHERE kr.id IS NULL 
    ORDER BY s.nama_siswa ASC
");

 $nomor = $halaman_awal + 1;

// --- FUNGSI PAGINATION ---
function generatePagination($halaman, $total_halaman, $link_search) {
    $pages = []; $maxVisible = 5;
    if ($total_halaman <= $maxVisible) { for ($i = 1; $i <= $total_halaman; $i++) $pages[] = $i; }
    else {
        $pages[] = 1;
        $rangeStart = max(2, $halaman - 1); $rangeEnd = min($total_halaman - 1, $halaman + 1);
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
    <title>Kartu RFID | SMK Putra Anda</title>
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
        .badge-aktif { background: #dcfce7; color: #166534; }
        .badge-nonaktif { background: #fee2e2; color: #991b1b; }
        .uid-text { font-family: 'Courier New', Courier, monospace; letter-spacing: 1px; }
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
                <h4 class="fw-bold mb-0">Manajemen Kartu RFID</h4>
                <p class="text-muted small mb-0">Daftarkan kartu fisik dan hubungkan ke data siswa</p>
            </div>
            <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i> Daftarkan Kartu Baru
            </button>
        </div>

        <!-- NOTIFIKASI -->
        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'uid_duplikat'): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Gagal: UID Kartu tersebut sudah terdaftar pada siswa lain!
                </div>
            <?php else: ?>
                <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i> Proses Berhasil: <?= str_replace('_', ' ', $_GET['msg']); ?>!
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- PENCARIAN -->
        <div class="card card-custom mb-4 p-3">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari berdasarkan nama, NISN, atau UID..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-accent w-100 text-white shadow-sm" style="background: var(--accent);">Cari Data</button>
                </div>
            </form>
        </div>

        <!-- TABEL DATA -->
        <div class="card card-custom overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="px-4 py-3 border-0">No</th>
                            <th class="px-4 py-3 border-0">Identitas Siswa</th>
                            <th class="border-0">UID Kartu Fisik</th>
                            <th class="text-center border-0">Status Kartu</th>
                            <th class="text-center border-0">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php if(mysqli_num_rows($query_kartu) > 0): ?>
                            <?php while($k = mysqli_fetch_assoc($query_kartu)): ?>
                            <tr>
                                <td class="px-4"><?= $no++; ?></td>
                                <td class="px-4">
                                    <div class="fw-bold text-dark"><?= $k['nama_siswa']; ?></div>
                                    <small class="text-muted">NISN: <?= $k['nisn']; ?></small>
                                </td>
                                <td><span class="uid-text badge bg-light text-dark border px-3 py-2"><?= $k['uid_rfid']; ?></span></td>
                                <td class="text-center">
    <span class="badge <?= ($k['status_user'] == 'aktif') ? 'badge-aktif' : 'badge-nonaktif'; ?> p-2 px-3">
        <i class="bi <?= ($k['status_user'] == 'aktif') ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?> me-1"></i>
        <?= strtoupper($k['status_user']); ?>
    </span>
</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info btn-action me-1" 
                                            data-bs-toggle="modal" data-bs-target="#modalEdit"
                                            data-id="<?= $k['id']; ?>" data-uid="<?= $k['uid_rfid']; ?>" data-nama="<?= $k['nama_siswa']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="kartu_rfid.php?hapus=1&id_kartu=<?= $k['id']; ?><?= !empty($search) ? '&search='.$search : '' ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirmHapus('Kartu RFID milik <?= htmlspecialchars(addslashes($k['nama_siswa'])); ?>', this.href)">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada kartu yang terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <small class="text-muted">Menampilkan <b><?= mysqli_num_rows($query_kartu); ?></b> dari <b><?= $total_data; ?></b> kartu</small>
                    <?php if($total_halaman > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <?php $link_search = !empty($search) ? "&search=$search" : ""; ?>
                            <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?pagi=<?= ($halaman-1).$link_search; ?>">Prev</a></li>
                            <?php generatePagination($halaman, $total_halaman, $link_search); ?>
                            <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?pagi=<?= ($halaman+1).$link_search; ?>">Next</a></li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH KARTU -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-cpu me-2"></i>Daftarkan Kartu Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info small py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i> Hanya siswa yang belum memiliki kartu yang muncul di daftar.
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">PILIH SISWA</label>
                        <select name="siswa_id" class="form-select" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php if(mysqli_num_rows($query_siswa_tanpa_kartu) > 0): ?>
                                <?php while($s = mysqli_fetch_assoc($query_siswa_tanpa_kartu)): ?>
                                    <option value="<?= $s['id_siswa']; ?>"><?= $s['nisn']; ?> - <?= $s['nama_siswa']; ?></option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>Semua siswa sudah punya kartu</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">UID KARTU (SCAN FISIK)</label>
                        <input type="text" name="uid_rfid" class="form-control uid-text" placeholder="Contoh: D3-4A-5B-6C" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary rounded-pill px-4">Simpan Kartu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT KARTU -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Ubah UID Kartu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_kartu" id="edit_id_kartu">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">PEMILIK KARTU</label>
                        <input type="text" id="edit_nama" class="form-control" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">UID KARTU BARU</label>
                        <input type="text" name="uid_rfid" id="edit_uid" class="form-control uid-text" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_kartu" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
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
            document.getElementById('edit_id_kartu').value = button.getAttribute('data-id');
            document.getElementById('edit_uid').value = button.getAttribute('data-uid');
            document.getElementById('edit_nama').value = button.getAttribute('data-nama');
        });
    }
</script>
</body>
</html>