<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- AMBIL DATA UNTUK FILTER ---
$query_kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
$query_mapel_list = mysqli_query($conn, "SELECT * FROM mapel ORDER BY nama_mapel ASC");
$query_sesi_list = mysqli_query($conn, "SELECT su.id_sesi, su.nama_ujian, m.nama_mapel, k.nama_kelas 
                                       FROM sesi_ujian su
                                       JOIN mapel m ON su.id_mapel = m.id_mapel
                                       LEFT JOIN kelas k ON su.id_kelas = k.id_kelas
                                       ORDER BY su.tgl_mulai DESC");

// --- FILTER GET ---
$filter_kelas = isset($_GET['filter_kelas']) ? (int)$_GET['filter_kelas'] : 0;
$filter_mapel = isset($_GET['filter_mapel']) ? (int)$_GET['filter_mapel'] : 0;
$filter_sesi = isset($_GET['filter_sesi']) ? (int)$_GET['filter_sesi'] : 0;
$filter_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// --- BUILD QUERY ---
$where_conditions = ["u.status = 'selesai'"];
$params = [];

if ($filter_kelas > 0) {
    $where_conditions[] = "s.id_kelas = '$filter_kelas'";
}
if ($filter_mapel > 0) {
    $where_conditions[] = "u.id_mapel = '$filter_mapel'";
}
if ($filter_sesi > 0) {
    $where_conditions[] = "u.id_sesi = '$filter_sesi'";
}
if (!empty($filter_search)) {
    $where_conditions[] = "(s.nama_siswa LIKE '%$filter_search%' OR s.nis LIKE '%$filter_search%')";
}

$where_sql = implode(" AND ", $where_conditions);

// --- PAGINATION ---
$batas = 10;
$halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;
$sebelum = $halaman - 1;
$sesudah = $halaman + 1;

// Hitung total data
$base_query = "FROM ujian u
               JOIN siswa s ON u.id_user = s.id_user
               JOIN kelas k ON s.id_kelas = k.id_kelas
               JOIN mapel m ON u.id_mapel = m.id_mapel
               JOIN sesi_ujian su ON u.id_sesi = su.id_sesi
               WHERE $where_sql";

$count_query = mysqli_query($conn, "SELECT COUNT(*) as total $base_query");
$jumlah_data = mysqli_fetch_assoc($count_query)['total'];
$total_halaman = ceil($jumlah_data / $batas);

// Query data utama
$query_rekap = mysqli_query($conn, "SELECT u.id_ujian, u.judul_ujian, u.nilai, u.waktu_mulai, u.waktu_selesai, u.durasi,
                                           s.nama_siswa, s.nisn, s.id_kelas,
                                           k.nama_kelas,
                                           m.nama_mapel,
                                           su.nama_ujian as nama_sesi, su.jenis_ujian, su.tgl_mulai as jadwal_mulai
                                    $base_query
                                    ORDER BY u.nilai DESC, u.waktu_selesai DESC
                                    LIMIT $halaman_awal, $batas");

$nomor = $halaman_awal + 1;

// --- STATISTIK GLOBAL ---
$q_global = mysqli_query($conn, "SELECT 
    COUNT(*) as total_peserta,
    ROUND(AVG(u.nilai), 1) as rata_rata,
    MAX(u.nilai) as nilai_tertinggi,
    MIN(u.nilai) as nilai_terendah,
    SUM(CASE WHEN u.nilai >= 70 THEN 1 ELSE 0 END) as lulus,
    SUM(CASE WHEN u.nilai < 70 THEN 1 ELSE 0 END) as tidak_lulus
    $base_query");
$global = mysqli_fetch_assoc($q_global);

// Helper grade
function getGradeLaporan($nilai) {
    if ($nilai >= 90) return ['grade' => 'A', 'color' => '#16a34a', 'bg' => '#dcfce7'];
    elseif ($nilai >= 80) return ['grade' => 'B', 'color' => '#2563eb', 'bg' => '#dbeafe'];
    elseif ($nilai >= 70) return ['grade' => 'C', 'color' => '#d97706', 'bg' => '#fef3c7'];
    elseif ($nilai >= 60) return ['grade' => 'D', 'color' => '#ea580c', 'bg' => '#ffedd5'];
    else return ['grade' => 'E', 'color' => '#dc2626', 'bg' => '#fee2e2'];
}

// --- LOGIKA EXPORT CSV ---
if (isset($_GET['export'])) {
    $export_query = mysqli_query($conn, "SELECT u.id_ujian, u.judul_ujian, u.nilai, u.waktu_mulai, u.waktu_selesai,
                                               s.nama_siswa, s.nis,
                                               k.nama_kelas,
                                               m.nama_mapel,
                                               su.nama_ujian as nama_sesi, su.jenis_ujian
                                        $base_query
                                        ORDER BY k.nama_kelas ASC, u.nilai DESC");

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=rekap_nilai_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['No', 'NIS', 'Nama Siswa', 'Kelas', 'Mata Pelajaran', 'Nama Ujian', 'Jenis', 'Nilai', 'Grade', 'Tanggal']);

    $no = 1;
    while ($row = mysqli_fetch_assoc($export_query)) {
        $g = getGradeLaporan($row['nilai']);
        fputcsv($output, [
            $no++,
            $row['nis'],
            $row['nama_siswa'],
            $row['nama_kelas'],
            $row['nama_mapel'],
            $row['nama_sesi'],
            $row['jenis_ujian'],
            number_format($row['nilai'], 0),
            $g['grade'],
            date('d M Y', strtotime($row['waktu_selesai']))
        ]);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Nilai | SMK Putra Anda</title>
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
        
        /* Stat Cards */
        .stat-card {
            background: #fff; border-radius: 14px; border: 1px solid #e2e8f0;
            padding: 18px; text-align: center; transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.05); }
        .stat-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 10px; font-size: 1.2rem;
        }
        .stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
        .stat-label { font-size: 0.75rem; color: #64748b; margin-top: 4px; }

        /* Grade Badge */
        .grade-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 34px; height: 34px; border-radius: 8px; font-weight: 800;
            font-size: 0.9rem; color: #fff;
        }

        /* Filter Section */
        .filter-card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; }
        
        /* Table */
        .table-custom thead th { 
            font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.3px;
            color: #64748b; padding: 12px 14px;
        }

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
            <li class="mb-2"><a href="sesi_ujian.php" class="nav-link"><i class="bi bi-clipboard-check"></i> Sesi Ujian</a></li>
            <li class="mb-2"><a href="laporan_full.php" class="nav-link active"><i class="bi bi-bar-chart-line"></i> Rekap Nilai</a></li>
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
                    <h4 class="fw-bold mb-0">Rekap Nilai Ujian</h4>
                    <p class="text-muted small mb-0">Lihat dan analisis hasil ujian seluruh siswa.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['export' => 1])); ?>" 
                       class="btn btn-success btn-sm rounded-pill px-3 shadow-sm <?= ($jumlah_data == 0) ? 'disabled' : ''; ?>">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i> Export CSV
                    </a>
                </div>
            </div>

            <!-- STATISTIK -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stat-value text-dark"><?= $global['total_peserta'] ?: 0; ?></div>
                        <div class="stat-label">Total Peserta</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#dbeafe;color:#2563eb;">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div class="stat-value" style="color:#2563eb;"><?= $global['rata_rata'] ?: 0; ?></div>
                        <div class="stat-label">Rata-rata</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#dcfce7;color:#16a34a;">
                            <i class="bi bi-trophy-fill"></i>
                        </div>
                        <div class="stat-value" style="color:#16a34a;"><?= $global['nilai_tertinggi'] ?: 0; ?></div>
                        <div class="stat-label">Tertinggi</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fee2e2;color:#dc2626;">
                            <i class="bi bi-arrow-down-circle-fill"></i>
                        </div>
                        <div class="stat-value" style="color:#dc2626;"><?= $global['nilai_terendah'] ?: 0; ?></div>
                        <div class="stat-label">Terendah</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#dcfce7;color:#16a34a;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="stat-value" style="color:#16a34a;"><?= $global['lulus'] ?: 0; ?></div>
                        <div class="stat-label">Lulus (≥70)</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fee2e2;color:#dc2626;">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div class="stat-value" style="color:#dc2626;"><?= $global['tidak_lulus'] ?: 0; ?></div>
                        <div class="stat-label">Tidak Lulus</div>
                    </div>
                </div>
            </div>

            <!-- FILTER -->
            <div class="filter-card p-3 mb-4">
                <form method="GET" action="" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1 d-block">KELAS</label>
                        <select name="filter_kelas" class="form-select form-select-sm">
                            <option value="0">Semua Kelas</option>
                            <?php while($k = mysqli_fetch_assoc($query_kelas_list)): ?>
                                <option value="<?= $k['id_kelas']; ?>" <?= ($filter_kelas == $k['id_kelas']) ? 'selected' : ''; ?>>
                                    <?= $k['nama_kelas']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1 d-block">MATA PELAJARAN</label>
                        <select name="filter_mapel" class="form-select form-select-sm">
                            <option value="0">Semua Mapel</option>
                            <?php while($m = mysqli_fetch_assoc($query_mapel_list)): ?>
                                <option value="<?= $m['id_mapel']; ?>" <?= ($filter_mapel == $m['id_mapel']) ? 'selected' : ''; ?>>
                                    <?= $m['nama_mapel']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1 d-block">SESI UJIAN</label>
                        <select name="filter_sesi" class="form-select form-select-sm">
                            <option value="0">Semua Sesi</option>
                            <?php while($su = mysqli_fetch_assoc($query_sesi_list)): ?>
                                <option value="<?= $su['id_sesi']; ?>" <?= ($filter_sesi == $su['id_sesi']) ? 'selected' : ''; ?>>
                                    <?= $su['nama_ujian']; ?> - <?= $su['nama_mapel']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <input type="text" name="search" class="form-control form-control-sm" 
                               placeholder="Cari nama/NIS..." value="<?= $filter_search; ?>">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                <?php if ($filter_kelas > 0 || $filter_mapel > 0 || $filter_sesi > 0 || !empty($filter_search)): ?>
                <div class="mt-2">
                    <a href="laporan_full.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="bi bi-x-circle me-1"></i> Reset Filter
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- TABEL REKAP -->
            <div class="card card-custom overflow-hidden">
                <div class="card-header bg-white border-bottom-0 pt-3 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-table me-2"></i>Data Nilai</h6>
                        <span class="badge bg-light text-muted border px-3 py-2" style="font-size:0.78rem;">
                            <?= $jumlah_data; ?> data
                        </span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3 py-3 text-center" width="4%">No</th>
                                <th>Nama Siswa</th>
                                <th class="d-none d-md-table-cell">Kelas</th>
                                <th class="d-none d-lg-table-cell">Mapel</th>
                                <th>Ujian</th>
                                <th class="text-center d-none d-md-table-cell">Tanggal</th>
                                <th class="text-center">Nilai</th>
                                <th class="text-center">Grade</th>
                                <th class="text-center d-none d-md-table-cell">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($jumlah_data > 0): 
                                $n = $nomor;
                                while ($r = mysqli_fetch_assoc($query_rekap)):
                                    $g = getGradeLaporan($r['nilai']);
                                    $lulus = ($r['nilai'] >= 70);
                            ?>
                            <tr>
                                <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size:0.9rem;"><?= $r['nama_siswa']; ?></div>
                                    <small class="text-muted">NISN: <?= $r['nisn'] ?: '-'; ?></small>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="badge bg-light text-dark border"><?= $r['nama_kelas']; ?></span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge bg-info bg-opacity-10 text-info"><?= $r['nama_mapel']; ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= $r['nama_sesi']; ?></div>
                                    <small class="text-muted"><?= $r['jenis_ujian']; ?></small>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <div class="small fw-bold"><?= date('d M Y', strtotime($r['waktu_selesai'])); ?></div>
                                    <div class="small text-muted"><?= date('H:i', strtotime($r['waktu_mulai'])); ?> WIB</div>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold" style="font-size:1.05rem; color:<?= $g['color']; ?>;">
                                        <?= number_format($r['nilai'], 0); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="grade-badge" style="background:<?= $g['color']; ?>;"><?= $g['grade']; ?></span>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php if ($lulus): ?>
                                        <span class="badge" style="background:#dcfce7;color:#166534;font-weight:600;">
                                            <i class="bi bi-check-circle-fill me-1"></i>Lulus
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#fee2e2;color:#991b1b;font-weight:600;">
                                            <i class="bi bi-x-circle-fill me-1"></i>Remedial
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Tidak ada data nilai yang cocok dengan filter.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_halaman > 1): ?>
                <div class="card-footer bg-white border-0 py-3">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-pill px-3 me-2" href="<?= ($halaman > 1) ? "?pagi=$sebelum&" . http_build_query(array_intersect_key($_GET, array_flip(['filter_kelas','filter_mapel','filter_sesi','search']))) : '#'; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php 
                            // Tampilkan max 5 halaman
                            $start_page = max(1, $halaman - 2);
                            $end_page = min($total_halaman, $halaman + 2);
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link rounded-pill mx-1" href="?pagi=1&' . http_build_query(array_intersect_key($_GET, array_flip(['filter_kelas','filter_mapel','filter_sesi','search']))) . '">1</a></li>';
                                if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            for ($x = $start_page; $x <= $end_page; $x++):
                            ?>
                                <li class="page-item <?= ($halaman == $x) ? 'active' : ''; ?>">
                                    <a class="page-link rounded-pill mx-1" href="?pagi=<?= $x; ?>&<?= http_build_query(array_intersect_key($_GET, array_flip(['filter_kelas','filter_mapel','filter_sesi','search']))); ?>"><?= $x; ?></a>
                                </li>
                            <?php endfor; 
                            if ($end_page < $total_halaman) {
                                if ($end_page < $total_halaman - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                echo '<li class="page-item"><a class="page-link rounded-pill mx-1" href="?pagi=' . $total_halaman . '&' . http_build_query(array_intersect_key($_GET, array_flip(['filter_kelas','filter_mapel','filter_sesi','search']))) . '">' . $total_halaman . '</a></li>';
                            }
                            ?>
                            <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-pill px-3 ms-2" href="<?= ($halaman < $total_halaman) ? "?pagi=$sesudah&" . http_build_query(array_intersect_key($_GET, array_flip(['filter_kelas','filter_mapel','filter_sesi','search']))) : '#'; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <div class="text-center mt-2">
                        <small class="text-muted">Total Data: <b><?= $jumlah_data; ?></b> | Halaman <b><?= $halaman; ?></b> dari <b><?= $total_halaman; ?></b></small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('toggleBtn').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
</script>
</body>
</html>