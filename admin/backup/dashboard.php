<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- AMBIL DATA STATISTIK LENGKAP ---
 $jml_siswa   = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM siswa"));
 $jml_guru    = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM guru"));
 $jml_kelas   = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kelas"));
 $jml_ruang    = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM ruang_ujian"));
 $jml_mapel   = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM mapel"));
 $jml_soal    = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM bank_soal"));
 $jml_ujian    = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM sesi_ujian WHERE status='aktif'"));
  $jml_nilai = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM ujian WHERE status='selesai'"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | SMK Putra Anda</title>
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
    <nav id="sidebar">
        <div class="sidebar-header">
            <h5 class="fw-bold mb-0">SMK PUTRA ANDA BINJAI</h5>
            <small class="opacity-50">Admin</small>
        </div>
        <ul class="nav flex-column p-2 mt-3">
            <li class="mb-2"><a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
            <li class="mb-2"><a href="guru.php" class="nav-link"><i class="bi bi-person-badge"></i> Data Guru</a></li>
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

    <div class="content-area">
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
                <h4 class="fw-bold text-dark">Ringkasan Data Sistem</h4>
                <p class="text-muted small">Berikut adalah ringkasan seluruh data yang tersedia di database CBT.</p>
            </div>

            <!-- Grid 8 Kartu Statistik -->
            <div class="row g-4">
                <!-- 1. Total Siswa -->
                <div class="col-6 col-md-3">
                    <div class="card card-stat p-4 h-100">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <h6 class="text-muted small fw-bold mb-1">TOTAL SISWA</h6>
                        <h2 class="fw-bold mb-0"><?= $jml_siswa; ?></h2>
                    </div>
                </div>

                <!-- 2. Total Guru -->
                <div class="col-6 col-md-3">
                    <div class="card card-stat p-4 h-100">
                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="bi bi-person-workspace fs-4"></i>
                        </div>
                        <h6 class="text-muted small fw-bold mb-1">TOTAL GURU</h6>
                        <h2 class="fw-bold mb-0"><?= $jml_guru; ?></h2>
                    </div>
                </div>

                <!-- 3. Total Kelas -->
                <div class="col-6 col-md-3">
                    <div class="card card-stat p-4 h-100">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-house-door-fill fs-4"></i>
                        </div>
                        <h6 class="text-muted small fw-bold mb-1">JUMLAH KELAS</h6>
                        <h2 class="fw-bold mb-0"><?= $jml_kelas; ?></h2>
                    </div>
                </div>

                <!-- 4. Total Ruang Ujian (BARU) -->
                <div class="col-6 col-md-3">
                    <div class="card card-stat p-4 h-100">
                        <div class="icon-box bg-info bg-opacity-10 text-info">
                            <i class="bi bi-building fs-4"></i>
                        </div>
                        <h6 class="text-muted small fw-bold mb-1">TOTAL RUANG</h6>
                        <h2 class="fw-bold mb-0"><?= $jml_ruang; ?></h2>
                    </div>
                </div>

                <!-- 5. Total Mapel (BARU) -->
                <div class="col-6 col-md-3">
                    <div class="card card-stat p-4 h-100">
                        <div class="icon-box bg-secondary bg-opacity-10 text-secondary">
                            <i class="bi bi-book-half fs-4"></i>
                        </div>
                        <h6 class="text-muted small fw-bold mb-1">TOTAL MAPEL</h6>
                        <h2 class="fw-bold mb-0"><?= $jml_mapel; ?></h2>
                    </div>
                </div>

                <!-- 6. Total Bank Soal (BARU) -->
                <div class="col-6 col-md-3">
                    <div class="card card-stat p-4 h-100">
                        <div class="icon-box bg-dark bg-opacity-10 text-dark">
                            <i class="bi bi-stack fs-4"></i>
                        </div>
                        <h6 class="text-muted small fw-bold mb-1">BANK SOAL</h6>
                        <h2 class="fw-bold mb-0"><?= $jml_soal; ?></h2>
                    </div>
                </div>

                <!-- 7. Ujian Aktif -->
                <div class="col-6 col-md-3">
                    <div class="card card-stat p-4 h-100">
                        <div class="icon-box bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-lightning-charge-fill fs-4"></i>
                        </div>
                        <h6 class="text-muted small fw-bold mb-1">UJIAN AKTIF</h6>
                        <h2 class="fw-bold mb-0"><?= $jml_ujian; ?></h2>
                    </div>
                </div>

                <!-- 8. Hasil Nilai (BARU) -->
                <div class="col-6 col-md-3">
                    <div class="card card-stat p-4 h-100">
                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="bi bi-graph-up-arrow fs-4"></i>
                        </div>
                        <h6 class="text-muted small fw-bold mb-1">HASIL NILAI</h6>
                        <h2 class="fw-bold mb-0"><?= $jml_nilai; ?></h2>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Logika Sidebar Toggle (Sama dengan Kelas.php)
    document.getElementById('toggleBtn').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
</script>
</body>
</html>