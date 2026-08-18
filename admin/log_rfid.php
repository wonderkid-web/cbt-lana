<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- LOGIKA FILTER TANGGAL ---
 $tanggal_filter = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// --- LOGIKA EXPORT EXCEL ---
if (isset($_GET['export'])) {
    $exp_tanggal = $_GET['tanggal'] ?? date('Y-m-d');
    
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Log_Absensi_RIFD_" . date('d-m-Y', strtotime($exp_tanggal)) . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $query_export = mysqli_query($conn, "SELECT lr.*, s.nama_siswa, s.nisn, k.nama_kelas 
        FROM log_rfid lr 
        JOIN siswa s ON lr.siswa_id = s.id_siswa 
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE DATE(lr.waktu) = '$exp_tanggal'
        ORDER BY lr.waktu DESC");

    echo "<html>";
    echo "<head><meta charset='UTF-8'></head>";
    echo "<body>";
    echo "<h3 style='text-align:center;'>Log Absensi RFID - " . date('d F Y', strtotime($exp_tanggal)) . "</h3>";
    echo "<h4 style='text-align:center;'>SMK Putra Anda Binjai</h4>";
    echo "<p style='text-align:center;'>Diekspor pada: " . date('d-m-Y H:i:s') . "</p>";
    echo "<br>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse; width:100%;'>";
    echo "<thead>";
    echo "<tr style='background-color:#3b82f6; color:#fff; font-weight:bold; text-align:center;'>";
    echo "<th>No</th>";
    echo "<th>Waktu Tap</th>";
    echo "<th>Nama Siswa</th>";
    echo "<th>NISN</th>";
    echo "<th>Kelas</th>";
    echo "<th>UID Kartu</th>";
    echo "<th>Status</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    if (mysqli_num_rows($query_export) > 0) {
        $no = 1;
        while ($e = mysqli_fetch_assoc($query_export)) {
            echo "<tr>";
            echo "<td style='text-align:center;'>" . $no++ . "</td>";
            echo "<td style='text-align:center;'>" . date('H:i:s', strtotime($e['waktu'])) . "</td>";
            echo "<td>" . $e['nama_siswa'] . "</td>";
            echo "<td style='text-align:center;'>" . $e['nisn'] . "</td>";
            echo "<td style='text-align:center;'>" . ($e['nama_kelas'] ?? '-') . "</td>";
            echo "<td style='text-align:center; font-family:monospace;'>" . $e['uid_rfid'] . "</td>";
            echo "<td style='text-align:center;'>BERHASIL</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center;'>Tidak ada data log absensi pada tanggal ini.</td></tr>";
    }

    echo "</tbody>";
    echo "</table>";

    // Ringkasan
    $total_export = mysqli_num_rows($query_export);
    echo "<br>";
    echo "<p><strong>Total Siswa Tap: " . $total_export . "</strong></p>";

    echo "</body>";
    echo "</html>";
    exit;
}

// --- LOGIKA PAGINATION ---
 $batas = 15; 
 $halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
 $halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

 $query_hitung = mysqli_query($conn, "SELECT COUNT(*) as total FROM log_rfid WHERE DATE(waktu) = '$tanggal_filter'");
 $res_hitung = mysqli_fetch_assoc($query_hitung);
 $total_data = $res_hitung['total'];
 $total_halaman = ceil($total_data / $batas);

 $query_log = mysqli_query($conn, "SELECT lr.*, s.nama_siswa, s.nisn 
    FROM log_rfid lr 
    JOIN siswa s ON lr.siswa_id = s.id_siswa 
    WHERE DATE(lr.waktu) = '$tanggal_filter'
    ORDER BY lr.waktu DESC 
    LIMIT $halaman_awal, $batas");

// Statistik Hari Ini
 $stat_hari_ini = mysqli_query($conn, "SELECT COUNT(*) as total FROM log_rfid WHERE DATE(waktu) = CURDATE()");
 $total_hari_ini = mysqli_fetch_assoc($stat_hari_ini)['total'];

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
    <title>Log RFID | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
        .card-stat { border: none; border-radius: 12px; transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
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
                <h4 class="fw-bold mb-0">Log Absensi RFID</h4>
                <p class="text-muted small mb-0">Riwayat siswa yang berhasil melakukan tap kartu</p>
            </div>
            <!-- FILTER TANGGAL + EXPORT -->
            <form method="GET" action="" class="d-flex gap-2 align-items-center flex-wrap">
                <input type="date" name="tanggal" value="<?= $tanggal_filter ?>" class="form-control form-control-sm" style="width: auto;">
                <button type="submit" class="btn btn-sm btn-primary shadow-sm rounded-pill px-3">
                    <i class="bi bi-search me-1"></i>Terapkan
                </button>
                <?php if($tanggal_filter !== date('Y-m-d')): ?>
                    <a href="log_rfid.php" class="btn btn-sm btn-outline-secondary shadow-sm rounded-pill px-3">Hari Ini</a>
                <?php endif; ?>
                <a href="log_rfid.php?export=1&tanggal=<?= $tanggal_filter ?>" class="btn btn-sm btn-success shadow-sm rounded-pill px-3">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                </a>
            </form>
        </div>

        <!-- STATISTIK RINGKAS -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card card-stat shadow-sm p-3 text-white" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="opacity-75">Total Tap Terpilih</small>
                            <h3 class="fw-bold mb-0 mt-1"><?= $total_data; ?></h3>
                        </div>
                        <i class="bi bi-journal-check fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat shadow-sm p-3 text-white" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="opacity-75">Tap Hari Ini</small>
                            <h3 class="fw-bold mb-0 mt-1"><?= $total_hari_ini; ?></h3>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat shadow-sm p-3 border-0 bg-white">
                    <div class="d-flex justify-content-between align-items-center text-dark">
                        <div>
                            <small class="text-muted">Filter Tanggal</small>
                            <h5 class="fw-bold mb-0 mt-1"><?= date('d M Y', strtotime($tanggal_filter)); ?></h5>
                        </div>
                        <i class="bi bi-calendar3 fs-1 text-muted opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL LOG -->
        <div class="card card-custom overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="px-4 py-3 border-0">No</th>
                            <th class="px-4 py-3 border-0">Waktu Tap</th>
                            <th class="px-4 py-3 border-0">Identitas Siswa</th>
                            <th class="border-0">UID Kartu</th>
                            <th class="text-center border-0">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php if(mysqli_num_rows($query_log) > 0): ?>
                            <?php while($l = mysqli_fetch_assoc($query_log)): ?>
                            <tr>
                                <td class="px-4"><?= $no++; ?></td>
                                <td class="px-4">
                                    <span class="badge bg-light text-dark border px-3 py-2">
                                        <i class="bi bi-clock me-1"></i> <?= date('H:i:s', strtotime($l['waktu'])); ?>
                                    </span>
                                </td>
                                <td class="px-4">
                                    <div class="fw-bold text-dark"><?= $l['nama_siswa']; ?></div>
                                    <small class="text-muted">NISN: <?= $l['nisn']; ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border px-3" style="font-family: monospace;"><?= $l['uid_rfid']; ?></span></td>
                                <td class="text-center">
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2">
                                        <i class="bi bi-check-circle-fill me-1"></i> BERHASIL
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                Tidak ada log absensi pada tanggal ini.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <small class="text-muted">Menampilkan <b><?= mysqli_num_rows($query_log); ?></b> data log</small>
                    <?php if($total_halaman > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <?php $link_search = "&tanggal=$tanggal_filter"; ?>
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
</script>
</body>
</html>