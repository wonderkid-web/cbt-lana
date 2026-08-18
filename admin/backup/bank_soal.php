<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// Ambil ID User Login untuk ditulis di database (siapa yang input soal)
 $id_user_login = $_SESSION['id_user'] ?? 0; 

// --- LOGIKA PROSES DATA (CRUD) ---

// 1. Import Soal
if (isset($_POST['import_soal'])) {
    $file = $_FILES['file_soal']['tmp_name'];
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

            $nama_mapel_csv = mysqli_real_escape_string($conn, trim($data[0]));
            $pertanyaan     = mysqli_real_escape_string($conn, trim($data[1]));
            $opsi_a         = mysqli_real_escape_string($conn, trim($data[2]));
            $opsi_b         = mysqli_real_escape_string($conn, trim($data[3]));
            $opsi_c         = mysqli_real_escape_string($conn, trim($data[4]));
            $opsi_d         = mysqli_real_escape_string($conn, trim($data[5]));
            $opsi_e         = mysqli_real_escape_string($conn, trim($data[6]));
            $kunci          = mysqli_real_escape_string($conn, trim($data[7]));
            
            // Cari ID Mapel berdasarkan nama di CSV
            $q_cari_mapel = mysqli_query($conn, "SELECT id_mapel FROM mapel WHERE nama_mapel = '$nama_mapel_csv' LIMIT 1");
            $m = mysqli_fetch_assoc($q_cari_mapel);
            
            if ($m) {
                $id_mapel_valid = $m['id_mapel'];
                $sql = "INSERT INTO bank_soal (id_user, id_mapel, pertanyaan, tipe_soal, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban) 
                        VALUES ('$id_user_login', '$id_mapel_valid', '$pertanyaan', 'pg', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$kunci')";
                if (mysqli_query($conn, $sql)) {
                    $success_count++;
                }
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
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $kunci      = mysqli_real_escape_string($conn, $_POST['kunci_jawaban']);
    $opsi_a     = mysqli_real_escape_string($conn, $_POST['opsi_a']);
    $opsi_b     = mysqli_real_escape_string($conn, $_POST['opsi_b']);
    $opsi_c     = mysqli_real_escape_string($conn, $_POST['opsi_c']);
    $opsi_d     = mysqli_real_escape_string($conn, $_POST['opsi_d']);
    $opsi_e     = mysqli_real_escape_string($conn, $_POST['opsi_e']);

    $sql = "INSERT INTO bank_soal (id_user, id_mapel, pertanyaan, tipe_soal, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban) 
            VALUES ('$id_user_login', '$id_mapel', '$pertanyaan', 'pg', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$kunci')";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: bank_soal.php?msg=ditambah"); exit;
    }
}

// 3. Edit Soal
if (isset($_POST['edit_soal'])) {
    $id_soal    = (int)$_POST['id_soal'];
    $id_mapel   = (int)$_POST['id_mapel'];
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $kunci      = mysqli_real_escape_string($conn, $_POST['kunci_jawaban']);
    $opsi_a     = mysqli_real_escape_string($conn, $_POST['opsi_a']);
    $opsi_b     = mysqli_real_escape_string($conn, $_POST['opsi_b']);
    $opsi_c     = mysqli_real_escape_string($conn, $_POST['opsi_c']);
    $opsi_d     = mysqli_real_escape_string($conn, $_POST['opsi_d']);
    $opsi_e     = mysqli_real_escape_string($conn, $_POST['opsi_e']);

    $sql = "UPDATE bank_soal SET 
                id_mapel = '$id_mapel', 
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

// --- PAGINATION & FILTER ---
 $limit = 5;
 $halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
 $mulai = ($halaman > 1) ? ($halaman * $limit) - $limit : 0;

 $filter_mapel = isset($_GET['filter_mapel']) ? (int)$_GET['filter_mapel'] : '';
 $where_mapel = $filter_mapel ? " AND bs.id_mapel = '$filter_mapel'" : "";

// Hitung total data
 $query_hitung = mysqli_query($conn, "SELECT COUNT(*) as total FROM bank_soal bs WHERE 1=1 $where_mapel");
 $total_data = mysqli_fetch_assoc($query_hitung)['total'];
 $total_halaman = ceil($total_data / $limit);

// Ambil data soal
 $query_soal = mysqli_query($conn, "SELECT bs.*, m.nama_mapel 
                                    FROM bank_soal bs 
                                    LEFT JOIN mapel m ON bs.id_mapel = m.id_mapel 
                                    WHERE 1=1 $where_mapel
                                    ORDER BY bs.id_soal DESC 
                                    LIMIT $mulai, $limit");

// Ambil data Mapel untuk Dropdown
 $query_mapel = mysqli_query($conn, "SELECT * FROM mapel ORDER BY nama_mapel ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Soal Admin | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { 
            --sidebar-bg: #0f172a; 
            --accent: #3b82f6; 
        }
        body { 
            background-color: #f1f5f9; 
            font-family: 'Inter', sans-serif; 
            overflow-x: hidden;
        }
        
        /* Sidebar Styling (Sama dengan Kelas.php) */
        #sidebar { 
            min-width: 260px; 
            max-width: 260px; 
            background: var(--sidebar-bg); 
            color: #fff; 
            min-height: 100vh; 
            transition: 0.3s; 
        }
        #sidebar.active { margin-left: -260px; }
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

        /* Main Content Styling */
        .content-area { width: 100%; }
        .top-nav { 
            background: #fff; 
            padding: 15px 25px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        }

        /* Stats Cards */
        .card-stat { 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.02); 
            transition: 0.3s; 
            background: #fff;
        }
        .card-stat:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 10px 20px rgba(0,0,0,0.05); 
        }
        
        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
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
    <!-- Sidebar -->
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
            <li class="mb-2"><a href="ruang.php" class="nav-link"><i class="bi bi-door-open"></i> Ruang Ujian</a></li>
            <li class="mb-2"><a href="mapel.php" class="nav-link"><i class="bi bi-book"></i> Data Mapel</a></li>
            <!-- Active Link -->
            <li class="mb-2"><a href="bank_soal.php" class="nav-link active"><i class="bi bi-file-earmark-text"></i> Bank Soal</a></li>
            <li class="mb-2"><a href="sesi_ujian.php" class="nav-link"><i class="bi bi-clipboard-check"></i> Sesi Ujian</a></li>
            <li class="mb-2"><a href="laporan_full.php" class="nav-link"><i class="bi bi-bar-chart-line"></i> Rekap Nilai</a></li>
            <hr class="mx-3 opacity-25 text-white">
            <li><a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Main Content Wrapper (w-100) -->
    <div class="w-100">
        <!-- Top Navigation -->
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

        <!-- Page Content -->
        <div class="container-fluid p-4">
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">Manajemen Bank Soal</h4>
                        <p class="text-muted small mb-0">Kelola bank soal untuk ujian sekolah.</p>
                    </div>
                    <div>
                        <button class="btn btn-success btn-sm rounded-pill px-3 shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#modalImport">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                        </button>
                        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Soal
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notifikasi Alert -->
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <?php if($_GET['msg'] == 'import_sukses'): ?>
                        <i class="bi bi-check-circle-fill me-2"></i> Berhasil mengimpor <b><?= $_GET['jml']; ?></b> soal!
                    <?php elseif($_GET['msg'] == 'error_file'): ?>
                        <div class="text-danger"><i class="bi bi-exclamation-circle me-2"></i> File CSV tidak ditemukan atau salah format.</div>
                    <?php elseif($_GET['msg'] == 'import_gagal'): ?>
                        <div class="text-danger"><i class="bi bi-exclamation-circle me-2"></i> Terjadi kesalahan saat import data.</div>
                    <?php else: ?>
                        <i class="bi bi-check-circle-fill me-2"></i> Data berhasil <strong><?= $_GET['msg']; ?></strong>.
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Main Card (Filter & Table) -->
            <div class="card card-custom overflow-hidden">
                <!-- Filter Toolbar -->
                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <form method="GET" class="d-flex align-items-center gap-2 w-100">
                        <label class="small fw-bold text-muted mb-0">Filter Mapel:</label>
                        <select name="filter_mapel" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                            <option value="">-- Semua Mapel --</option>
                            <?php mysqli_data_seek($query_mapel, 0); ?>
                            <?php while($m = mysqli_fetch_assoc($query_mapel)): ?>
                                <option value="<?= $m['id_mapel']; ?>" <?= ($filter_mapel == $m['id_mapel']) ? 'selected' : ''; ?>><?= $m['nama_mapel']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                    <small class="text-muted">Total: <?= $total_data; ?> Soal</small>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%" class="px-4 border-0">No</th>
                                <th width="40%" class="border-0">Pertanyaan</th>
                                <th width="15%" class="border-0">Mapel</th>
                                <th width="10%" class="border-0">Kunci</th>
                                <th width="15%" class="border-0">Opsi (Preview)</th>
                                <th width="15%" class="text-center border-0">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = $mulai + 1; ?>
                            <?php if(mysqli_num_rows($query_soal) > 0): ?>
                                <?php while($s = mysqli_fetch_assoc($query_soal)): ?>
                                <tr>
                                    <td class="px-4"><?= $no++; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($s['pertanyaan']); ?></div>
                                        <small class="text-muted">ID: #<?= $s['id_soal']; ?></small>
                                    </td>
                                    <td><span class="badge bg-info text-dark rounded-pill"><?= $s['nama_mapel'] ?? '-'; ?></span></td>
                                    <td class="fw-bold text-primary"><?= strtoupper($s['kunci_jawaban']); ?></td>
                                    <td><small class="text-muted">A: <?= htmlspecialchars(substr($s['opsi_a'], 0, 20)); ?>...</small></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-light text-primary border shadow-sm me-1" 
                                            data-bs-toggle="modal" data-bs-target="#modalEdit<?= $s['id_soal']; ?>"><i class="bi bi-pencil-square"></i></button>
                                        <a href="?hapus=<?= $s['id_soal']; ?>" class="btn btn-sm btn-light text-danger border shadow-sm" onclick="return confirm('Yakin hapus soal ini?')"><i class="bi bi-trash3"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data soal.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Footer -->
                <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted">Halaman <?= $halaman; ?> dari <?= $total_halaman; ?></small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?halaman=<?= $halaman-1 ?>&filter_mapel=<?= $filter_mapel ?>">Prev</a>
                            </li>
                            <?php for($i=1; $i<=$total_halaman; $i++): ?>
                                <li class="page-item <?= ($i == $halaman) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?halaman=<?= $i ?>&filter_mapel=<?= $filter_mapel ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?halaman=<?= $halaman+1 ?>&filter_mapel=<?= $filter_mapel ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL IMPORT -->
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white">
                    <h6 class="modal-title">Import Soal CSV</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning small py-2 bg-light border-warning"><i class="bi bi-info-circle me-1"></i> Format CSV: Nama Mapel, Soal, Opsi A, B, C, D, E, Kunci</div>
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">File CSV</label>
                        <input type="file" name="file_soal" class="form-control" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="import_soal" class="btn btn-success">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title">Tambah Soal Baru</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="small fw-bold text-muted mb-1">Pilih Mapel</label>
                            <select name="id_mapel" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <?php mysqli_data_seek($query_mapel, 0); ?>
                                <?php while($m = mysqli_fetch_assoc($query_mapel)): ?>
                                    <option value="<?= $m['id_mapel']; ?>"><?= $m['nama_mapel']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted mb-1">Pertanyaan</label>
                            <textarea name="pertanyaan" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted mb-1">Opsi A</label>
                            <input type="text" name="opsi_a" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted mb-1">Opsi B</label>
                            <input type="text" name="opsi_b" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted mb-1">Opsi C</label>
                            <input type="text" name="opsi_c" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted mb-1">Opsi D</label>
                            <input type="text" name="opsi_d" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted mb-1">Opsi E</label>
                            <input type="text" name="opsi_e" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-danger mb-1">Kunci Jawaban</label>
                            <input type="text" name="kunci_jawaban" class="form-control border-danger" placeholder="A / B / C / D / E" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_soal" class="btn btn-primary px-4 rounded-pill">Simpan Soal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT (Loop) -->
<?php mysqli_data_seek($query_soal, 0); ?>
<?php while($s = mysqli_fetch_assoc($query_soal)): ?>
<div class="modal fade" id="modalEdit<?= $s['id_soal']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title">Edit Soal #<?= $s['id_soal']; ?></h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_soal" value="<?= $s['id_soal']; ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="small fw-bold text-muted mb-1">Pilih Mapel</label>
                            <select name="id_mapel" class="form-select">
                                <?php mysqli_data_seek($query_mapel, 0); ?>
                                <?php while($m = mysqli_fetch_assoc($query_mapel)): ?>
                                    <option value="<?= $m['id_mapel']; ?>" <?= ($s['id_mapel'] == $m['id_mapel']) ? 'selected' : ''; ?>><?= $m['nama_mapel']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted mb-1">Pertanyaan</label>
                            <textarea name="pertanyaan" class="form-control" rows="3"><?= htmlspecialchars($s['pertanyaan']); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted mb-1">Opsi A</label>
                            <input type="text" name="opsi_a" class="form-control" value="<?= htmlspecialchars($s['opsi_a'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted mb-1">Opsi B</label>
                            <input type="text" name="opsi_b" class="form-control" value="<?= htmlspecialchars($s['opsi_b'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted mb-1">Opsi C</label>
                            <input type="text" name="opsi_c" class="form-control" value="<?= htmlspecialchars($s['opsi_c'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted mb-1">Opsi D</label>
                            <input type="text" name="opsi_d" class="form-control" value="<?= htmlspecialchars($s['opsi_d'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted mb-1">Opsi E</label>
                            <input type="text" name="opsi_e" class="form-control" value="<?= htmlspecialchars($s['opsi_e'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-danger mb-1">Kunci Jawaban</label>
                            <input type="text" name="kunci_jawaban" class="form-control border-danger" value="<?= htmlspecialchars($s['kunci_jawaban']); ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_soal" class="btn btn-primary px-4 rounded-pill">Update Soal</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JS Toggle Sidebar (Sama seperti mapel.php)
    document.getElementById('toggleBtn').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
</script>
</body>
</html>