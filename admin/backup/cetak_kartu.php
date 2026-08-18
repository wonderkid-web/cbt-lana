<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// 1. Perbarui Query untuk Join ke tabel ruang_ujian
// Menggunakan LEFT JOIN agar siswa yang belum dapat ruangan tetap muncul (tapi keterangan ruang kosong)
$query = mysqli_query($conn, "SELECT siswa.*, kelas.nama_kelas, ruang_ujian.nama_ruang 
         FROM siswa 
         JOIN kelas ON siswa.id_kelas = kelas.id_kelas 
         LEFT JOIN ruang_ujian ON siswa.id_ruang = ruang_ujian.id_ruang
         ORDER BY kelas.nama_kelas ASC, siswa.nama_siswa ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu Ujian - SMK Putra Anda</title>
    <style>
        /* Style tetap sama seperti sebelumnya */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f0f0f0; }
        .grid { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 10px; 
            padding: 10mm; 
            max-width: 210mm; 
            margin: auto;
        }
        .card { 
            background: #fff; 
            border: 1.5px solid #000; 
            padding: 12px; 
            border-radius: 8px; 
            position: relative; 
            height: 65mm; /* Sedikit ditambah tingginya karena ada baris ruangan */
            box-sizing: border-box;
            page-break-inside: avoid; 
            display: flex;
            flex-direction: column;
        }
        .header { 
            display: flex; 
            align-items: center; 
            border-bottom: 2px solid #000; 
            padding-bottom: 8px; 
            margin-bottom: 10px; 
        }
        .logo { width: 50px; height: 50px; margin-right: 10px; object-fit: contain; }
        .header-text { flex-grow: 1; text-align: center; }
        .header-text h4 { margin: 0; font-size: 13px; text-transform: uppercase; }
        .header-text span { font-size: 11px; font-weight: bold; display: block; }
        .content table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .content td { padding: 2px 0; vertical-align: top; }
        .content strong { font-size: 12px; }
        .footer { 
            margin-top: auto; 
            font-size: 9px; 
            font-style: italic; 
            color: #444; 
            border-top: 1px dashed #ccc; 
            padding-top: 5px; 
        }
        @media print {
            body { background: #fff; }
            .no-print { display: none; }
            .grid { padding: 0; gap: 5mm; }
            @page { size: A4; margin: 10mm; }
        }
        /* Style khusus untuk label ruangan agar mencolok */
        .badge-ruang {
            background: #fff;
            color: #000;
            padding: 2px 8px;
            border-radius: 3px;
            border: 1px solid #000;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="no-print" style="background: #0f172a; color: #fff; padding: 15px; text-align: center; position: sticky; top: 0; z-index: 9999;">
    <p style="margin-bottom: 10px;">Ukuran kertas disarankan <b>A4</b> dengan Layout <b>Portrait</b>.</p>
    <button onclick="window.print()" style="padding: 10px 25px; cursor:pointer; font-weight:bold; background: #22c55e; color: white; border: none; border-radius: 5px;">
        KLIK UNTUK CETAK (PRINT)
    </button>
</div>

<div class="grid">
    <?php while($row = mysqli_fetch_assoc($query)): ?>
    <div class="card">
        <div class="header">
            <img src="../assets/img/LogoPa.png" alt="Logo" class="logo" onerror="this.src='https://via.placeholder.com/50x50?text=SMK'">
            <div class="header-text">
                <h4>KARTU PESERTA UJIAN</h4>
                <span>SMK PUTRA ANDA BINJAI</span>
                <small style="font-size: 9px;">Tahun Pelajaran 2025/2026</small>
            </div>
        </div>
        <div class="content">
            <table>
                <tr>
                    <td width="35%">Nama Peserta</td>
                    <td>: <strong><?= $row['nama_siswa']; ?></strong></td>
                </tr>
                <tr>
                    <td>Kelas</td>
                    <td>: <?= $row['nama_kelas']; ?></td>
                </tr>
                <tr>
                    <td>Ruang Ujian</td>
                    <td>: <span class="badge-ruang"><?= ($row['nama_ruang']) ? $row['nama_ruang'] : 'Belum Diatur'; ?></span></td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 5px; border-top: 1px solid #eee;">
                        <small style="font-size: 9px; color: #666;">Akses Login: Masukkan Username dan Password dibawah untuk login.</small>
                    </td>
                </tr>
                <tr>
                    <td>Username</td>
                    <td>: <strong><?= $row['nisn']; ?></strong></td>
                </tr>
                <tr>
                    <td>Password</td>
                    <td>: <strong><?= $row['nisn']; ?></strong></td>
                </tr>
            </table>
        </div>
        <div class="footer">
            * Pastikan kartu/akun sudah diaktifkan. Hubungi proktor jika bermasalah.
        </div>
    </div>
    <?php endwhile; ?>
</div>

</body>
</html>