<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- LOGIKA PROSES DATA ---

// FUNGSI GENERATE RUANG OTOMATIS
if (isset($_POST['generate_ruang'])) {
    $ruang_list = [];
    $q_r = mysqli_query($conn, "SELECT id_ruang FROM ruang_ujian ORDER BY id_ruang ASC");
    while($r = mysqli_fetch_assoc($q_r)) { $ruang_list[] = $r['id_ruang']; }

    if (empty($ruang_list)) {
        header("Location: siswa.php?msg=error_ruang_kosong");
        exit;
    }

    $q_s = mysqli_query($conn, "SELECT id_user FROM siswa JOIN kelas ON siswa.id_kelas = kelas.id_kelas ORDER BY kelas.nama_kelas ASC");
    $siswa_list = [];
    while($s = mysqli_fetch_assoc($q_s)) { $siswa_list[] = $s['id_user']; }

    $chunks = array_chunk($siswa_list, 20);
    
    foreach ($chunks as $index => $group_siswa) {
        $id_ruang_tujuan = isset($ruang_list[$index]) ? $ruang_list[$index] : end($ruang_list);
        $ids = implode("','", $group_siswa);
        mysqli_query($conn, "UPDATE siswa SET id_ruang = '$id_ruang_tujuan' WHERE id_user IN ('$ids')");
    }

    header("Location: siswa.php?msg=generate_ruang_berhasil");
    exit;
}

// FUNGSI RESET RUANG (AKSI MASSAL)
if (isset($_POST['reset_ruang'])) {
    mysqli_query($conn, "UPDATE siswa SET id_ruang = NULL");
    header("Location: siswa.php?msg=reset_ruang_berhasil");
    exit;
}

// 1. Toggle Aktivasi (Single)
if (isset($_GET['toggle_status'])) {
    $id_u = $_GET['id_user'];
    $status_skrg = $_GET['status'];
    $status_baru = ($status_skrg == 'aktif') ? 'nonaktif' : 'aktif';
    mysqli_query($conn, "UPDATE users SET status = '$status_baru' WHERE id_user = '$id_u'");
    $ref = isset($_GET['search']) ? "siswa.php?search=".$_GET['search'] : "siswa.php";
    header("Location: $ref");
    exit;
}

// 2. Aktivasi Massal
if (isset($_POST['aksi_massal'])) {
    $status_massal = $_POST['status_tujuan'];
    mysqli_query($conn, "UPDATE users SET status = '$status_massal' WHERE role = 'siswa'");
    header("Location: siswa.php?msg=massal_berhasil");
    exit;
}

// 3. Tambah Siswa Manual
if (isset($_POST['tambah'])) {
    $nisn = mysqli_real_escape_string($conn, $_POST['nisn']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_siswa']);
    $kelas = $_POST['id_kelas'];
    $pass = password_hash($nisn, PASSWORD_DEFAULT);

    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "INSERT INTO users (username, password, role, status) VALUES ('$nisn', '$pass', 'siswa', 'nonaktif')");
        $id_u = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO siswa (id_user, id_kelas, nisn, nama_siswa) VALUES ('$id_u', '$kelas', '$nisn', '$nama')");
        mysqli_commit($conn);
        header("Location: siswa.php?msg=disimpan");
    } catch (Exception $e) {
        mysqli_rollback($conn);
    }
}

// 4. Fitur Import CSV
if (isset($_POST['import_siswa'])) {
    $file = $_FILES['file_csv']['tmp_name'];
    
    // Cek apakah file benar-benar diunggah
    if (!is_uploaded_file($file)) {
        header("Location: siswa.php?msg=file_tidak_ditemukan");
        exit;
    }

    $handle = fopen($file, "r");
    $row = 0;
    
    mysqli_begin_transaction($conn);
    try {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Jika CSV menggunakan titik koma (;) bukan koma (,)
            if (count($data) == 1) {
                rewind($handle); // Ulang pembacaan baris ini
                $data = fgetcsv($handle, 1000, ";");
            }

            if ($row == 0) { $row++; continue; } // Skip header
            
            // Validasi: Pastikan kolom minimal ada 3 (NISN, Nama, Kelas)
            if (count($data) < 3) continue;

            $nisn       = mysqli_real_escape_string($conn, trim($data[0]));
            $nama       = mysqli_real_escape_string($conn, trim($data[1]));
            $nama_kelas = mysqli_real_escape_string($conn, trim($data[2]));
            $pass       = password_hash($nisn, PASSWORD_DEFAULT);

            // 1. Cari ID Kelas berdasarkan Nama Kelas dari CSV
            $query_k = mysqli_query($conn, "SELECT id_kelas FROM kelas WHERE nama_kelas = '$nama_kelas'");
            
            if ($k = mysqli_fetch_assoc($query_k)) {
                $id_kelas = $k['id_kelas'];

                // 2. Cek apakah user sudah ada berdasarkan username (NISN)
                $cek_user = mysqli_query($conn, "SELECT id_user FROM users WHERE username = '$nisn'");
                
                if (mysqli_num_rows($cek_user) > 0) {
                    $u = mysqli_fetch_assoc($cek_user);
                    $id_u = $u['id_user'];
                    // Update data profil siswa jika user sudah ada
                    mysqli_query($conn, "UPDATE siswa SET nama_siswa = '$nama', id_kelas = '$id_kelas' WHERE id_user = '$id_u'");
                } else {
                    // 3. Jika belum ada, buat user di tabel 'users' dulu
                    mysqli_query($conn, "INSERT INTO users (username, password, role, status) VALUES ('$nisn', '$pass', 'siswa', 'nonaktif')");
                    $id_u = mysqli_insert_id($conn);
                    // Baru masukkan ke tabel 'siswa'
                    mysqli_query($conn, "INSERT INTO siswa (id_user, id_kelas, nisn, nama_siswa) VALUES ('$id_u', '$id_kelas', '$nisn', '$nama')");
                }
            }
        }
        mysqli_commit($conn);
        fclose($handle);
        header("Location: siswa.php?msg=imported");
        exit;
    } catch (Exception $e) { 
        mysqli_rollback($conn); 
        fclose($handle);
        header("Location: siswa.php?msg=error_import");
        exit;
    }
}

