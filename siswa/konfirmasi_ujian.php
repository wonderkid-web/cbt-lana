<?php 
date_default_timezone_set('Asia/Jakarta');
require_once 'auth_check.php'; 

// Jika akses langsung tanpa POST, kembali ke daftar ujian
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ujian_list.php");
    exit;
}

$id_sesi = (int)$_POST['id_sesi'];
$id_user = (int)$_SESSION['id_user'];

// Ambil info sesi ujian
$q = mysqli_query($conn, "SELECT sesi_ujian.*, mapel.nama_mapel 
                          FROM sesi_ujian 
                          JOIN mapel ON sesi_ujian.id_mapel = mapel.id_mapel
                          WHERE id_sesi = '$id_sesi'");
$sesi = mysqli_fetch_assoc($q);

// Cek apakah kelas siswa terdaftar di sesi ujian ini (multi-kelas)
$q_cek_kelas = mysqli_query($conn, "SELECT COUNT(*) as total 
                                      FROM sesi_ujian_kelas 
                                      WHERE id_sesi = '$id_sesi' AND id_kelas = (SELECT id_kelas FROM siswa WHERE id_user = '$id_user')");
$cek_kelas = mysqli_fetch_assoc($q_cek_kelas);
if (!$cek_kelas || $cek_kelas['total'] == 0) {
    header("Location: ujian_list.php");
    exit;
}

if (!$sesi) {
    header("Location: ujian_list.php");
    exit;
}

// Cek apakah siswa sudah punya record ujian aktif untuk sesi ini
$q_cek = mysqli_query($conn, "SELECT * FROM ujian WHERE id_user = '$id_user' AND id_sesi = '$id_sesi' AND status = 'sedang_dikerjakan'");
$cek_ujian = mysqli_fetch_assoc($q_cek);

if ($cek_ujian) {
    // Sudah ada record, langsung masuk ke halaman ujian
    $_SESSION['id_ujian_aktif'] = $cek_ujian['id_ujian'];
    header("Location: mulai_ujian.php");
    exit;
}

// Cek apakah siswa sudah selesai mengerjakan ujian ini
$q_selesai = mysqli_query($conn, "SELECT * FROM ujian WHERE id_user = '$id_user' AND id_sesi = '$id_sesi' AND status = 'selesai'");
$selesai = mysqli_fetch_assoc($q_selesai);

if ($selesai) {
    // Sudah selesai, langsung ke hasil
    $_SESSION['id_ujian_aktif'] = $selesai['id_ujian'];
    header("Location: hasil_ujian.php");
    exit;
}

$error = '';

// Jika tombol verifikasi token ditekan
if (isset($_POST['verifikasi_token'])) {
    $input_token = mysqli_real_escape_string($conn, strtoupper($_POST['token']));
    
    // Cek waktu ujian
    $now = strtotime(date('Y-m-d H:i:s'));
    $start = strtotime($sesi['tgl_mulai']);
    $end = $start + ($sesi['durasi'] * 60);
    
    if ($now < $start) {
        $error = "Ujian belum dimulai! Mulai pukul " . date('H:i', $start) . " WIB.";
    } elseif ($now > $end) {
        $error = "Waktu ujian sudah habis!";
    } elseif ($input_token !== $sesi['token']) {
        $error = "Token yang Anda masukkan salah!";
    } else {
        // Token cocok & waktu valid, buat record di tabel ujian
        $waktu_mulai = date('Y-m-d H:i:s');
        $insert = mysqli_query($conn, "INSERT INTO ujian (id_user, id_sesi, id_mapel, judul_ujian, kode_ujian, waktu_mulai, durasi, acak_soal, status) 
                                       VALUES ('$id_user', '$id_sesi', '$sesi[id_mapel]', '$sesi[nama_ujian]', '$sesi[token]', '$waktu_mulai', '$sesi[durasi]', 'Y', 'sedang_dikerjakan')");
        
        if ($insert) {
            $_SESSION['id_ujian_aktif'] = mysqli_insert_id($conn);
            header("Location: mulai_ujian.php");
            exit;
        } else {
            $error = "Gagal memulai ujian. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Token | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; font-family: 'Inter', sans-serif; }
        .card-token { border: none; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .input-token { 
            font-size: 1.8rem; 
            letter-spacing: 12px; 
            text-align: center; 
            font-weight: 700; 
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            transition: border-color 0.3s;
        }
        .input-token:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
        .input-token::placeholder { letter-spacing: 5px; font-size: 1rem; font-weight: 400; color: #94a3b8; }
        .icon-lock { font-size: 3rem; color: #3b82f6; }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card card-token">
                    <div class="card-body p-5 text-center">
                        <div class="mb-4">
                            <i class="bi bi-shield-lock-fill icon-lock"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Masukkan Token Ujian</h5>
                        <p class="text-muted small mb-4"><?= $sesi['nama_ujian']; ?> &mdash; <?= $sesi['nama_mapel']; ?></p>
                        
                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger py-2 small border-0 d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="off">
                            <input type="hidden" name="id_sesi" value="<?= $id_sesi; ?>">
                            <input type="text" name="token" class="form-control form-control-lg input-token mb-4" 
                                   maxlength="5" placeholder="XXXXX" required
                                   style="text-transform: uppercase;"
                                   oninput="this.value = this.value.toUpperCase()">
                            <button type="submit" name="verifikasi_token" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Verifikasi & Mulai
                            </button>
                        </form>
                        <a href="ujian_list.php" class="btn btn-link btn-sm text-muted mt-4 text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Ujian
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-focus input token
    document.querySelector('.input-token').focus();
</script>
</body>
</html>