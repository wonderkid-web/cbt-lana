<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- LOGIKA PROSES DATA (CRUD) ---

// 1. Tambah Mapel
if (isset($_POST['tambah_mapel'])) {
    $nama_mapel  = mysqli_real_escape_string($conn, trim($_POST['nama_mapel'] ?? ''));
    $jenis_mapel = mysqli_real_escape_string($conn, $_POST['jenis_mapel'] ?? 'umum');
    $id_jurusan  = ($jenis_mapel === 'kejuruan' && !empty($_POST['id_jurusan'])) ? (int)$_POST['id_jurusan'] : 'NULL';

    // Validasi
    if (empty($nama_mapel)) {
        header("Location: mapel.php?msg=nama_kosong"); exit;
    }
    if ($jenis_mapel === 'kejuruan' && $id_jurusan === 'NULL') {
        header("Location: mapel.php?msg=jurusan_wajib"); exit;
    }

    // Cek duplikat nama mapel dalam jurusan yang sama
    if ($jenis_mapel === 'umum') {
        $cek = mysqli_query($conn, "SELECT id_mapel FROM mapel WHERE nama_mapel = '$nama_mapel' AND jenis_mapel = 'umum' LIMIT 1");
    } else {
        $cek = mysqli_query($conn, "SELECT id_mapel FROM mapel WHERE nama_mapel = '$nama_mapel' AND id_jurusan = '$id_jurusan' LIMIT 1");
    }
    if (mysqli_num_rows($cek) > 0) {
        header("Location: mapel.php?msg=duplicate"); exit;
    }

    $sql = "INSERT INTO mapel (nama_mapel, jenis_mapel, id_jurusan) VALUES ('$nama_mapel', '$jenis_mapel', $id_jurusan)";
    if (mysqli_query($conn, $sql)) {
        header("Location: mapel.php?msg=ditambah"); exit;
    } else {
        header("Location: mapel.php?msg=gagal"); exit;
    }
}

// 2. Edit Mapel
if (isset($_POST['edit_mapel'])) {
    $id_mapel    = (int)$_POST['id_mapel'];
    $nama_mapel  = mysqli_real_escape_string($conn, trim($_POST['nama_mapel'] ?? ''));
    $jenis_mapel = mysqli_real_escape_string($conn, $_POST['jenis_mapel'] ?? 'umum');
    $id_jurusan  = ($jenis_mapel === 'kejuruan' && !empty($_POST['id_jurusan'])) ? (int)$_POST['id_jurusan'] : 'NULL';

    // Validasi
    if (empty($nama_mapel)) {
        header("Location: mapel.php?msg=nama_kosong"); exit;
    }
    if ($jenis_mapel === 'kejuruan' && $id_jurusan === 'NULL') {
        header("Location: mapel.php?msg=jurusan_wajib"); exit;
    }

    // Cek duplikat (kecuali dirinya sendiri)
    if ($jenis_mapel === 'umum') {
        $cek = mysqli_query($conn, "SELECT id_mapel FROM mapel WHERE nama_mapel = '$nama_mapel' AND jenis_mapel = 'umum' AND id_mapel != '$id_mapel' LIMIT 1");
    } else {
        $cek = mysqli_query($conn, "SELECT id_mapel FROM mapel WHERE nama_mapel = '$nama_mapel' AND id_jurusan = '$id_jurusan' AND id_mapel != '$id_mapel' LIMIT 1");
    }
    if (mysqli_num_rows($cek) > 0) {
        header("Location: mapel.php?msg=duplicate"); exit;
    }

    $sql = "UPDATE mapel SET nama_mapel = '$nama_mapel', jenis_mapel = '$jenis_mapel', id_jurusan = $id_jurusan WHERE id_mapel = '$id_mapel'";
    if (mysqli_query($conn, $sql)) {
        header("Location: mapel.php?msg=diedit"); exit;
    } else {
        header("Location: mapel.php?msg=gagal"); exit;
    }
}

// 3. Hapus Mapel
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Cek apakah mapel digunakan di tabel lain
    $cek_guru = mysqli_query($conn, "SELECT COUNT(*) as total FROM guru WHERE id_mapel = '$id'");
    $cek_bank = mysqli_query($conn, "SELECT COUNT(*) as total FROM bank_soal WHERE id_mapel = '$id'");
    $cek_sesi = mysqli_query($conn, "SELECT COUNT(*) as total FROM sesi_ujian WHERE id_mapel = '$id'");
    
    $used = (int)(mysqli_fetch_assoc($cek_guru)['total']) + (int)(mysqli_fetch_assoc($cek_bank)['total']) + (int)(mysqli_fetch_assoc($cek_sesi)['total']);
    
    if ($used > 0) {
        header("Location: mapel.php?msg=terpakai"); exit;
    }
    
    mysqli_query($conn, "DELETE FROM mapel WHERE id_mapel = '$id'");
    header("Location: mapel.php?msg=dihapus"); exit;
}

