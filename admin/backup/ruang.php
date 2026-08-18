<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- LOGIKA PAGINATION ---
$limit = 5; // Maksimal 5 baris per halaman
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Ruang Ujian | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-bg: #0f172a; --accent: #3b82f6; }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        #sidebar { min-width: 260px; max-width: 260px; background: var(--sidebar-bg); color: #fff; min-height: 100vh; }
        .sidebar-header { padding: 25px; background: rgba(0,0,0,0.2); text-align: center; }
        .nav-link { color: rgba(255,255,255,0.7); padding: 12px 20px; display: flex; align-items: center; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: #fff; background: var(--accent); border-radius: 10px; margin: 0 10px; }
        .nav-link i { font-size: 1.2rem; margin-right: 15px; }
        .top-nav { background: #fff; padding: 15px 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
        .progress { height: 8px; border-radius: 10px; }
        .dropdown-item:hover { background-color: #f8f9fa; color: var(--accent); }
        #sidebar { 
            min-width: 260px; 
            max-width: 260px; 
            background: var(--sidebar-bg); 
            color: #fff; 
            min-height: 100vh; 
            transition: 0.3s; 
        }
        /* Responsive Mobile */
        @media (max-width: 768px) {
            #sidebar { position: fixed; z-index: 1000; margin-left: -260px; }
            #sidebar.active { margin-left: 0; }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <nav id="sidebar">
        <div class="sidebar-header">
            <h5 class="fw-bold mb-0">SMK PUTRA ANDA BINJAI</h5>
            <small class="opacity-50">Admin</small>
        </div>
        <ul class="nav flex-column p-2 mt-3">
            <li class="mb-2"><a href="dashboard.php" class="nav-link"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
            <li class="mb-2"><a href="guru.php" class="nav-link"><i class="bi bi-person-badge"></i> Data Guru</a></li>
            <li class="mb-2"><a href="siswa.php" class="nav-link"><i class="bi bi-people"></i> Data Siswa</a></li>
            <li class="mb-2"><a href="kelas.php" class="nav-link"><i class="bi bi-house-door"></i> Data Kelas</a></li>
            <li class="mb-2"><a href="ruang.php" class="nav-link active"><i class="bi bi-door-open"></i> Ruang Ujian</a></li>
            <li class="mb-2"><a href="mapel.php" class="nav-link"><i class="bi bi-book"></i> Data Mapel</a></li>
            <li class="mb-2"><a href="bank_soal.php" class="nav-link"><i class="bi bi-file-earmark-text"></i> Bank Soal</a></li>
            <li class="mb-2"><a href="sesi_ujian.php" class="nav-link"><i class="bi bi-clipboard-check"></i> Sesi Ujian</a></li>
            <li class="mb-2"><a href="laporan_full.php" class="nav-link"><i class="bi bi-bar-chart-line"></i> Rekap Nilai</a></li>
            <hr class="mx-3 opacity-25 text-white">
            <li><a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="w-100">
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
                                        <td class="px-4 fw-bold"><?= $r['nama_ruang']; ?></td>
                                        <td>
                                            <div class="d-flex justify-content-between mb-1" style="font-size: 0.75rem;">
                                                <span class="fw-bold"><?= $r['total_isi']; ?>/20</span>
                                                <span class="text-muted"><?= round($persen); ?>%</span>
                                            </div>
                                            <div class="progress"><div class="progress-bar <?= $warna_progres; ?>" style="width: <?= $persen; ?>%"></div></div>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-light text-primary border shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $r['id_ruang']; ?>"><i class="bi bi-pencil-square"></i></button>
                                            <a href="?hapus=<?= $r['id_ruang']; ?>" class="btn btn-sm btn-light text-danger border shadow-sm" onclick="return confirm('Hapus ruangan?')"><i class="bi bi-trash3"></i></a>
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
                                                        <input type="text" name="nama_ruang" class="form-control" value="<?= $r['nama_ruang']; ?>" required>
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

                        <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
                            <small class="text-muted">Halaman <?= $halaman; ?> dari <?= $total_halaman; ?></small>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?halaman=<?= $halaman - 1; ?>">Prev</a>
                                    </li>
                                    <?php for($i=1; $i<=$total_halaman; $i++): ?>
                                        <li class="page-item <?= ($halaman == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?halaman=<?= $i; ?>"><?= $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?halaman=<?= $halaman + 1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Logika Sidebar Toggle (Sama dengan Kelas.php)
    document.getElementById('toggleBtn').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>