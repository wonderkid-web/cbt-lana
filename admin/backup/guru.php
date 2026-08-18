<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

if (!isset($password_default)) { $password_default = '10211380'; }

// Ambil data Mapel untuk Dropdown
 $query_mapel_list = mysqli_query($conn, "SELECT * FROM mapel ORDER BY nama_mapel ASC");

// --- LOGIKA PROSES DATA ---

// 1. Toggle Aktivasi
if (isset($_GET['toggle_status'])) {
    $id_u = (int)$_GET['id_user'];
    mysqli_query($conn, "UPDATE users SET status = IF(status='aktif', 'nonaktif', 'aktif') WHERE id_user = '$id_u'");
    header("Location: guru.php");
    exit;
}

// 2. Aktivasi Massal
if (isset($_POST['aksi_massal'])) {
    $status_massal = $_POST['status_tujuan'];
    if ($status_massal == 'aktif' || $status_massal == 'nonaktif') {
        $status_massal = mysqli_real_escape_string($conn, $status_massal);
        mysqli_query($conn, "UPDATE users SET status = '$status_massal' WHERE role = 'guru'");
        header("Location: guru.php?msg=massal_berhasil");
        exit;
    }
}

// 3. Tambah Guru Manual
if (isset($_POST['tambah'])) {
    $nuptk = mysqli_real_escape_string($conn, $_POST['nuptk']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_guru']);
    $id_mapel = (int)$_POST['id_mapel']; 
    $email = mysqli_real_escape_string($conn, $_POST['email']); 
    
    $pass_hash = password_hash($password_default, PASSWORD_DEFAULT);

    mysqli_begin_transaction($conn);
    try {
        // 1. Simpan ke tabel users (Login)
        mysqli_query($conn, "INSERT INTO users (username, password, role, status) VALUES ('$email', '$pass_hash', 'guru', 'nonaktif')");
        $id_u = mysqli_insert_id($conn);
        
        // 2. Simpan ke tabel guru (Profil)
        // Kita simpan ID mapel saja. Nama mapel diambil lewat JOIN saat ditampilkan.
        mysqli_query($conn, "INSERT INTO guru (id_user, nuptk, nama_guru, id_mapel, email) VALUES ('$id_u', '$nuptk', '$nama', '$id_mapel', '$email')");
        
        mysqli_commit($conn);
        header("Location: guru.php?msg=disimpan");
        exit;
    } catch (Exception $e) { 
        mysqli_rollback($conn); 
        // Tampilkan error sementara agar tau salahnya dimana (bisa dihapus nanti)
        echo "Error: " . $e->getMessage(); 
        exit;
    }
}

// 4. Import CSV
if (isset($_POST['import_guru'])) {
    $file = $_FILES['file_csv']['tmp_name'];
    if ($file) {
        $handle = fopen($file, "r");
        $row = 0;
        mysqli_begin_transaction($conn);
        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($row == 0) { $row++; continue; } 
                
                // Urutan CSV: NUPTK (0), Nama (1), Mapel (2), Email (3)
                $nuptk = mysqli_real_escape_string($conn, $data[0]);
                $nama = mysqli_real_escape_string($conn, $data[1]);
                $nama_mapel_csv = mysqli_real_escape_string($conn, $data[2]);
                $email = mysqli_real_escape_string($conn, $data[3]);
                
                // Cari ID Mapel berdasarkan Nama
                $cari_mapel = mysqli_query($conn, "SELECT id_mapel FROM mapel WHERE nama_mapel = '$nama_mapel_csv'");
                $res_mapel = mysqli_fetch_assoc($cari_mapel);
                $id_mapel_csv = $res_mapel ? $res_mapel['id_mapel'] : 0; // 0 jika tidak ketemu
                
                $pass_hash = password_hash($password_default, PASSWORD_DEFAULT);

                mysqli_query($conn, "INSERT IGNORE INTO users (username, password, role, status) VALUES ('$email', '$pass_hash', 'guru', 'nonaktif')");
                
                $res_id = mysqli_query($conn, "SELECT id_user FROM users WHERE username = '$email'");
                $usr = mysqli_fetch_assoc($res_id);
                
                if ($usr) {
                    $id_u = $usr['id_user'];
                    mysqli_query($conn, "INSERT INTO guru (id_user, nuptk, nama_guru, id_mapel, email) VALUES ('$id_u', '$nuptk', '$nama', '$id_mapel_csv', '$email') 
                                        ON DUPLICATE KEY UPDATE nama_guru='$nama', id_mapel='$id_mapel_csv'");
                }
            }
            mysqli_commit($conn);
            header("Location: guru.php?msg=imported");
            exit;
        } catch (Exception $e) { 
            mysqli_rollback($conn); 
        }
    }
}

