<?php 
date_default_timezone_set('Asia/Jakarta');
require_once 'auth_check.php'; 

// Cek session ujian aktif
if (!isset($_SESSION['id_ujian_aktif'])) {
    header("Location: ujian_list.php");
    exit;
}

$id_ujian = (int)$_SESSION['id_ujian_aktif'];
$id_user = (int)$_SESSION['id_user'];

// Ambil data siswa
$q_siswa = mysqli_query($conn, "SELECT siswa.*, kelas.nama_kelas FROM siswa 
                                JOIN kelas ON siswa.id_kelas = kelas.id_kelas 
                                WHERE id_user = '$id_user'");
$s = mysqli_fetch_assoc($q_siswa);

// Ambil data ujian + sesi
$q_ujian = mysqli_query($conn, "SELECT u.*, su.tgl_mulai as jadwal_mulai, su.nama_ujian as nama_sesi,
                                 m.nama_mapel 
                                 FROM ujian u 
                                 JOIN sesi_ujian su ON u.id_sesi = su.id_sesi
                                 JOIN mapel m ON u.id_mapel = m.id_mapel
                                 WHERE u.id_ujian = '$id_ujian'");
$ujian = mysqli_fetch_assoc($q_ujian);

if (!$ujian) {
    unset($_SESSION['id_ujian_aktif']);
    header("Location: ujian_list.php");
    exit;
}

// Jika ujian sudah selesai, redirect ke hasil
if ($ujian['status'] == 'selesai') {
    header("Location: hasil_ujian.php");
    exit;
}

// Hitung waktu tersisa (berdasarkan waktu mulai siswa + durasi)
$waktu_mulai = strtotime($ujian['waktu_mulai']);
$now = strtotime(date('Y-m-d H:i:s'));
$detik_tersisa = ($ujian['durasi'] * 60) - ($now - $waktu_mulai);

if ($detik_tersisa <= 0) {
    // Waktu habis, auto submit
    header("Location: proses_ujian.php?timeout=1");
    exit;
}

// Konversi ke format mm:ss untuk JavaScript
$timer_menit = floor($detik_tersisa / 60);
$timer_detik = $detik_tersisa % 60;
$timer_format = sprintf("%02d:%02d", $timer_menit, $timer_detik);

// Ambil soal dari bank_soal (acak jika acak_soal = 'Y')
$order_by = ($ujian['acak_soal'] == 'Y') ? 'ORDER BY RAND()' : 'ORDER BY id_soal ASC';
$q_soal = mysqli_query($conn, "SELECT * FROM bank_soal WHERE id_mapel = '$ujian[id_mapel]' $order_by");

$soal_list = [];
while ($row = mysqli_fetch_assoc($q_soal)) {
    $soal_list[] = $row;
}
$total_soal = count($soal_list);

if ($total_soal == 0) {
    echo "<script>alert('Tidak ada soal tersedia untuk ujian ini.'); window.location='ujian_list.php';</script>";
    exit;
}

// Simpan ID soal ke session untuk konsistensi (saat refresh tidak berubah urutan)
if (!isset($_SESSION['soal_ids'])) {
    $_SESSION['soal_ids'] = array_column($soal_list, 'id_soal');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ujian Berlangsung | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* === KEAMANAN UJIAN === */
        html, body {
            -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none;
            -webkit-touch-callout: none;
            overscroll-behavior: none; /* Blokir pull-to-refresh & bounce scroll */
            touch-action: manipulation; /* Blokir double-tap zoom */
        }
        * { -webkit-user-drag: none; -khtml-user-drag: none; -moz-user-drag: none; -o-user-drag: none; user-drag: none; }

        /* === VARIABEL & BASE === */
        :root { --sidebar-w: 220px; }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; overflow-x: hidden; }

        /* === ENTRY POINT (LAYAR KOSONG AWAL) === */
        #entryScreen {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #0f172a; z-index: 9999;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            color: #fff; text-align: center; padding: 20px; cursor: pointer;
        }
        #entryScreen .entry-icon { font-size: 5rem; margin-bottom: 20px; opacity: 0.8; }
        #entryScreen h4 { font-size: 1.5rem; margin-bottom: 10px; }
        #entryScreen p { opacity: 0.6; max-width: 300px; font-size: 0.9rem; line-height: 1.5; }
        .btn-mulai {
            margin-top: 30px; background: #22c55e; color: #fff; border: none;
            padding: 15px 40px; border-radius: 50px; font-size: 1.1rem; font-weight: 700;
            box-shadow: 0 10px 30px rgba(34, 197, 94, 0.4); transition: transform 0.2s;
        }
        .btn-mulai:hover { transform: scale(1.05); }
        .btn-mulai:active { transform: scale(0.95); }

        /* === OVERLAY PERINGATAN (SAAT KELUAR DARI FULLSCREEN/TAB) === */
        #lockOverlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.97); z-index: 10000;
            display: none; flex-direction: column; justify-content: center; align-items: center;
            color: #fff; text-align: center; padding: 30px;
        }
        #lockOverlay .lock-icon { font-size: 4rem; margin-bottom: 20px; color: #ef4444; }
        #lockOverlay h4 { font-size: 1.4rem; margin-bottom: 10px; color: #fbbf24; }
        #lockOverlay p { opacity: 0.7; max-width: 340px; font-size: 0.9rem; line-height: 1.6; margin-bottom: 20px; }
        #lockOverlay .violation-counter {
            background: rgba(239, 68, 68, 0.2); border: 2px solid #ef4444;
            border-radius: 12px; padding: 12px 24px; margin-bottom: 20px;
        }
        #lockOverlay .violation-counter .count { font-size: 2rem; font-weight: 800; color: #ef4444; }
        #lockOverlay .violation-counter small { color: #fca5a5; font-size: 0.8rem; }
        .btn-kembali-ujian {
            background: #3b82f6; color: #fff; border: none;
            padding: 14px 36px; border-radius: 50px; font-size: 1rem; font-weight: 700;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4); transition: transform 0.2s;
        }
        .btn-kembali-ujian:hover { transform: scale(1.05); }
        .btn-kembali-ujian:active { transform: scale(0.95); }

        /* === TOP BAR === */
        .top-bar { 
            background: #0f172a; color: #fff; padding: 10px 15px; 
            position: sticky; top: 0; z-index: 100;
        }
        .timer-box {
            background: rgba(255,255,255,0.1); border-radius: 10px; padding: 6px 14px;
            display: flex; align-items: center; gap: 8px;
        }
        .timer-box i { font-size: 1rem; }
        .timer-text { font-size: 1.2rem; font-weight: 700; font-variant-numeric: tabular-nums; letter-spacing: 1px; }
        .timer-danger { background: rgba(239,68,68,0.3) !important; animation: pulse-red 1s infinite; }
        @keyframes pulse-red { 0%,100% { opacity:1; } 50% { opacity:0.7; } }

        /* === INDIKATOR PELANGGARAN DI TOP BAR === */
        .violation-badge {
            background: rgba(239, 68, 68, 0.25); border: 1px solid rgba(239, 68, 68, 0.5);
            border-radius: 6px; padding: 3px 8px; font-size: 0.65rem; font-weight: 700;
            color: #fca5a5; display: flex; align-items: center; gap: 4px;
        }
        .violation-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: #ef4444; }

        /* === SIDEBAR NAVIGATOR (DESKTOP) === */
        .soal-navigator {
            position: fixed; top: 60px; right: 0; bottom: 0; width: var(--sidebar-w);
            background: #fff; border-left: 1px solid #e2e8f0; padding: 12px;
            overflow-y: auto; z-index: 50;
        }
        .main-content { margin-right: var(--sidebar-w); padding: 15px; }

        .nav-soal-btn {
            width: 38px; height: 38px; border-radius: 8px; border: 2px solid #e2e8f0;
            background: #fff; font-weight: 600; font-size: 0.8rem;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s; text-decoration: none; color: #475569;
        }
        .nav-soal-btn:hover { border-color: #3b82f6; color: #3b82f6; }
        .nav-soal-btn.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
        .nav-soal-btn.dijawab { background: #dcfce7; color: #166534; border-color: #86efac; }
        .nav-soal-btn.dijawab.active { background: #16a34a; color: #fff; border-color: #16a34a; }
        .nav-soal-btn.ragu { background: #fef3c7; color: #92400e; border-color: #fbbf24; }
        .nav-soal-btn.ragu.active { background: #f59e0b; color: #fff; border-color: #f59e0b; }

        /* === QUESTION CARD === */
        .question-card {
            background: #fff; border-radius: 14px; box-shadow: 0 2px 15px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
        }
        .question-header { 
            background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
            color: #fff; padding: 12px 18px; border-radius: 14px 14px 0 0; 
        }
        .question-body { padding: 18px; }

        /* === OPTION CARDS === */
        .option-card {
            border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;
            cursor: pointer; transition: all 0.2s; display: flex; align-items: flex-start; gap: 12px;
            margin-bottom: 8px;
        }
        .option-card:hover { border-color: #93c5fd; background: #eff6ff; }
        .option-card.selected { border-color: #3b82f6; background: #dbeafe; }
        .option-card.selected .option-letter { background: #3b82f6; color: #fff; }
        .option-letter {
            width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.85rem; flex-shrink: 0; transition: all 0.2s;
        }
        .option-text { font-size: 0.9rem; color: #334155; line-height: 1.5; padding-top: 3px; }
        .option-card input[type="radio"] { display: none; }

        /* === PROGRESS BAR === */
        .progress-bar-custom { height: 5px; border-radius: 3px; background: #e2e8f0; }
        .progress-fill { height: 100%; border-radius: 3px; background: #3b82f6; transition: width 0.3s; }

        /* === BUTTON NAVIGASI === */
        .btn-nav { border-radius: 10px; padding: 10px 18px; font-weight: 600; font-size: 0.85rem; }
        .btn-submit-finish { 
            background: #fbbf24; color: #000; border: none; border-radius: 10px; 
            padding: 10px 20px; font-weight: 700; font-size: 0.85rem;
        }

        /* === HIDE NATIVE RADIO === */
        input[type="radio"] { display: none; }

        /* === MOBILE NAV MODAL === */
        .btn-lihat-soal {
            display: none; background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25); color: #fff;
            border-radius: 8px; padding: 5px 12px; font-size: 0.75rem;
            font-weight: 600; cursor: pointer; position: relative;
        }
        .btn-lihat-soal:hover { background: rgba(255,255,255,0.25); color: #fff; }
        .btn-lihat-soal .badge-count {
            position: absolute; top: -5px; right: -5px;
            background: #3b82f6; color: #fff; font-size: 0.6rem;
            width: 18px; height: 18px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-weight: 700;
        }
        .modal-navigator .modal-content { border-radius: 16px 16px 0 0; border-bottom: none; max-height: 60vh; }
        .modal-navigator .modal-dialog { margin:0; position:fixed; bottom:0; left:0; right:0; max-width:100%; }
        .sheet-handle { width: 40px; height: 4px; background: #cbd5e1; border-radius: 2px; margin: 8px auto 4px; }
        .sheet-nav-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(42px, 1fr)); gap: 6px; max-height: 40vh; overflow-y: auto; padding: 4px 2px; }
        .sheet-nav-btn {
            width: 100%; aspect-ratio: 1; border-radius: 8px; border: 2px solid #e2e8f0;
            background: #fff; font-weight: 600; font-size: 0.8rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s; color: #475569;
        }
        .sheet-nav-btn.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
        .sheet-nav-btn.dijawab { background: #dcfce7; color: #166534; border-color: #86efac; }
        .sheet-nav-btn.ragu { background: #fef3c7; color: #92400e; border-color: #fbbf24; }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .soal-navigator { display: none !important; }
            .main-content { margin-right: 0; margin-bottom: 0; padding: 10px; }
            .btn-lihat-soal { display: inline-flex; align-items: center; gap: 5px; }
            
            .timer-box { padding: 5px 10px; border-radius: 8px; }
            .timer-text { font-size: 1rem; }
            .timer-box i { font-size: 0.9rem; }
            
            .question-header { padding: 10px 14px; }
            .question-header h5 { font-size: 0.85rem; margin-bottom: 0; }
            
            .question-body { padding: 14px; }
            .question-body p { font-size: 0.92rem; margin-bottom: 14px !important; }
            
            .option-card { padding: 10px 12px; gap: 10px; border-radius: 8px; }
            .option-letter { width: 28px; height: 28px; font-size: 0.78rem; border-radius: 6px; }
            .option-text { font-size: 0.84rem; }
            
            .btn-nav { padding: 8px 14px; font-size: 0.8rem; }
            .btn-submit-finish { padding: 8px 16px; font-size: 0.8rem; }
            .question-body .btn-sm { font-size: 0.7rem; padding: 4px 8px; }
            
            #lockOverlay .lock-icon { font-size: 3rem; }
            #lockOverlay h4 { font-size: 1.1rem; }
            #lockOverlay p { font-size: 0.8rem; }
        }
    </style>
</head>
<body>

<!-- LAYAR ENTRY POINT -->
<div id="entryScreen">
    <div class="entry-icon">📝</div>
    <h4>Ujian Siap Dimulai</h4>
    <p>Klik tombol di bawah untuk memasuki mode ujian. Layar akan terkunci penuh dan Anda tidak bisa keluar sebelum menyelesaikan ujian atau waktu habis.</p>
    <div style="background:rgba(255,255,255,0.08); border-radius:12px; padding:14px 20px; margin-top:16px; max-width:320px; text-align:left;">
        <small style="color:#fbbf24; font-weight:700; display:block; margin-bottom:6px;"><i class="bi bi-exclamation-triangle me-1"></i> PERHATIAN:</small>
        <small style="opacity:0.7; line-height:1.5; display:block;">
                            Jangan keluar dari layar ujian.
                            <br>Pelanggaran maksimal <b style="color:#fbbf24;">3 kali</b>, ujian akan dikumpulkan otomatis.
                            <br>Jangan tarik notifikasi, pindah app, atau tekan Home.
                        </small>
    </div>
    <button class="btn-mulai" onclick="mulaiUjian()"><i class="bi bi-box-arrow-in-right me-2"></i> MASUK UJIAN</button>
</div>

<!-- OVERLAY PERINGATAN PELANGGARAN -->
<div id="lockOverlay">
    <div class="lock-icon"><i class="bi bi-lock-fill"></i></div>
    <h4>ANDA KELUAR DARI LAYAR UJIAN!</h4>
    <p>Kembali ke ujian segera. Setiap kali Anda keluar dari layar akan dihitung sebagai pelanggaran.</p>
    <div class="violation-counter">
        <div class="count" id="lockViolationCount">0</div>
        <small>dari 3 pelanggaran yang diizinkan</small>
    </div>
    <button class="btn-kembali-ujian" onclick="kembaliKeUjian()">
        <i class="bi bi-arrow-left-circle me-2"></i> KEMBALI KE UJIAN
    </button>
</div>

<!-- TOP BAR -->
<div class="top-bar">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-white" style="font-size:0.9rem;">CBT</span>
            <div class="vr text-white opacity-25"></div>
            <div>
                <div class="fw-bold" style="font-size: 12px; line-height: 1.2;"><?= $ujian['judul_ujian']; ?></div>
                <small class="opacity-50" style="font-size:10px;"><?= $ujian['nama_mapel']; ?> • <?= $s['nama_kelas']; ?></small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="violation-badge" id="violationBadge" style="display:none;">
                <span class="dot"></span> <span id="violationBadgeText">0/3</span>
            </div>
            <button type="button" class="btn-lihat-soal" data-bs-toggle="modal" data-bs-target="#modalNavigator">
                <i class="bi bi-grid-3x3-gap"></i> Soal
                <span class="badge-count" id="badgeDijawab">0</span>
            </button>
            <div class="timer-box" id="timerBox">
                <i class="bi bi-hourglass-split"></i>
                <span class="timer-text" id="timerDisplay"><?= $timer_format; ?></span>
            </div>
            <div class="vr text-white opacity-25 d-none d-md-block"></div>
            <div class="d-none d-md-block">
                <small class="opacity-50 d-block" style="font-size:9px;">Peserta</small>
                <span class="fw-bold" style="font-size:12px;"><?= $s['nama_siswa']; ?></span>
            </div>
        </div>
    </div>
    <div class="progress-bar-custom mt-2">
        <div class="progress-fill" id="progressBar" style="width: 0%;"></div>
    </div>
    <div class="d-flex justify-content-between mt-1">
        <small class="opacity-50" style="font-size:10px;">Dijawab: <span id="dijawabCount">0</span>/<?= $total_soal; ?></small>
        <small class="opacity-50" style="font-size:10px;">Soal <span id="soalSekarang">1</span> dari <?= $total_soal; ?></small>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <form id="formUjian" action="proses_ujian.php" method="POST">
        <input type="hidden" name="id_ujian" value="<?= $id_ujian; ?>">

        <?php foreach ($soal_list as $index => $soal): ?>
        <div class="question-card mb-3 soal-item <?= ($index > 0) ? 'd-none' : ''; ?>" id="soal_<?= $index; ?>">
            <div class="question-header">
                <h5 class="fw-bold mb-0">Soal <?= $index + 1; ?></h5>
            </div>
            <div class="question-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="button" class="btn btn-outline-warning btn-sm" id="raguBtn_<?= $index; ?>" onclick="toggleRagu(<?= $index; ?>)">Ragu-ragu</button>
                    <small class="text-muted" style="font-size:0.75rem;">Status: <span id="status_<?= $index; ?>">Belum dijawab</span></small>
                </div>
                <p class="fw-medium mb-3" style="font-size: 1rem; line-height: 1.6; color: #1e293b;">
                    <?= htmlspecialchars_decode($soal['pertanyaan']); ?>
                </p>

                <div class="options-container">
                    <?php 
                    $pilihan = ['A' => 'opsi_a', 'B' => 'opsi_b', 'C' => 'opsi_c', 'D' => 'opsi_d', 'E' => 'opsi_e'];
                    foreach ($pilihan as $huruf => $kolom): 
                    ?>
                    <label class="option-card" id="option_<?= $index; ?>_<?= $huruf; ?>" onclick="selectOption(<?= $index; ?>, '<?= $huruf; ?>')">
                        <input type="radio" name="jawaban_<?= $soal['id_soal']; ?>" value="<?= $huruf; ?>">
                        <div class="option-letter"><?= $huruf; ?></div>
                        <div class="option-text"><?= htmlspecialchars_decode($soal[$kolom]); ?></div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-light btn-nav" id="btnPrev" onclick="navSoal(-1)" <?= ($index == 0) ? 'disabled' : ''; ?>>
                        <i class="bi bi-chevron-left me-1"></i> Prev
                    </button>
                    <div class="text-center">
                        <span class="text-muted small" id="labelSoal"><?= $index + 1; ?> / <?= $total_soal; ?></span>
                    </div>
                    <?php if ($index < $total_soal - 1): ?>
                        <button type="button" class="btn btn-primary btn-nav" onclick="navSoal(1)">
                            Next <i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-submit-finish" onclick="submitUjian()" id="btnSelesaiTop">
                            <i class="bi bi-send-fill me-1"></i> Selesai
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <button type="submit" id="hiddenSubmit" style="display:none;"></button>
    </form>
</div>

<!-- SOAL NAVIGATOR (Desktop) -->
<div class="soal-navigator" id="soalNavigator">
    <div class="mb-2">
        <h6 class="fw-bold text-dark mb-1" style="font-size:0.85rem;"><i class="bi bi-grid-3x3-gap me-1"></i> Navigasi</h6>
    </div>
    <div class="d-flex flex-wrap gap-2 nav-grid">
        <?php for ($i = 0; $i < $total_soal; $i++): ?>
        <button type="button" class="nav-soal-btn <?= ($i == 0) ? 'active' : ''; ?>" 
                id="navBtn_<?= $i; ?>" onclick="goToSoal(<?= $i; ?>)"><?= $i + 1; ?></button>
        <?php endfor; ?>
    </div>
    <hr class="my-2">
    <div class="d-flex gap-2 align-items-center mb-1">
        <span style="width:14px;height:14px;border-radius:3px;background:#eff6ff;border:2px solid #3b82f6;display:inline-block;"></span>
        <small class="text-muted" style="font-size:0.7rem;">Dilihat</small>
    </div>
    <div class="d-flex gap-2 align-items-center mb-2">
        <span style="width:14px;height:14px;border-radius:3px;background:#dcfce7;border:2px solid #86efac;display:inline-block;"></span>
        <small class="text-muted" style="font-size:0.7rem;">Dijawab</small>
    </div>
    <button type="button" class="btn btn-outline-danger btn-sm w-100 rounded-pill mt-2" onclick="submitUjian()">
        <i class="bi bi-send-fill me-1"></i> Selesai
    </button>
</div>

<!-- MODAL NAVIGATOR SOAL (Mobile Bottom Sheet) -->
<div class="modal fade modal-navigator" id="modalNavigator" tabindex="-1" data-bs-backdrop="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="sheet-handle"></div>
            <div class="modal-header border-0 pb-0 px-4 pt-2">
                <h6 class="fw-bold mb-0" style="font-size:0.95rem;"><i class="bi bi-grid-3x3-gap me-1"></i> Navigasi Soal</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">Dijawab: <span class="fw-bold text-primary" id="sheetDijawab">0</span>/<?= $total_soal; ?></small>
                </div>
                <div class="sheet-nav-grid" id="sheetNavGrid">
                    <?php for ($i = 0; $i < $total_soal; $i++): ?>
                    <button type="button" class="sheet-nav-btn <?= ($i == 0) ? 'active' : ''; ?>" 
                            id="sheetBtn_<?= $i; ?>" onclick="goToSoalFromSheet(<?= $i; ?>)"><?= $i + 1; ?></button>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-2">
                <button type="button" class="btn btn-outline-danger w-100 rounded-pill" data-bs-dismiss="modal" onclick="setTimeout(()=>submitUjian(),200)">
                    <i class="bi bi-send-fill me-1"></i> Selesai Ujian
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI SELESAI -->
<div class="modal fade" id="modalSelesai" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-body p-4 text-center">
                <div class="mb-3"><i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 2.5rem;"></i></div>
                <h5 class="fw-bold text-dark mb-2">Selesaikan Ujian?</h5>
                <p class="text-muted small mb-1">Pastikan semua soal sudah dijawab.</p>
                <div class="small mb-4 text-center">
                    <div style="margin-bottom: 10px;">
                        <i class="bi bi-info-circle text-primary" style="font-size: 1.5rem;"></i>
                        <h6 class="fw-bold text-dark mb-0">Pastikan Semua Soal Telah Dijawab</h6>
                        <div class="fw-bold text-primary" style="font-size: 2rem;" id="modalDijawab">0</div>
                        <p class="text-muted small mb-0">dari <span class="fw-bold"><?= $total_soal; ?></span> soal</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light flex-fill rounded-pill" data-bs-dismiss="modal">Kembali</button>
                    <button type="button" class="btn btn-danger flex-fill rounded-pill" onclick="kumpulkanUjian()">
                        <i class="bi bi-send-fill me-1"></i> Ya, Kumpulkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ============================================================
//  KONFIGURASI KEAMANAN
// ============================================================
const MAX_VIOLATION = 3;          // Maksimal pelanggaran sebelum auto-submit
const VISIBILITY_DEBOUNCE = 2000; // Abaikan visibility change < 2 detik (anti false positive)

// ============================================================
//  VARIABEL GLOBAL
// ============================================================
let currentIndex = 0;
const totalSoal = <?= $total_soal; ?>;
const jawaban = {}; 
const ragu = {};   
let ujianAktif = false;           // Status ujian sedang berjalan
let violationCount = 0;           // Counter pelanggaran
let isSubmitting = false;         // Flag agar tidak double-submit
let gracePeriod = false;          // Grace period setelah kembali ke ujian (tidak hitung pelanggaran)
let visibilityTimeout = null;     // Timeout untuk debounce visibility change

// ============================================================
//  MASUK UJIAN (ENTRY POINT + FULLSCREEN)
// ============================================================
function mulaiUjian() {
    const elem = document.documentElement;
    
    // Request fullscreen
    const requestFs = elem.requestFullscreen || elem.webkitRequestFullscreen || elem.msRequestFullscreen;
    if (requestFs) {
        requestFs.call(elem).then(() => {
            aktifkanUjian();
        }).catch(() => {
            // Jika fullscreen ditolak, tetap aktifkan ujian (dengan peringatan)
            aktifkanUjian();
        });
    } else {
        aktifkanUjian();
    }
}

function aktifkanUjian() {
    ujianAktif = true;
    document.getElementById('entryScreen').style.display = 'none';
}

// ============================================================
//  FULLSCREEN MONITOR
//  Catatan: Hanya menggunakan event, TANPA interval check.
//  Interval check dihapus karena terlalu agresif dan sering
//  false positive saat interaksi normal (klik modal, dll).
// ============================================================
document.addEventListener('fullscreenchange', onFullscreenChange);
document.addEventListener('webkitfullscreenchange', onFullscreenChange);

function onFullscreenChange() {
    if (!ujianAktif || isSubmitting || gracePeriod) return;
    
    const isFs = document.fullscreenElement || document.webkitFullscreenElement;
    if (!isFs) {
        // Keluar dari fullscreen → tampilkan overlay peringatan
        // Tapi jangan langsung catat pelanggaran, beri kesempatan kembali
        tampilkanOverlay();
        
        // Set timeout 5 detik: jika belum kembali ke fullscreen, catat pelanggaran
        setTimeout(() => {
            const isFsNow = document.fullscreenElement || document.webkitFullscreenElement;
            if (!isFsNow && ujianAktif && !isSubmitting) {
                catatPelanggaran('Keluar dari mode layar penuh dan tidak kembali');
            }
        }, 5000);
    }
}

// ============================================================
//  PAGE VISIBILITY API - DETEKSI PINDAH TAB / APP
//  Dengan DEBOUNCE 2 detik: hanya hitung pelanggaran jika
//  siswa benar-benar pindah tab/app > 2 detik.
//  Quick flicker (misal notifikasi muncul sekejap) diabaikan.
//  
//  Catatan: window.blur DIHAPUS karena terlalu banyak false
//  positive (klik modal, klik tombol navigasi, address bar, dll)
// ============================================================
document.addEventListener('visibilitychange', () => {
    if (!ujianAktif || isSubmitting || gracePeriod) return;
    
    if (document.hidden) {
        // Tab jadi tidak terlihat → mulai hitung waktu
        visibilityTimeout = setTimeout(() => {
            // Masih hidden setelah 2 detik → ini pelanggaran nyata
            if (document.hidden && ujianAktif && !isSubmitting) {
                catatPelanggaran('Berpindah dari layar ujian');
            }
        }, VISIBILITY_DEBOUNCE);
    } else {
        // Tab kembali terlihat → batalkan timeout (bukan pelanggaran, cuma sekejap)
        if (visibilityTimeout) {
            clearTimeout(visibilityTimeout);
            visibilityTimeout = null;
        }
    }
});

// ============================================================
//  CATAT PELANGGARAN + OVERLAY
// ============================================================

// Tampilkan overlay saja (tanpa catat pelanggaran dulu)
function tampilkanOverlay() {
    if (isSubmitting) return;
    document.getElementById('lockOverlay').style.display = 'flex';
}

// Catat pelanggaran (hanya untuk pelanggaran nyata yang sudah melewati debounce)
function catatPelanggaran(alasan) {
    violationCount++;
    
    // Update UI counter
    document.getElementById('lockViolationCount').textContent = violationCount;
    document.getElementById('violationBadge').style.display = 'flex';
    document.getElementById('violationBadgeText').textContent = violationCount + '/' + MAX_VIOLATION;
    
    // Tampilkan overlay
    document.getElementById('lockOverlay').style.display = 'flex';
    
    // Cek apakah melebihi batas
    if (violationCount >= MAX_VIOLATION) {
        // Batas terlampaui, auto-submit
        setTimeout(() => {
            alert('Batas pelanggaran terlampaui! Ujian Anda dikumpulkan otomatis.');
            forceSubmit();
        }, 500);
        return;
    }
}

function kembaliKeUjian() {
    // Sembunyikan overlay
    document.getElementById('lockOverlay').style.display = 'none';
    
    // Aktifkan grace period 3 detik (setelah kembali, jangan langsung hitung pelanggaran)
    gracePeriod = true;
    setTimeout(() => { gracePeriod = false; }, 3000);
    
    // Paksa kembali ke fullscreen
    const elem = document.documentElement;
    const requestFs = elem.requestFullscreen || elem.webkitRequestFullscreen || elem.msRequestFullscreen;
    if (requestFs) {
        requestFs.call(elem).catch(() => {
            // Jika gagal fullscreen, minimal fokuskan kembali window
            window.focus();
        });
    }
}

// ============================================================
//  TIMER
// ============================================================
let detikTersisa = <?= $detik_tersisa; ?>;
const timerInterval = setInterval(() => {
    if (!ujianAktif) return;
    
    detikTersisa--;
    if (detikTersisa <= 0) {
        clearInterval(timerInterval);
        forceSubmit(); // Waktu habis, submit paksa
        return;
    }
    let m = Math.floor(detikTersisa / 60);
    let s = detikTersisa % 60;
    document.getElementById('timerDisplay').textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');

    if (detikTersisa <= 300) {
        document.getElementById('timerBox').classList.add('timer-danger');
        document.getElementById('timerDisplay').classList.add('text-warning');
    }
}, 1000);

// ============================================================
//  UPDATE PROGRESS
// ============================================================
function updateProgress() {
    const jumlahDijawab = Object.keys(jawaban).length;
    const persen = Math.round((jumlahDijawab / totalSoal) * 100);
    
    document.getElementById('progressBar').style.setProperty('width', persen + '%', 'important');
    document.getElementById('dijawabCount').textContent = jumlahDijawab;
    document.getElementById('badgeDijawab').textContent = jumlahDijawab;
    document.getElementById('sheetDijawab').textContent = jumlahDijawab;
    document.getElementById('modalDijawab').textContent = jumlahDijawab;
}

// ============================================================
//  PILIH JAWABAN
// ============================================================
function selectOption(soalIndex, huruf) {
    jawaban[soalIndex] = huruf;

    const options = ['A', 'B', 'C', 'D', 'E'];
    options.forEach(h => {
        const el = document.getElementById('option_' + soalIndex + '_' + h);
        if (el) el.classList.remove('selected');
    });
    const selectedOption = document.getElementById('option_' + soalIndex + '_' + huruf);
    if (selectedOption) selectedOption.classList.add('selected');

    document.querySelectorAll('#soal_' + soalIndex + ' input[type="radio"]').forEach(r => {
        r.checked = (r.value === huruf);
    });

    const navBtn = document.getElementById('navBtn_' + soalIndex);
    const sheetBtn = document.getElementById('sheetBtn_' + soalIndex);
    if (navBtn) navBtn.classList.add('dijawab');
    if (sheetBtn) sheetBtn.classList.add('dijawab');
    
    const statusEl = document.getElementById('status_' + soalIndex);
    if (statusEl && !ragu[soalIndex]) {
        statusEl.textContent = 'Dijawab';
    }
    
    updateProgress();
}

// ============================================================
//  NAVIGASI SOAL
// ============================================================
function navSoal(direction) {
    let newIndex = currentIndex + direction;
    if (newIndex >= 0 && newIndex < totalSoal) showSoal(newIndex);
}

function goToSoal(index) { showSoal(index); }

function goToSoalFromSheet(index) {
    const modalEl = document.getElementById('modalNavigator');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
    setTimeout(() => { showSoal(index); }, 200);
}

function showSoal(index) {
    document.querySelectorAll('.soal-item').forEach(el => el.classList.add('d-none'));
    document.getElementById('soal_' + index).classList.remove('d-none');
    
    document.querySelectorAll('.nav-soal-btn').forEach(btn => btn.classList.remove('active'));
    const navBtn = document.getElementById('navBtn_' + index);
    if (navBtn) navBtn.classList.add('active');
    
    document.querySelectorAll('.sheet-nav-btn').forEach(btn => btn.classList.remove('active'));
    const sheetBtn = document.getElementById('sheetBtn_' + index);
    if (sheetBtn) sheetBtn.classList.add('active');
    
    currentIndex = index;
    document.getElementById('soalSekarang').textContent = index + 1;
    
    const prevBtn = document.querySelector('#soal_' + index + ' #btnPrev');
    if (prevBtn) prevBtn.disabled = (index === 0);
    
    restoreSelection(index);
    updateRaguButton(index);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function restoreSelection(index) {
    const selectedValue = jawaban[index] || null;
    document.querySelectorAll('#soal_' + index + ' input[type="radio"]').forEach(radio => { 
        radio.checked = (radio.value === selectedValue); 
        if (radio.value === selectedValue) {
            const h = radio.value;
            const el = document.getElementById('option_' + index + '_' + h);
            if (el) el.classList.add('selected');
        }
    });
}

function updateRaguButton(index) {
    const btn = document.getElementById('raguBtn_' + index);
    const status = document.getElementById('status_' + index);
    const navBtn = document.getElementById('navBtn_' + index);
    const sheetBtn = document.getElementById('sheetBtn_' + index);
    const isRagu = !!ragu[index];

    if (btn) {
        btn.classList.toggle('btn-warning', isRagu);
        btn.classList.toggle('btn-outline-warning', !isRagu);
        btn.textContent = isRagu ? 'Ragu-ragu ✓' : 'Ragu-ragu';
    }
    if (status) status.textContent = isRagu ? 'Ragu-ragu' : (jawaban[index] ? 'Dijawab' : 'Belum dijawab');
    if (navBtn) navBtn.classList.toggle('ragu', isRagu);
    if (sheetBtn) sheetBtn.classList.toggle('ragu', isRagu);
}

function toggleRagu(index) {
    ragu[index] = !ragu[index];
    updateRaguButton(index);
}

// ============================================================
//  SUBMIT UJIAN (VALIDASI DULU)
// ============================================================
function submitUjian() {
    updateProgress();
    
    // Cek ada soal ragu
    const jumlahRagu = Object.values(ragu).filter(Boolean).length;
    if (jumlahRagu > 0) {
        alert('Masih ada ' + jumlahRagu + ' soal bertanda ragu-ragu.\nHapus tanda ragu pada soal tersebut terlebih dahulu.');
        return;
    }

    // Cek ada yang belum dijawab
    const jumlahDijawab = Object.keys(jawaban).length;
    const belumDijawab = totalSoal - jumlahDijawab;
    
    if (belumDijawab > 0) {
        alert('Anda belum menjawab ' + belumDijawab + ' dari ' + totalSoal + ' soal.\nAnda wajib menjawab semua soal untuk menyelesaikan ujian.');
        return;
    }
    
    // Semua OK → tampilkan modal konfirmasi
    const modal = new bootstrap.Modal(document.getElementById('modalSelesai'));
    modal.show();
}

// Kumpulkan ujian dari modal konfirmasi
function kumpulkanUjian() {
    forceSubmit();
}

// Force submit (dipakai oleh timeout & pelanggaran juga)
function forceSubmit() {
    if (isSubmitting) return; // Cegah double-submit
    isSubmitting = true;
    ujianAktif = false; // Stop semua monitoring
    
    // Keluar dari fullscreen sebelum submit
    if (document.fullscreenElement) {
        document.exitFullscreen().catch(() => {});
    }
    
    // Submit form
    document.getElementById('formUjian').submit();
}

// ============================================================
//  KEAMANAN: BLOKIR KLIK KANAN, SHORTCUT, SWIPE
// ============================================================

// Blokir klik kanan
document.addEventListener('contextmenu', e => e.preventDefault());

// Blokir shortcut keyboard berbahaya
document.addEventListener('keydown', function(e) {
    // F12
    if (e.key === 'F12') { e.preventDefault(); return; }
    
    // Ctrl/Cmd + kombinasi berbahaya
    if (e.ctrlKey || e.metaKey) {
        const blocked = ['KeyI', 'KeyU', 'KeyS', 'KeyC', 'KeyP', 'KeyT', 'KeyW', 'KeyN', 'KeyL', 'KeyJ'];
        if (blocked.includes(e.code)) { e.preventDefault(); return; }
    }
    
    // Alt + Tab (detectable di beberapa browser)
    if (e.altKey && e.key === 'Tab') { e.preventDefault(); return; }
    
    // Escape (untuk keluar fullscreen)
    if (e.key === 'Escape') { e.preventDefault(); return; }
});

// Blokir beforeunload (peringatan saat tutup tab/refresh)
window.addEventListener('beforeunload', function(e) {
    if (ujianAktif && !isSubmitting) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Blokir swipe dari tepi atas saja (area notification bar)
// Tepi kiri/kanan dihapus karena mengganggu scroll biasa di HP
document.addEventListener('touchstart', function(e) {
    if (!ujianAktif) return;
    
    // Hanya blokir area paling atas (notification bar, bukan konten soal)
    const touch = e.touches[0];
    if (touch.clientY < 15) {
        e.preventDefault();
    }
}, { passive: false });

// Cegah drag & drop
document.addEventListener('dragstart', e => e.preventDefault());

// Catatan: Deteksi devtools DIHAPUS karena di HP, buka keyboard
// virtual mengubah dimensi window dan memicu false positive.

// ============================================================
//  RE-ENTER FULLSCREEN OTOMATIS SAAT KLIK DI HALAMAN
// ============================================================
document.addEventListener('click', function() {
    if (!ujianAktif || isSubmitting) return;
    
    const isFs = document.fullscreenElement || document.webkitFullscreenElement;
    if (!isFs) {
        const elem = document.documentElement;
        const requestFs = elem.requestFullscreen || elem.webkitRequestFullscreen || elem.msRequestFullscreen;
        if (requestFs) {
            requestFs.call(elem).catch(() => {});
        }
    }
});

// ============================================================
//  PREVENT BACK BUTTON (HISTORY MANIPULATION)
// ============================================================
history.pushState(null, null, location.href);
window.addEventListener('popstate', function(e) {
    if (ujianAktif && !isSubmitting) {
        history.pushState(null, null, location.href);
        catatPelanggaran('Mencoba kembali ke halaman sebelumnya');
    }
});
</script>
</body>
</html>