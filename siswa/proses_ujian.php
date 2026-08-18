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

// Ambil data ujian
$q_ujian = mysqli_query($conn, "SELECT u.*, m.nama_mapel, su.nama_ujian as nama_sesi, su.id_sesi
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

// Cegah double submit (jika sudah selesai, redirect)
if ($ujian['status'] == 'selesai') {
    header("Location: hasil_ujian.php?id_ujian=$id_ujian");
    exit;
}

// Cek apakah sudah ada jawaban sebelumnya (karena timeout/refresh)
$q_cek_jawaban = mysqli_query($conn, "SELECT COUNT(*) as total FROM jawaban_siswa WHERE id_ujian = '$id_ujian'");
$ada_jawaban = mysqli_fetch_assoc($q_cek_jawaban)['total'];

if ($ada_jawaban > 0) {
    // Jawaban sudah pernah disimpan, redirect ke hasil
    mysqli_query($conn, "UPDATE ujian SET status = 'selesai' WHERE id_ujian = '$id_ujian'");
    unset($_SESSION['id_ujian_aktif']);
    header("Location: hasil_ujian.php?id_ujian=$id_ujian");
    exit;
}

// Ambil semua soal untuk mapel ini
// Gunakan urutan yang tersimpan di session, atau ambil normal jika tidak ada
if (isset($_SESSION['soal_ids']) && !empty($_SESSION['soal_ids'])) {
    $id_soal_list = implode(',', array_map('intval', $_SESSION['soal_ids']));
    $q_soal = mysqli_query($conn, "SELECT * FROM bank_soal WHERE id_soal IN ($id_soal_list) ORDER BY FIELD(id_soal, $id_soal_list)");
} else {
    $q_soal = mysqli_query($conn, "SELECT * FROM bank_soal WHERE id_mapel = '$ujian[id_mapel]' ORDER BY id_soal ASC");
}

$total_soal = mysqli_num_rows($q_soal);
$total_benar = 0;
$total_salah = 0;
$total_tidak_dijawab = 0;

// Proses setiap jawaban
while ($soal = mysqli_fetch_assoc($q_soal)) {
    $id_soal = $soal['id_soal'];
    $kunci = strtoupper($soal['kunci_jawaban']); // A/B/C/D/E
    
    // Ambil jawaban siswa dari POST
    $field_name = 'jawaban_' . $id_soal;
    $jawaban_siswa = isset($_POST[$field_name]) ? strtoupper(mysqli_real_escape_string($conn, $_POST[$field_name])) : '';
    
    // Tentukan benar/salah
    if (empty($jawaban_siswa)) {
        $is_benar = 0;
        $total_tidak_dijawab++;
    } elseif ($jawaban_siswa === $kunci) {
        $is_benar = 1;
        $total_benar++;
    } else {
        $is_benar = 0;
        $total_salah++;
    }
    
    // Simpan ke tabel jawaban_siswa
    mysqli_query($conn, "INSERT INTO jawaban_siswa (id_ujian, id_soal, jawaban, is_benar) 
                          VALUES ('$id_ujian', '$id_soal', '$jawaban_siswa', '$is_benar')");
}

// Hitung nilai (persentase)
if ($total_soal > 0) {
    $nilai = round(($total_benar / $total_soal) * 100, 1);
} else {
    $nilai = 0;
}

// Update tabel ujian
$waktu_selesai = date('Y-m-d H:i:s');
mysqli_query($conn, "UPDATE ujian SET 
                      waktu_selesai = '$waktu_selesai', 
                      status = 'selesai', 
                      nilai = '$nilai' 
                      WHERE id_ujian = '$id_ujian'");

// Bersihkan session
unset($_SESSION['id_ujian_aktif']);
unset($_SESSION['soal_ids']);

// Redirect ke halaman hasil
header("Location: hasil_ujian.php?id_ujian=$id_ujian");
exit;
?>