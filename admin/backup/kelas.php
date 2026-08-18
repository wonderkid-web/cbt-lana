<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- LOGIKA PROSES DATA ---

if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_kelas']);
    mysqli_query($conn, "INSERT INTO kelas (nama_kelas) VALUES ('$nama')");
    header("Location: kelas.php?msg=disimpan");
}

if (isset($_POST['edit'])) {
    $id = $_POST['id_kelas'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_kelas']);
    mysqli_query($conn, "UPDATE kelas SET nama_kelas = '$nama' WHERE id_kelas = '$id'");
    header("Location: kelas.php?msg=diubah");
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kelas WHERE id_kelas = '$id'");
    header("Location: kelas.php?msg=dihapus");
}

// --- LOGIKA IMPORT KELAS (DISESUAIKAN STYLE) ---
if (isset($_POST['import_kelas'])) {
    $file = $_FILES['file_csv']['tmp_name'];

    // Cek apakah file benar-benar diunggah
    if (!is_uploaded_file($file)) {
        header("Location: kelas.php?msg=file_tidak_ditemukan");
        exit;
    }

    $handle = fopen($file, "r");
    $row = 0;
    $success_count = 0;

    mysqli_begin_transaction($conn);
    try {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Lewati baris pertama (Header)
            if ($row == 0) { 
                $row++; 
                continue; 
            }

            // Ambil data kolom pertama (Nama Kelas)
            $nama_import = mysqli_real_escape_string($conn, trim($data[0]));

            // Validasi agar tidak kosong
            if (!empty($nama_import)) {
                // Cek apakah nama kelas sudah ada untuk menghindari duplikat
                $cek = mysqli_query($conn, "SELECT id_kelas FROM kelas WHERE nama_kelas = '$nama_import'");
                if (mysqli_num_rows($cek) == 0) {
                    mysqli_query($conn, "INSERT INTO kelas (nama_kelas) VALUES ('$nama_import')");
                    $success_count++;
                }
            }
        }
        mysqli_commit($conn);
        fclose($handle);
        header("Location: kelas.php?msg=import_sukses&jml=$success_count");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        fclose($handle);
        header("Location: kelas.php?msg=import_gagal");
        exit;
    }
}

// --- LOGIKA PAGINATION ---
 $batas = 5; 
 $halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
 $halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

 $sebelum = $halaman - 1;
 $sesudah = $halaman + 1;

// Hitung total data
 $data_full = mysqli_query($conn, "SELECT * FROM kelas");
 $jumlah_data = mysqli_num_rows($data_full);
 $total_halaman = ceil($jumlah_data / $batas);