// 5. Fitur Edit Siswa
if (isset($_POST['edit_siswa'])) {
    $id_u = $_POST['id_user'];
    $nisn = mysqli_real_escape_string($conn, $_POST['nisn']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_siswa']);
    $kelas = $_POST['id_kelas'];

    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "UPDATE users SET username = '$nisn' WHERE id_user = '$id_u'");
        mysqli_query($conn, "UPDATE siswa SET nisn = '$nisn', nama_siswa = '$nama', id_kelas = '$kelas' WHERE id_user = '$id_u'");
        mysqli_commit($conn);
        header("Location: siswa.php?msg=updated");
    } catch (Exception $e) {
        mysqli_rollback($conn);
    }
}

// 6. Fitur Hapus Siswa
if (isset($_GET['hapus']) && isset($_GET['id_user'])) {
    $id_u = $_GET['id_user'];
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "DELETE FROM siswa WHERE id_user = '$id_u'");
        mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_u'");
        mysqli_commit($conn);
        header("Location: siswa.php?msg=dihapus");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        header("Location: siswa.php?msg=gagal_hapus");
        exit;
    }
}

// --- LOGIKA PENCARIAN ---
 $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
 $where_clause = "";
if (!empty($search)) {
    $where_clause = " WHERE (siswa.nama_siswa LIKE '%$search%' OR siswa.nisn LIKE '%$search%') ";
}

// --- LOGIKA PAGINATION ---
 $batas = 5; 
 $halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
 $halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

 $query_hitung = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa JOIN users ON siswa.id_user = users.id_user $where_clause");
 $res_hitung = mysqli_fetch_assoc($query_hitung);
 $total_data = $res_hitung['total'];
 $total_halaman = ceil($total_data / $batas);