// 5. Edit Guru
if (isset($_POST['edit_guru'])) {
    $id_u = (int)$_POST['id_user'];
    $nuptk = mysqli_real_escape_string($conn, $_POST['nuptk']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_guru']);
    $id_mapel = (int)$_POST['id_mapel'];
    $email_baru = mysqli_real_escape_string($conn, $_POST['email']);

    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "UPDATE users SET username = '$email_baru' WHERE id_user = '$id_u'");
        // Update id_mapel langsung
        mysqli_query($conn, "UPDATE guru SET nuptk = '$nuptk', nama_guru = '$nama', id_mapel = '$id_mapel', email = '$email_baru' WHERE id_user = '$id_u'");
        
        mysqli_commit($conn);
        header("Location: guru.php?msg=updated");
        exit;
    } catch (Exception $e) { mysqli_rollback($conn); }
}

// 6. Hapus Guru
if (isset($_GET['hapus']) && isset($_GET['id_user'])) {
    $id_u = (int)$_GET['id_user'];
    mysqli_begin_transaction($conn);
    try {
        // Karena ada ON DELETE CASCADE di tabel guru, menghapus user akan otomatis menghapus data di guru
        mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_u'");
        mysqli_commit($conn);
        header("Location: guru.php?msg=dihapus");
        exit;
    } catch (Exception $e) { mysqli_rollback($conn); }
}

// --- PENCARIAN & PAGINATION ---
 $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// 1. Buat Filter Dasar: Hanya tampilkan role = 'guru' (Ini akan menyembunyikan Admin)
 $filter_role = "users.role = 'guru'";

// 2. Filter Pencarian (Jika ada keyword search)
 $filter_search = "";
if (!empty($search)) {
    $filter_search = " AND (guru.nama_guru LIKE '%$search%' OR guru.nuptk LIKE '%$search%' OR mapel.nama_mapel LIKE '%$search%' OR guru.email LIKE '%$search%') ";
}

// Gabungkan filter
 $where_clause = " WHERE " . $filter_role . $filter_search;

 $batas = 5; 
 $halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
 $halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

