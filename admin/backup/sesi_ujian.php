<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- LOGIKA PROSES DATA ---

// 1. Toggle Status (Aktif/Nonaktifkan Ujian)
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['id_sesi'];
    // Logika toggle: Jika aktif jadi nonaktif, begitu sebaliknya
    mysqli_query($conn, "UPDATE sesi_ujian SET status = IF(status='aktif', 'nonaktif', 'aktif') WHERE id_sesi = '$id'");
    header("Location: sesi_ujian.php?msg=status_diubah");
    exit;
}

// 2. Tambah Sesi Ujian
if (isset($_POST['tambah'])) {
    $nama_ujian = mysqli_real_escape_string($conn, $_POST['nama_ujian']);
    $jenis_ujian = mysqli_real_escape_string($conn, $_POST['jenis_ujian']);
    $id_kelas = (int)$_POST['id_kelas'];
    $id_mapel = (int)$_POST['id_mapel'];
    $tgl_mulai = mysqli_real_escape_string($conn, $_POST['tgl_mulai']);
    $durasi = (int)$_POST['durasi'];
    $token = mysqli_real_escape_string($conn, $_POST['token']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $sql = "INSERT INTO sesi_ujian (nama_ujian, jenis_ujian, id_kelas, id_mapel, tgl_mulai, durasi, token, status) 
            VALUES ('$nama_ujian', '$jenis_ujian', '$id_kelas', '$id_mapel', '$tgl_mulai', '$durasi', '$token', '$status')";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: sesi_ujian.php?msg=disimpan");
    } else {
        header("Location: sesi_ujian.php?msg=gagal");
    }
    exit;
}

// 3. Edit Sesi Ujian
if (isset($_POST['edit'])) {
    $id = (int)$_POST['id_sesi'];
    $nama_ujian = mysqli_real_escape_string($conn, $_POST['nama_ujian']);
    $jenis_ujian = mysqli_real_escape_string($conn, $_POST['jenis_ujian']);
    $id_kelas = (int)$_POST['id_kelas'];
    $id_mapel = (int)$_POST['id_mapel'];
    $tgl_mulai = mysqli_real_escape_string($conn, $_POST['tgl_mulai']);
    $durasi = (int)$_POST['durasi'];
    $token = mysqli_real_escape_string($conn, $_POST['token']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $sql = "UPDATE sesi_ujian SET 
            nama_ujian = '$nama_ujian', 
            jenis_ujian = '$jenis_ujian', 
            id_kelas = '$id_kelas', 
            id_mapel = '$id_mapel', 
            tgl_mulai = '$tgl_mulai', 
            durasi = '$durasi', 
            token = '$token', 
            status = '$status' 
            WHERE id_sesi = '$id'";
            
    if (mysqli_query($conn, $sql)) {
        header("Location: sesi_ujian.php?msg=diubah");
    }
    exit;
}

// 4. Hapus Sesi Ujian
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM sesi_ujian WHERE id_sesi = '$id'");
    header("Location: sesi_ujian.php?msg=dihapus");
    exit;
}

// --- AMBIL DATA KELAS & MAPEL UNTUK DROPDOWN ---
$query_kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
$query_mapel_list = mysqli_query($conn, "SELECT * FROM mapel ORDER BY nama_mapel ASC");

// --- LOGIKA PAGINATION ---
$batas = 5; 
$halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;
$sebelum = $halaman - 1;
$sesudah = $halaman + 1;

// Hitung total data
$data_full = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian");
$jumlah_data = mysqli_fetch_assoc($data_full)['total'];
$total_halaman = ceil($jumlah_data / $batas);