// Query utama diperbarui untuk mengambil nama_ruang
 $query_siswa = mysqli_query($conn, "SELECT siswa.*, kelas.nama_kelas, ruang_ujian.nama_ruang, users.id_user, users.status 
    FROM siswa 
    JOIN kelas ON siswa.id_kelas = kelas.id_kelas 
    JOIN users ON siswa.id_user = users.id_user 
    LEFT JOIN ruang_ujian ON siswa.id_ruang = ruang_ujian.id_ruang
    $where_clause
    ORDER BY kelas.nama_kelas ASC, siswa.nama_siswa ASC 
    LIMIT $halaman_awal, $batas");

 $nomor = $halaman_awal + 1;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa | SMK Putra Anda</title>
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
        .pagination .page-link { border: none; background: #f1f5f9; color: #64748b; margin: 0 2px; border-radius: 8px !important; }
        .pagination .page-item.active .page-link { background: var(--accent); color: white; }
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
            <li class="mb-2"><a href="siswa.php" class="nav-link active"><i class="bi bi-people"></i> Data Siswa</a></li>
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
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Data Manajemen Siswa</h4>
                    <p class="text-muted small mb-0">Kelola akun login dan distribusi ruang ujian siswa</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="cetak_kartu.php" target="_blank" class="btn btn-dark btn-sm rounded-pill px-3 shadow-sm">
                        <i class="bi bi-printer me-1"></i> Cetak Kartu
                    </a>
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
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="" method="POST" onsubmit="return confirm('Ini akan mengatur ulang semua nomor ruang siswa. Lanjutkan?')">
                                    <button type="submit" name="generate_ruang" class="dropdown-item text-warning">Generate Ruang Otomatis</button>
                                </form>
                            </li>
                            <li>
                                <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SEMUA data ruangan siswa?')">
                                    <button type="submit" name="reset_ruang" class="dropdown-item text-danger">Hapus/Kosongkan Ruang</button>
                                </form>
                            </li>
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
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari berdasarkan nama atau NISN siswa..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-accent w-100 text-white shadow-sm" style="background: var(--accent);">Cari Data</button>
                    </div>
                </form>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i> 
                    Proses Berhasil: <?= str_replace('_', ' ', $_GET['msg']); ?>!
                </div>
            <?php endif; ?>

            <div class="card card-custom overflow-hidden shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="px-4 py-3 border-0">Identitas Siswa</th>
                                <th class="border-0">Kelas</th>
                                <th class="border-0 text-center">Ruang</th> <th class="text-center border-0">Status Login</th>
                                <th class="text-center border-0">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($query_siswa) > 0): ?>
                                <?php while($s = mysqli_fetch_assoc($query_siswa)): ?>
                                <tr>
                                    <td class="px-4">
                                        <div class="fw-bold text-dark"><?= $s['nama_siswa']; ?></div>
                                        <small class="text-muted">NISN: <?= $s['nisn']; ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border px-3"><?= $s['nama_kelas']; ?></span></td>
                                    <td class="text-center">
                                        <?php if($s['nama_ruang']): ?>
                                            <span class="badge bg-primary px-3"><i class="bi bi-door-open me-1"></i> <?= $s['nama_ruang']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-3">Belum Diatur</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="siswa.php?toggle_status=1&id_user=<?= $s['id_user']; ?>&status=<?= $s['status']; ?><?= !empty($search) ? '&search='.$search : '' ?>" 
                                           class="text-decoration-none badge <?= ($s['status'] == 'aktif') ? 'badge-aktif' : 'badge-nonaktif'; ?> p-2 px-3">
                                            <i class="bi <?= ($s['status'] == 'aktif') ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?> me-1"></i>
                                            <?= strtoupper($s['status']); ?>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-white text-primary border shadow-sm btn-edit" 
                                                data-bs-toggle="modal" data-bs-target="#modalEdit"
                                                data-id="<?= $s['id_user']; ?>" data-nisn="<?= $s['nisn']; ?>"
                                                data-nama="<?= $s['nama_siswa']; ?>" data-kelas="<?= $s['id_kelas']; ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="siswa.php?hapus=1&id_user=<?= $s['id_user']; ?><?= !empty($search) ? '&search='.$search : '' ?>" 
                                           class="btn btn-sm btn-white text-danger border shadow-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data siswa ini?')">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">Data tidak ditemukan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <small class="text-muted">Menampilkan <b><?= mysqli_num_rows($query_siswa); ?></b> dari <b><?= $total_data; ?></b> siswa</small>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <?php $link_search = !empty($search) ? "&search=$search" : ""; ?>
                                <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?pagi=<?= ($halaman-1).$link_search; ?>">Prev</a></li>
                                <?php for($i=1; $i<=$total_halaman; $i++): ?>
                                    <li class="page-item <?= ($halaman == $i) ? 'active' : ''; ?>"><a class="page-link" href="?pagi=<?= $i . $link_search; ?>"><?= $i; ?></a></li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?pagi=<?= ($halaman+1).$link_search; ?>">Next</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL IMPORT (Saya tambahkan ini karena sebelumnya hilang) -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <!-- Penting: enctype="multipart/form-data" agar file bisa terupload -->
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-excel me-2"></i>Import Data Siswa (CSV)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info small py-2">
                        <i class="bi bi-info-circle me-1"></i> Pastikan format CSV adalah: <b>NISN, Nama Lengkap, Nama Kelas</b>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-2">Pilih File CSV</label>
                        <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="import_siswa" class="btn btn-success rounded-pill px-4">Upload & Proses</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH (Saya tambahkan ini karena sebelumnya juga hilang) -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Tambah Siswa Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">NISN (Username)</label>
                        <input type="text" name="nisn" class="form-control" placeholder="Contoh: 12345" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">NAMA LENGKAP</label>
                        <input type="text" name="nama_siswa" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">KELAS</label>
                        <select name="id_kelas" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php 
                            $q_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
                            while($k = mysqli_fetch_assoc($q_kelas)): ?>
                                <option value="<?= $k['id_kelas']; ?>"><?= $k['nama_kelas']; ?></option>
                            <?php endwhile; ?>
                        </select>
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
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Data Siswa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_user" id="edit_id_user">
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">NISN (Username)</label>
                        <input type="text" name="nisn" id="edit_nisn" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">NAMA LENGKAP</label>
                        <input type="text" name="nama_siswa" id="edit_nama" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">KELAS</label>
                        <select name="id_kelas" id="edit_kelas" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php 
                            $q_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
                            while($k = mysqli_fetch_assoc($q_kelas)): ?>
                                <option value="<?= $k['id_kelas']; ?>"><?= $k['nama_kelas']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_siswa" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle Sidebar
    document.getElementById('toggleBtn').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });

    // Logika Pengisian Modal Edit
    const modalEdit = document.getElementById('modalEdit');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', function(event) {
            // Tombol yang memicu modal
            const button = event.relatedTarget;
            
            // Ambil data dari atribut data-*
            const id = button.getAttribute('data-id');
            const nisn = button.getAttribute('data-nisn');
            const nama = button.getAttribute('data-nama');
            const kelas = button.getAttribute('data-kelas');

            // Masukkan ke dalam input modal
            document.getElementById('edit_id_user').value = id;
            document.getElementById('edit_nisn').value = nisn;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_kelas').value = kelas;
        });
    }
</script>
</body>
</html>