// 3. Query Hitung: Tambahkan JOIN ke tabel users untuk filter role
 $query_hitung = mysqli_query($conn, "SELECT COUNT(*) as total FROM guru 
                                     JOIN users ON guru.id_user = users.id_user 
                                     LEFT JOIN mapel ON guru.id_mapel = mapel.id_mapel 
                                     $where_clause");
 $total_data = mysqli_fetch_assoc($query_hitung)['total'];
 $total_halaman = ceil($total_data / $batas);

// 4. Query Data: Sudah ada JOIN users, pastikan filter masuk di sini
 $query_guru = mysqli_query($conn, "SELECT guru.*, users.status, mapel.nama_mapel as nama_mapel_tampil FROM guru 
                                   JOIN users ON guru.id_user = users.id_user 
                                   LEFT JOIN mapel ON guru.id_mapel = mapel.id_mapel
                                   $where_clause
                                   ORDER BY guru.nama_guru ASC 
                                   LIMIT $halaman_awal, $batas");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Guru | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-bg: #0f172a; --accent: #3b82f6; }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        #sidebar { min-width: 260px; max-width: 260px; background: var(--sidebar-bg); color: #fff; min-height: 100vh; transition: 0.3s; }
        #sidebar.active { margin-left: -260px; }
        .sidebar-header { padding: 25px; background: rgba(0,0,0,0.2); text-align: center; }
        .nav-link { color: rgba(255,255,255,0.7); padding: 12px 20px; display: flex; align-items: center; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: #fff; background: var(--accent); border-radius: 10px; margin: 0 10px; }
        .nav-link i { font-size: 1.2rem; margin-right: 15px; }
        .top-nav { background: #fff; padding: 15px 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
        .badge-aktif { background: #dcfce7; color: #166534; }
        .badge-nonaktif { background: #fee2e2; color: #991b1b; }
        .btn-action { padding: 5px 10px; font-size: 13px; }
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
            <li class="mb-2"><a href="guru.php" class="nav-link active"><i class="bi bi-person-badge"></i> Data Guru</a></li>
            <li class="mb-2"><a href="siswa.php" class="nav-link"><i class="bi bi-people"></i> Data Siswa</a></li>
            <li class="mb-2"><a href="kelas.php" class="nav-link"><i class="bi bi-house-door"></i> Data Kelas</a></li>
            <li class="mb-2"><a href="ruang.php" class="nav-link"><i class="bi bi-door-open"></i> Ruang Ujian</a></li>
            <li class="mb-2"><a href="mapel.php" class="nav-link"><i class="bi bi-book"></i> Data Mapel</a></li>
            <li class="mb-2"><a href="bank_soal.php" class="nav-link"><i class="bi bi-file-earmark-text"></i> Bank Soal</a></li>
            <li class="mb-2"><a href="sesi_ujian.php" class="nav-link"><i class="bi bi-clipboard-check"></i> Sesi Ujian</a></li>
            <li class="mb-2"><a href="laporan_full.php" class="nav-link"><i class="bi bi-bar-chart-line"></i> Rekap Nilai</a></li>
            <hr class="mx-3 opacity-25 text-white">
            <li><a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="content-area w-100">
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
            <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Berhasil!</strong> Data guru berhasil diperbarui.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Data Manajemen Guru</h4>
                    <p class="text-muted small mb-0">Kelola akun pengajar dan status aktivasi login</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImport">
                        <i class="bi bi-file-earmark-excel me-1"></i> Import
                    </button>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-primary btn-sm rounded-pill px-3 dropdown-toggle shadow-sm" data-bs-toggle="dropdown">
                            Aksi Massal
                        </button>
                        <ul class="dropdown-menu border-0 shadow">
                            <li><form action="" method="POST"><input type="hidden" name="status_tujuan" value="aktif"><button type="submit" name="aksi_massal" class="dropdown-item text-success">Aktifkan Semua Login</button></form></li>
                            <li><form action="" method="POST"><input type="hidden" name="status_tujuan" value="nonaktif"><button type="submit" name="aksi_massal" class="dropdown-item text-danger">Matikan Semua Login</button></form></li>
                        </ul>
                    </div>

                    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="bi bi-plus-lg me-1"></i> Tambah
                    </button>
                </div>
            </div>

            <div class="card card-custom mb-4 p-3">
                <form method="GET" action="" class="row g-2">
                    <div class="col-md-10">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari nama, NUPTK, mapel, atau email..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 shadow-sm" style="background: var(--accent);">Cari Data</button>
                    </div>
                </form>
            </div>

            <div class="card card-custom overflow-hidden shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="px-4 border-0 small" style="width: 50px;">NO</th>
                                <th class="border-0 small">IDENTITAS GURU</th>
                                <th class="border-0 small">USERNAME (EMAIL)</th>
                                <th class="border-0 small">MATA PELAJARAN</th>
                                <th class="text-center border-0 small">STATUS</th>
                                <th class="text-center border-0 small">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = $halaman_awal + 1;
                            if(mysqli_num_rows($query_guru) > 0) {
                                while($g = mysqli_fetch_assoc($query_guru)): 
                            ?>
                            <tr>
                                <td class="px-4 text-muted fw-bold"><?= $no++; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($g['nama_guru']); ?></div>
                                    <small class="text-muted">NUPTK: <?= htmlspecialchars($g['nuptk']); ?></small>
                                </td>
                                <td>
                                    <div class="text-primary small fw-bold"><?= htmlspecialchars($g['email']); ?></div>
                                    <code class="x-small text-muted" style="font-size: 10px;">Pass: <?= $password_default ?></code>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($g['nama_mapel_tampil'] ?? '-'); ?></span></td>
                                <td class="text-center">
                                    <a href="?toggle_status=true&id_user=<?= $g['id_user']; ?>" class="text-decoration-none">
                                        <span class="badge <?= ($g['status'] == 'aktif') ? 'badge-aktif' : 'badge-nonaktif'; ?> p-2" style="cursor:pointer;">
                                            <?= strtoupper($g['status']); ?>
                                        </span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info btn-action me-1" 
                                        data-bs-toggle="modal" data-bs-target="#modalEdit"
                                        data-id="<?= $g['id_user']; ?>"
                                        data-nuptk="<?= $g['nuptk']; ?>"
                                        data-nama="<?= $g['nama_guru']; ?>"
                                        data-idmapel="<?= $g['id_mapel']; ?>" 
                                        data-email="<?= $g['email']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?hapus=true&id_user=<?= $g['id_user']; ?>" class="btn btn-sm btn-outline-danger btn-action" onclick="return confirm('Yakin hapus data ini?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; } else { echo "<tr><td colspan='6' class='text-center text-muted py-4'>Data tidak ditemukan.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="p-3 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted">Menampilkan <?= min($batas, $total_data) ?> dari <?= $total_data ?> data</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= ($halaman == 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagi=<?= $halaman-1 ?>&search=<?= $search ?>">Prev</a>
                            </li>
                            <?php for($i = 1; $i <= $total_halaman; $i++): ?>
                                <li class="page-item <?= ($i == $halaman) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagi=<?= $i ?>&search=<?= $search ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($halaman == $total_halaman) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagi=<?= $halaman+1 ?>&search=<?= $search ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Tambah Guru Manual</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                     <div class="alert alert-warning border-0 small py-2">
                        <strong>Info:</strong> Email yang dimasukkan akan menjadi <strong>Username</strong> untuk login. Password default: <code><?= $password_default ?></code>.
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">NUPTK</label>
                        <input type="text" name="nuptk" class="form-control" required placeholder="Masukkan NUPTK">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">NAMA LENGKAP</label>
                        <input type="text" name="nama_guru" class="form-control" required placeholder="Nama Lengkap">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">MATA PELAJARAN</label>
                        <select name="id_mapel" class="form-select" required>
                            <option value="">-- Pilih Mapel --</option>
                            <?php while($m = mysqli_fetch_assoc($query_mapel_list)): ?>
                                <option value="<?= $m['id_mapel']; ?>"><?= $m['nama_mapel']; ?></option>
                            <?php endwhile; ?>
                            <?php mysqli_data_seek($query_mapel_list, 0); ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">EMAIL (USERNAME)</label>
                        <input type="email" name="email" class="form-control" required placeholder="contoh@smkputraanda.sch.id">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="tambah" class="btn btn-primary rounded-pill px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Data Guru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_user" id="edit_id_user">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">NUPTK</label>
                        <input type="text" name="nuptk" id="edit_nuptk" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">NAMA LENGKAP</label>
                        <input type="text" name="nama_guru" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">MATA PELAJARAN</label>
                        <select name="id_mapel" id="edit_idmapel" class="form-select" required>
                            <option value="">-- Pilih Mapel --</option>
                            <?php while($m = mysqli_fetch_assoc($query_mapel_list)): ?>
                                <option value="<?= $m['id_mapel']; ?>"><?= $m['nama_mapel']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">EMAIL (USERNAME)</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="edit_guru" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Import Data Guru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 small mb-4">
                        <h6 class="fw-bold"><i class="bi bi-info-circle me-2"></i>Aturan File CSV:</h6>
                        <ul class="mb-0 ps-3">
                            <li>Gunakan pemisah koma (<strong>,</strong>)</li>
                            <li>Baris pertama adalah header (akan dilewati)</li>
                            <li>Urutan kolom: <strong>NUPTK, Nama Guru, Mata Pelajaran (Nama), Email</strong></li>
                            <li>Pastikan nama Mapel di CSV <strong>sama persis</strong> dengan yang ada di Data Mapel.</li>
                            <li>Contoh: <code>123456,Budi Santoso,Matematika,budi@smk.sch.id</code></li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-2">PILIH FILE CSV</label>
                        <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="import_guru" class="btn btn-success rounded-pill px-4">Mulai Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('toggleBtn').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });

    const modalEdit = document.getElementById('modalEdit');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('edit_id_user').value = button.getAttribute('data-id');
            document.getElementById('edit_nuptk').value = button.getAttribute('data-nuptk');
            document.getElementById('edit_nama').value = button.getAttribute('data-nama');
            document.getElementById('edit_email').value = button.getAttribute('data-email');
            document.getElementById('edit_idmapel').value = button.getAttribute('data-idmapel');
        });
    }
</script>
</body>
</html>