// Query data dengan JOIN ke tabel kelas dan mapel
$query_sesi = mysqli_query($conn, "SELECT sesi_ujian.*, kelas.nama_kelas, mapel.nama_mapel 
                                  FROM sesi_ujian 
                                  LEFT JOIN kelas ON sesi_ujian.id_kelas = kelas.id_kelas
                                  LEFT JOIN mapel ON sesi_ujian.id_mapel = mapel.id_mapel
                                  ORDER BY sesi_ujian.tgl_mulai DESC 
                                  LIMIT $halaman_awal, $batas");
$nomor = $halaman_awal + 1;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi Ujian | SMK Putra Anda</title>
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
        
        /* Badge Status */
        .badge-aktif { background: #dcfce7; color: #166534; }
        .badge-nonaktif { background: #fee2e2; color: #991b1b; }
        
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
            <li class="mb-2"><a href="ruang.php" class="nav-link"><i class="bi bi-door-open"></i> Ruang Ujian</a></li>
            <li class="mb-2"><a href="mapel.php" class="nav-link"><i class="bi bi-book"></i> Data Mapel</a></li>
            <li class="mb-2"><a href="bank_soal.php" class="nav-link"><i class="bi bi-file-earmark-text"></i> Bank Soal</a></li>
            <li class="mb-2"><a href="sesi_ujian.php" class="nav-link active"><i class="bi bi-clipboard-check"></i> Sesi Ujian</a></li>
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
                    <h4 class="fw-bold mb-0">Manajemen Sesi Ujian</h4>
                    <p class="text-muted small mb-0">Atur jadwal, token, dan durasi ujian.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Sesi
                    </button>
                </div>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Data Berhasil <strong><?= str_replace('_', ' ', $_GET['msg']); ?></strong>!
                </div>
            <?php endif; ?>

            <div class="card card-custom overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 text-center" width="5%">No</th>
                                <th>Nama Ujian</th>
                                <th>Mata Pelajaran</th>
                                <th>Kelas</th>
                                <th>Jadwal</th>
                                <th class="text-center">Durasi</th>
                                <th class="text-center">Token</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $n = $nomor; while($s = mysqli_fetch_assoc($query_sesi)): ?>
                            <tr>
                                <td class="text-center px-4"><?= $n++; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= $s['nama_ujian']; ?></div>
                                    <small class="text-muted"><?= $s['jenis_ujian']; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info"><?= $s['nama_mapel'] ?? '-'; ?></span>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= $s['nama_kelas'] ?? 'Semua'; ?></span></td>
                                <td>
                                    <div class="small fw-bold"><?= date('d M Y', strtotime($s['tgl_mulai'])); ?></div>
                                    <div class="small text-muted"><?= date('H:i', strtotime($s['tgl_mulai'])); ?> WIB</div>
                                </td>
                                <td class="text-center fw-bold"><?= $s['durasi']; ?> <small class="text-muted">mnt</small></td>
                                <td class="text-center">
                                    <code class="bg-light px-2 py-1 rounded text-primary fw-bold" style="font-size: 14px;"><?= $s['token']; ?></code>
                                </td>
                                <td class="text-center">
                                    <a href="?toggle_status=true&id_sesi=<?= $s['id_sesi']; ?>" class="text-decoration-none">
                                        <span class="badge <?= ($s['status'] == 'aktif') ? 'badge-aktif' : 'badge-nonaktif'; ?> p-2" style="cursor:pointer;">
                                            <?= strtoupper($s['status']); ?>
                                        </span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light text-primary edit-btn" 
                                            data-id="<?= $s['id_sesi']; ?>" 
                                            data-nama="<?= $s['nama_ujian']; ?>"
                                            data-jenis="<?= $s['jenis_ujian']; ?>"
                                            data-kelas="<?= $s['id_kelas']; ?>"
                                            data-mapel="<?= $s['id_mapel']; ?>"
                                            data-tgl="<?= date('Y-m-d\TH:i', strtotime($s['tgl_mulai'])); ?>"
                                            data-durasi="<?= $s['durasi']; ?>"
                                            data-token="<?= $s['token']; ?>"
                                            data-status="<?= $s['status']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalEdit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="sesi_ujian.php?hapus=<?= $s['id_sesi']; ?>" class="btn btn-sm btn-light text-danger shadow-sm" onclick="return confirm('Hapus sesi ini?')">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($query_sesi) == 0): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Belum ada sesi ujian.</td>
                            </tr>
                            <?php endif; ?>
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
                        <small class="text-muted">Total Data: <b><?= $jumlah_data; ?></b></small>
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
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Sesi Ujian</h5>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">NAMA UJIAN</label>
                    <input type="text" name="nama_ujian" class="form-control" placeholder="Contoh: UTS Ganjil 2024" required>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="small fw-bold text-muted mb-1">JENIS UJIAN</label>
                        <select name="jenis_ujian" class="form-select" required>
                            <option value="UAS">UAS</option>
                            <option value="UTS">UTS</option>
                            <option value="Ulangan Harian">Ulangan Harian</option>
                            <option value="Try Out">Try Out</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-muted mb-1">MATA PELAJARAN</label>
                        <select name="id_mapel" class="form-select" required>
                            <option value="">-- Pilih Mapel --</option>
                            <?php mysqli_data_seek($query_mapel_list, 0); ?>
                            <?php while($m = mysqli_fetch_assoc($query_mapel_list)): ?>
                                <option value="<?= $m['id_mapel']; ?>"><?= $m['nama_mapel']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">KELAS</label>
                    <select name="id_kelas" class="form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php mysqli_data_seek($query_kelas_list, 0); ?>
                        <?php while($k = mysqli_fetch_assoc($query_kelas_list)): ?>
                            <option value="<?= $k['id_kelas']; ?>"><?= $k['nama_kelas']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="row mb-3">
                    <div class="col-7">
                        <label class="small fw-bold text-muted mb-1">TANGGAL & WAKTU MULAI</label>
                        <input type="datetime-local" name="tgl_mulai" class="form-control" required>
                    </div>
                    <div class="col-5">
                        <label class="small fw-bold text-muted mb-1">DURASI (Menit)</label>
                        <input type="number" name="durasi" class="form-control" placeholder="60" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-8">
                        <label class="small fw-bold text-muted mb-1">TOKEN</label>
                        <input type="text" name="token" id="token_tambah" class="form-control" placeholder="Kode unik ujian" required>
                    </div>
                    <div class="col-4 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="generateToken('token_tambah')">
                            <i class="bi bi-arrow-repeat"></i> Generate
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">STATUS</label>
                    <select name="status" class="form-select">
                        <option value="nonaktif">Nonaktif</option>
                        <option value="aktif">Aktif</option>
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

<!-- MODAL EDIT -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Sesi Ujian</h5>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id_sesi" id="edit_id">
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">NAMA UJIAN</label>
                    <input type="text" name="nama_ujian" id="edit_nama" class="form-control" required>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="small fw-bold text-muted mb-1">JENIS UJIAN</label>
                        <select name="jenis_ujian" id="edit_jenis" class="form-select" required>
                            <option value="UAS">UAS</option>
                            <option value="UTS">UTS</option>
                            <option value="Ulangan Harian">Ulangan Harian</option>
                            <option value="Try Out">Try Out</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-muted mb-1">MATA PELAJARAN</label>
                        <select name="id_mapel" id="edit_mapel" class="form-select" required>
                            <option value="">-- Pilih Mapel --</option>
                            <?php mysqli_data_seek($query_mapel_list, 0); ?>
                            <?php while($m = mysqli_fetch_assoc($query_mapel_list)): ?>
                                <option value="<?= $m['id_mapel']; ?>"><?= $m['nama_mapel']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">KELAS</label>
                    <select name="id_kelas" id="edit_kelas" class="form-select" required>
                        <?php mysqli_data_seek($query_kelas_list, 0); ?>
                        <?php while($k = mysqli_fetch_assoc($query_kelas_list)): ?>
                            <option value="<?= $k['id_kelas']; ?>"><?= $k['nama_kelas']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="row mb-3">
                    <div class="col-7">
                        <label class="small fw-bold text-muted mb-1">TANGGAL & WAKTU MULAI</label>
                        <input type="datetime-local" name="tgl_mulai" id="edit_tgl" class="form-control" required>
                    </div>
                    <div class="col-5">
                        <label class="small fw-bold text-muted mb-1">DURASI (Menit)</label>
                        <input type="number" name="durasi" id="edit_durasi" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-8">
                        <label class="small fw-bold text-muted mb-1">TOKEN</label>
                        <input type="text" name="token" id="edit_token" class="form-control" required>
                    </div>
                    <div class="col-4 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="generateToken('edit_token')">
                            <i class="bi bi-arrow-repeat"></i> Generate
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">STATUS</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="nonaktif">Nonaktif</option>
                        <option value="aktif">Aktif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('toggleBtn').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });

    // Fungsi Generate Token Random
    function generateToken(targetId) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        for (let i = 0; i < 5; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById(targetId).value = result;
    }

    // Fungsi Isi Modal Edit
    const editBtns = document.querySelectorAll('.edit-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_nama').value = this.getAttribute('data-nama');
            document.getElementById('edit_jenis').value = this.getAttribute('data-jenis');
            document.getElementById('edit_kelas').value = this.getAttribute('data-kelas');
            document.getElementById('edit_mapel').value = this.getAttribute('data-mapel');
            document.getElementById('edit_tgl').value = this.getAttribute('data-tgl');
            document.getElementById('edit_durasi').value = this.getAttribute('data-durasi');
            document.getElementById('edit_token').value = this.getAttribute('data-token');
            document.getElementById('edit_status').value = this.getAttribute('data-status');
        });
    });
</script>
</body>
</html>