// Query data dengan LIMIT
 $query_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC LIMIT $halaman_awal, $batas");
 $nomor = $halaman_awal + 1;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kelas | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-bg: #0f172a; --accent: #3b82f6; }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        
        /* Sidebar Styling */
        #sidebar { min-width: 260px; max-width: 260px; background: var(--sidebar-bg); color: #fff; min-height: 100vh; transition: 0.3s; }
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
        .top-nav { background: #fff; padding: 15px 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
        
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
            <li class="mb-2"><a href="kelas.php" class="nav-link active"><i class="bi bi-house-door"></i> Data Kelas</a></li>
            <li class="mb-2"><a href="ruang.php" class="nav-link"><i class="bi bi-door-open"></i> Ruang Ujian</a></li>
            <li class="mb-2"><a href="mapel.php" class="nav-link"><i class="bi bi-book"></i> Data Mapel</a></li>
            <li class="mb-2"><a href="bank_soal.php" class="nav-link"><i class="bi bi-file-earmark-text"></i> Bank Soal</a></li>
            <li class="mb-2"><a href="sesi_ujian.php" class="nav-link"><i class="bi bi-clipboard-check"></i> Sesi Ujian</a></li>
            <li class="mb-2"><a href="laporan_full.php" class="nav-link"><i class="bi bi-bar-chart-line"></i> Rekap Nilai</a></li>
            <hr class="mx-3 opacity-25 text-white">
            <li><a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="content-area">
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0">Data Manajemen Kelas</h4>
                    <p class="text-muted small mb-0">Kelola daftar kelas untuk distribusi siswa dan ujian.</p>
                </div>
                <div class="d-flex gap-2">
                    <!-- Tombol Import (Style Disamakan) -->
                    <button class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImport">
                        <i class="bi bi-file-earmark-excel me-1"></i> Import
                    </button>
                    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="bi bi-plus-lg me-1"></i> Tambah
                    </button>
                </div>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php if($_GET['msg'] == 'import_sukses'): ?>
                        Berhasil mengimpor <b><?= $_GET['jml']; ?></b> kelas baru!
                    <?php else: ?>
                        Data Berhasil <strong><?= str_replace('_', ' ', $_GET['msg']); ?></strong>!
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="card card-custom overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 text-center" width="8%">No</th>
                                <th>Nama Kelas</th>
                                <th class="text-center" width="20%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $n = $nomor; while($k = mysqli_fetch_assoc($query_kelas)): ?>
                            <tr>
                                <td class="text-center px-4"><?= $n++; ?></td>
                                <td class="fw-bold text-dark"><?= $k['nama_kelas']; ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light text-primary edit-btn" 
                                            data-id="<?= $k['id_kelas']; ?>" 
                                            data-nama="<?= $k['nama_kelas']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalEdit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="kelas.php?hapus=<?= $k['id_kelas']; ?>" class="btn btn-sm btn-light text-danger shadow-sm" onclick="return confirm('Hapus kelas ini?')">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-0 py-3">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-pill px-3 me-2" href="<?= ($halaman > 1) ? "?pagi=$sebelum" : '#'; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>

                            <?php for($x=1; $x<=$total_halaman; $x++): ?>
                                <li class="page-item <?= ($halaman == $x) ? 'active' : ''; ?>">
                                    <a class="page-link rounded-pill mx-1" href="?pagi=<?= $x; ?>"><?= $x; ?></a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-pill px-3 ms-2" href="<?= ($halaman < $total_halaman) ? "?pagi=$sesudah" : '#'; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <div class="text-center mt-2">
                        <small class="text-muted">Total Data: <b><?= $jumlah_data; ?></b> | Halaman <b><?= $halaman; ?></b> dari <b><?= $total_halaman; ?></b></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0"><h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Kelas Baru</h5></div>
            <div class="modal-body p-4">
                <label class="small fw-bold text-muted mb-2">NAMA KELAS</label>
                <input type="text" name="nama_kelas" class="form-control" placeholder="Contoh: XII RPL 1" required>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah" class="btn btn-primary rounded-pill px-4">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0"><h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Ubah Nama Kelas</h5></div>
            <div class="modal-body p-4">
                <input type="hidden" name="id_kelas" id="edit_id">
                <label class="small fw-bold text-muted mb-2">NAMA KELAS</label>
                <input type="text" name="nama_kelas" id="edit_nama" class="form-control" required>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL IMPORT (STYLE DISAMAKAN DENGAN BANK SOAL) -->
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark-excel me-2"></i>Import Data Kelas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning small py-2">
                    <strong>Format CSV:</strong> Satu kolom saja berisi Nama Kelas. 
                    <br>Baris pertama adalah Header (akan dilewati).
                    <br><br>
                    <code>Nama Kelas</code><br>
                    <code>X RPL 1</code><br>
                    <code>X RPL 2</code>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-2">Pilih File CSV</label>
                    <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="import_kelas" class="btn btn-success rounded-pill px-4">Upload & Proses</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('toggleBtn').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });

    const editBtns = document.querySelectorAll('.edit-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_nama').value = this.getAttribute('data-nama');
        });
    });
</script>
</body>
</html>