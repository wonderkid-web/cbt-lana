<?php
session_start();
require_once 'config/database.php';

// Menangkap pesan dari URL jika ada (seperti msg=logout)
 $msg = isset($_GET['msg']) ? $_GET['msg'] : '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    
    if (mysqli_num_rows($query) === 1) {
        $row = mysqli_fetch_assoc($query);

        if (password_verify($password, $row['password'])) {
            
            // --- LOGIKA STATUS LOGIN (SUDAH DIPERBAIKI) ---
            // Cek status untuk GURU dan SISWA. Jika status bukan 'aktif', tolak login.
            if (($row['role'] == 'guru' || $row['role'] == 'siswa') && $row['status'] !== 'aktif') {
                header("Location: login.php?msg=nonaktif");
                exit;
            }

            // Jika lolos (atau jika dia Admin), buat session
            $_SESSION['id_user']  = $row['id_user'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role'];

                        // Redirect sesuai role
            if ($row['role'] == 'admin') {
                header("Location: admin/dashboard.php");
                exit;
            } elseif ($row['role'] == 'guru') {
                header("Location: guru/dashboard.php");
                exit;
            } else {
                // SISWA: Selalu wajib tap RFID dulu sebelum masuk
                header("Location: siswa/waiting_rfid.php");
                exit;
            }
        } else {
            $error = "Password yang Anda masukkan salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SMK Putra Anda Binjai</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-bg: #0f172a;
            --accent-color: #3b82f6;
        }
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .notification-area {
            width: 100%;
            max-width: 420px;
            margin-bottom: 10px;
        }
        .card-login {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .card-header-custom {
            background: var(--primary-bg);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .btn-primary-custom {
            background-color: var(--accent-color);
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary-custom:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
        }
        .form-control-custom {
            padding: 12px 15px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="notification-area">
        <?php if ($msg == 'nonaktif') : ?>
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-3" style="border-radius: 15px;">
                <i class="bi bi-shield-lock-fill fs-4 me-3"></i>
                <div>
                    <small class="d-block fw-bold">Akses Ditolak</small>
                    <small>Akun Anda telah dinonaktifkan oleh Administrator. Hubungi Admin untuk informasi lebih lanjut.</small>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($msg == 'logout') : ?>
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-3" style="border-radius: 15px;">
                <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                <div><small>Sesi berakhir. Anda telah keluar dengan aman.</small></div>
            </div>
        <?php endif; ?>

        <?php if (isset($error)) : ?>
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-3" style="border-radius: 15px;">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <div><small><?= $error; ?></small></div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card card-login">
        <div class="card-header-custom">
            <img src="assets/img/LogoPa.png" alt="Logo Sekolah" style="width: 5rem; height: 5rem;">
            <h4>Computer Based Test</h4>
            <p>SMKS Putra Anda Binjai</p>
        </div>
        
        <div class="card-body p-4 p-md-5">
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">USERNAME</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0" style="border-radius: 12px 0 0 12px;">
                            <i class="bi bi-person text-muted"></i>
                        </span>
                        <input type="text" name="username" class="form-control form-control-custom border-start-0" placeholder="Masukkan Username" required style="border-radius: 0 12px 12px 0;">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">PASSWORD</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0" style="border-radius: 12px 0 0 12px;">
                            <i class="bi bi-lock text-muted"></i>
                        </span>
                        <input type="password" name="password" class="form-control form-control-custom border-start-0" placeholder="Masukkan Password" required style="border-radius: 0 12px 12px 0;">
                    </div>
                </div>

                <button type="submit" name="login" class="btn btn-primary btn-primary-custom w-100 shadow-sm">
                    Masuk Sekarang <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </form>
        </div>
    </div>
    
    <div class="text-center mt-4">
        <p class="text-muted small">&copy; 2026 SMK Putra Anda Binjai. <br> Powered by Justlannn.</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Otomatis hilangkan notifikasi setelah 4 detik
    setTimeout(function() {
        const alerts = document.querySelectorAll('.notification-area .alert');
        alerts.forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            // Hapus elemen dari HTML setelah animasi selesai
            setTimeout(() => alert.remove(), 500);
        });
    }, 4000); // 4000 = 4 detik. Bisa diubah sesuai keinginan (3000 = 3 detik, 5000 = 5 detik)
</script>
</body>
</html>