// --- AMBIL DATA ---

// Data Jurusan untuk dropdown
$query_jurusan = mysqli_query($conn, "SELECT * FROM jurusan ORDER BY nama_program ASC");

// Main query - JOIN jurusan
$query_mapel = mysqli_query($conn, "SELECT m.*, j.nama_program, j.kode AS kode_jurusan
                                     FROM mapel m
                                     LEFT JOIN jurusan j ON m.id_jurusan = j.id_jurusan
                                     ORDER BY m.jenis_mapel ASC, m.id_mapel ASC");
$total_mapel = mysqli_num_rows($query_mapel);

// Statistik ringkas
$q_umum = mysqli_query($conn, "SELECT COUNT(*) as total FROM mapel WHERE jenis_mapel = 'umum'");
$q_kejuruan = mysqli_query($conn, "SELECT COUNT(*) as total FROM mapel WHERE jenis_mapel = 'kejuruan'");
$q_jurusan_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM jurusan");
$total_umum = mysqli_fetch_assoc($q_umum)['total'];
$total_kejuruan = mysqli_fetch_assoc($q_kejuruan)['total'];
$total_jurusan = mysqli_fetch_assoc($q_jurusan_total)['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Pelajaran | Admin CBT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .stat-card { border-radius: 16px; border: 1px solid #e2e8f0; background: #fff; padding: 20px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
        .stat-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; }
        .stat-value { font-size: 1.6rem; font-weight: 800; line-height: 1; }
        .stat-label { font-size: 0.75rem; color: #64748b; margin-top: 4px; }

        /* Radio button custom styling */
        .jenis-radio-group .form-check-input:checked {
            background-color: var(--accent);
            border-color: var(--accent);
        }
        .jenis-radio-group .form-check {
            padding-left: 1.8em;
        }
        .jenis-radio-group .form-check-label {
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Jurusan dropdown transition */
        #jurusanWrapper {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.35s ease, opacity 0.3s ease, margin 0.3s ease;
            margin-bottom: 0;
        }
        #jurusanWrapper.show {
            max-height: 120px;
            opacity: 1;
            margin-bottom: 1rem;
        }
    </style>
    <!-- Sidebar: grouping menu + mode mini -->
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=4">
    <script src="../assets/js/sidebar.js?v=4"></script>
</head>
<body>

<!-- Overlay gelap (hanya aktif di mobile) -->
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
        <div class="alert alert-<?= in_array($_GET['msg'], ['gagal','nama_kosong','jurusan_wajib','duplicate','terpakai']) ? 'danger' : 'success'; ?> border-0 shadow-sm mb-4 d-flex align-items-center">
            <i class="bi bi-<?= in_array($_GET['msg'], ['gagal','nama_kosong','jurusan_wajib','duplicate','terpakai']) ? 'exclamation-triangle-fill' : 'check-circle-fill'; ?> me-2"></i>
            <?php 
            $msgs = [
                'ditambah'  => 'Mata pelajaran berhasil ditambahkan!',
                'diedit'    => 'Mata pelajaran berhasil diperbarui!',
                'dihapus'   => 'Mata pelajaran berhasil dihapus!',
                'nama_kosong'  => 'Nama mata pelajaran tidak boleh kosong.',
                'jurusan_wajib' => 'Jenis Kejuruan wajib memilih Program Keahlian.',
                'duplicate' => 'Nama mata pelajaran sudah ada untuk jenis/cakupan tersebut.',
                'terpakai'  => 'Mata pelajaran tidak dapat dihapus karena masih digunakan (guru, soal, atau ujian).',
                'gagal'     => 'Operasi gagal. Periksa kembali data yang diisi.',
            ];
            echo $msgs[$_GET['msg']] ?? 'Operasi berhasil.';
            ?>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-journal-bookmark me-2"></i>Kelola Mata Pelajaran</h5>
                <p class="text-muted small mb-0">Tambah, edit, dan hapus data mata pelajaran (umum & kejuruan)</p>
            </div>
            <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i> Tambah Mapel
            </button>
        </div>

        <!-- Statistik Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">TOTAL MAPEL</div>
                            <div class="stat-value text-dark mt-1"><?= $total_mapel; ?></div>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-journal-bookmark"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">MAPEL UMUM</div>
                            <div class="stat-value mt-1" style="color:#0ea5e9;"><?= $total_umum; ?></div>
                        </div>
                        <div class="stat-icon" style="background:#e0f2fe;color:#0ea5e9;"><i class="bi bi-globe2"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">MAPEL KEJURUAN</div>
                            <div class="stat-value mt-1" style="color:#f59e0b;"><?= $total_kejuruan; ?></div>
                        </div>
                        <div class="stat-icon" style="background:#fef3c7;color:#f59e0b;"><i class="bi bi-tools"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">PROGRAM KEAHLIAN</div>
                            <div class="stat-value text-success mt-1"><?= $total_jurusan; ?></div>
                        </div>
                        <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-bookmark-star"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL MAPEL -->
        <div class="card card-custom overflow-hidden">
            <div class="card-header bg-white border-bottom-0 pt-3 px-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-table me-2"></i>Daftar Mata Pelajaran</h6>
                    <span class="badge bg-light text-muted border px-3 py-2" style="font-size:0.78rem;"><?= $total_mapel; ?> mapel</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-3 text-center" width="5%">No</th>
                            <th width="35%">Nama Mata Pelajaran</th>
                            <th class="text-center" width="15%">Jenis</th>
                            <th class="text-center" width="25%">Cakupan</th>
                            <th class="text-center" width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_mapel > 0):
                            $no = 1;
                            while ($m = mysqli_fetch_assoc($query_mapel)):
                                $is_kejuruan = ($m['jenis_mapel'] === 'kejuruan');
                        ?>
                        <tr>
                            <td class="text-center px-3 fw-bold text-muted"><?= $no++; ?></td>
                            <td>
                                <div class="fw-bold text-dark" style="font-size:0.88rem;">
                                    <?= htmlspecialchars($m['nama_mapel']); ?>
                                </div>
                                <?php if ($is_kejuruan && !empty($m['kode_jurusan'])): ?>
                                    <small class="text-muted"><i class="bi bi-barcode me-1"></i><?= htmlspecialchars($m['kode_jurusan']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($is_kejuruan): ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning rounded-pill px-3 py-1" style="font-size:0.78rem; font-weight:600;">
                                        <i class="bi bi-tools me-1"></i>Kejuruan
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info rounded-pill px-3 py-1" style="font-size:0.78rem; font-weight:600;">
                                        <i class="bi bi-globe2 me-1"></i>Umum
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($is_kejuruan && !empty($m['nama_program'])): ?>
                                    <span class="fw-semibold text-dark" style="font-size:0.85rem;">
                                        <i class="bi bi-bookmark-star text-warning me-1"></i><?= htmlspecialchars($m['nama_program']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:0.85rem;">
                                        <i class="bi bi-people me-1"></i>Semua Jurusan
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-info edit-btn me-1"
                                        data-id="<?= $m['id_mapel']; ?>"
                                        data-nama="<?= htmlspecialchars($m['nama_mapel']); ?>"
                                        data-jenis="<?= $m['jenis_mapel']; ?>"
                                        data-jurusan="<?= $m['id_jurusan'] ?? ''; ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit"
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="mapel.php?hapus=<?= $m['id_mapel']; ?>" class="btn btn-sm btn-outline-danger" title="Hapus"
                                   onclick="return confirmHapus('<?= htmlspecialchars(addslashes($m['nama_mapel'])); ?>', '<?= $m['id_mapel']; ?>')">
                                    <i class="bi bi-trash3"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-journal-bookmark fs-1 d-block mb-2 opacity-25"></i>
                                <strong>Belum ada mata pelajaran</strong><br>
                                <small>Klik "Tambah Mapel" untuk menambahkan mata pelajaran baru.</small>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ========== MODAL TAMBAH MAPEL ========== -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="mapel.php" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Mata Pelajaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Nama Mapel -->
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">NAMA MATA PELAJARAN</label>
                    <input type="text" name="nama_mapel" class="form-control" placeholder="Contoh: Matematika, PPLG, TKJ..." required autofocus>
                </div>

                <!-- Jenis Mapel -->
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-2">JENIS MAPEL</label>
                    <div class="jenis-radio-group d-flex gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="jenis_mapel" id="jenis_umum_tambah" value="umum" checked onchange="toggleJurusanTambah()">
                            <label class="form-check-label" for="jenis_umum_tambah">
                                <i class="bi bi-globe2 me-1 text-info"></i>Umum
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="jenis_mapel" id="jenis_kejuruan_tambah" value="kejuruan" onchange="toggleJurusanTambah()">
                            <label class="form-check-label" for="jenis_kejuruan_tambah">
                                <i class="bi bi-tools me-1 text-warning"></i>Kejuruan
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Jurusan (hidden by default) -->
                <div id="jurusanWrapperTambah">
                    <label class="small fw-bold text-muted mb-1">PROGRAM KEAHLIAN</label>
                    <select name="id_jurusan" id="id_jurusan_tambah" class="form-select">
                        <option value="">-- Pilih Program Keahlian --</option>
                        <?php while ($j = mysqli_fetch_assoc($query_jurusan)): ?>
                            <option value="<?= $j['id_jurusan']; ?>"><?= htmlspecialchars($j['kode'] . ' - ' . $j['nama_program']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Info -->
                <div class="alert alert-info small py-2 mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Umum:</strong> Berlaku untuk semua jurusan. <strong>Kejuruan:</strong> Khusus untuk satu program keahlian.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_mapel" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-save me-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL EDIT MAPEL ========== -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="mapel.php" method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="id_mapel" id="edit_id">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Mata Pelajaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Nama Mapel -->
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">NAMA MATA PELAJARAN</label>
                    <input type="text" name="nama_mapel" id="edit_nama" class="form-control" required>
                </div>

                <!-- Jenis Mapel -->
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-2">JENIS MAPEL</label>
                    <div class="jenis-radio-group d-flex gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="jenis_mapel" id="jenis_umum_edit" value="umum" onchange="toggleJurusanEdit()">
                            <label class="form-check-label" for="jenis_umum_edit">
                                <i class="bi bi-globe2 me-1 text-info"></i>Umum
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="jenis_mapel" id="jenis_kejuruan_edit" value="kejuruan" onchange="toggleJurusanEdit()">
                            <label class="form-check-label" for="jenis_kejurusan_edit">
                                <i class="bi bi-tools me-1 text-warning"></i>Kejuruan
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Jurusan (hidden by default) -->
                <div id="jurusanWrapperEdit">
                    <label class="small fw-bold text-muted mb-1">PROGRAM KEAHLIAN</label>
                    <select name="id_jurusan" id="edit_jurusan" class="form-select">
                        <option value="">-- Pilih Program Keahlian --</option>
                        <?php 
                        mysqli_data_seek($query_jurusan, 0);
                        while ($j = mysqli_fetch_assoc($query_jurusan)): ?>
                            <option value="<?= $j['id_jurusan']; ?>"><?= htmlspecialchars($j['kode'] . ' - ' . $j['nama_program']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Info -->
                <div class="alert alert-info small py-2 mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Umum:</strong> Berlaku untuk semua jurusan. <strong>Kejuruan:</strong> Khusus untuk satu program keahlian.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit_mapel" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-save me-1"></i> Perbarui
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ===== SIDEBAR TOGGLE =====
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

    // ===== JURUSAN DROPDOWN TOGGLE =====
    function toggleJurusanTambah() {
        const isKejuruan = document.getElementById('jenis_kejuruan_tambah').checked;
        const wrapper = document.getElementById('jurusanWrapperTambah');
        const select = document.getElementById('id_jurusan_tambah');

        if (isKejuruan) {
            wrapper.style.maxHeight = '120px';
            wrapper.style.opacity = '1';
            wrapper.style.marginTop = '0';
            select.setAttribute('required', '');
        } else {
            wrapper.style.maxHeight = '0';
            wrapper.style.opacity = '0';
            wrapper.style.marginTop = '-0.5rem';
            select.removeAttribute('required');
            select.value = '';
        }
    }

    function toggleJurusanEdit() {
        const isKejuruan = document.getElementById('jenis_kejuruan_edit').checked;
        const wrapper = document.getElementById('jurusanWrapperEdit');
        const select = document.getElementById('edit_jurusan');

        if (isKejuruan) {
            wrapper.style.maxHeight = '120px';
            wrapper.style.opacity = '1';
            wrapper.style.marginTop = '0';
            select.setAttribute('required', '');
        } else {
            wrapper.style.maxHeight = '0';
            wrapper.style.opacity = '0';
            wrapper.style.marginTop = '-0.5rem';
            select.removeAttribute('required');
            select.value = '';
        }
    }

    // Init: hide jurusan wrapper on load for tambah (default = umum)
    document.getElementById('jurusanWrapperTambah').style.maxHeight = '0';
    document.getElementById('jurusanWrapperTambah').style.opacity = '0';
    document.getElementById('jurusanWrapperTambah').style.marginTop = '-0.5rem';

    // ===== EDIT MODAL POPULATE =====
    document.querySelectorAll('.edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_id').value     = this.dataset.id;
            document.getElementById('edit_nama').value    = this.dataset.nama;
            document.getElementById('edit_jurusan').value = this.dataset.jurusan;

            const jenis = this.dataset.jenis;
            if (jenis === 'kejuruan') {
                document.getElementById('jenis_kejuruan_edit').checked = true;
            } else {
                document.getElementById('jenis_umum_edit').checked = true;
            }
            toggleJurusanEdit();
        });
    });

    // ===== DELETE CONFIRMATION (SweetAlert2) =====
    function confirmHapus(nama, id) {
        Swal.fire({
            title: 'Hapus Mata Pelajaran?',
            html: 'Anda akan menghapus <strong>' + nama + '</strong>.<br>Tindakan ini tidak dapat dibatalkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = 'mapel.php?hapus=' + id;
            }
        });
        return false;
    }
</script>
</body>
</html>
