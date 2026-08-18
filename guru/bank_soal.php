<?php 
// === BANK SOAL GURU ===
// File ini di-include oleh dashboard.php (page=bank_soal)
// Guru hanya bisa melihat & mengelola soal miliknya sendiri
// 
// UPDATE: Sekarang menggunakan struktur jurusan & konsentrasi
// - Mapel UMUM → filter kelas menampilkan SEMUA kelas
// - Mapel KEJURUAN → filter kelas HANYA kelas di jurusan mapel tersebut

 $id_user = (int)$_SESSION['id_user'];

// Ambil data guru + info jenis mapel & jurusan
 $q_guru_mapel = mysqli_query($conn, "SELECT guru.id_mapel, mapel.nama_mapel, 
                                      mapel.jenis_mapel, mapel.id_jurusan AS id_jurusan_mapel,
                                      j.nama_program, j.kode AS kode_jurusan
                                      FROM guru 
                                      LEFT JOIN mapel ON guru.id_mapel = mapel.id_mapel 
                                      LEFT JOIN jurusan j ON mapel.id_jurusan = j.id_jurusan
                                      WHERE guru.id_user = '$id_user'");
 $guru_mapel = mysqli_fetch_assoc($q_guru_mapel);
 $id_mapel_guru = (int)($guru_mapel['id_mapel'] ?? 0);
 $jenis_mapel_guru = $guru_mapel['jenis_mapel'] ?? 'umum';
 $id_jurusan_mapel = (int)($guru_mapel['id_jurusan_mapel'] ?? 0);
 $nama_program = $guru_mapel['nama_program'] ?? '';

// --- PROSES: HAPUS SOAL ---
if (isset($_GET['hapus'])) {
    $id_soal = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM bank_soal WHERE id_soal = '$id_soal' AND id_mapel = '$id_mapel_guru'");
    $redirect_url = "?page=bank_soal&msg=dihapus";
    if ($view_mapel_id) {
        $redirect_url .= "&view_mapel=$view_mapel_id";
    }
    if (!empty($filter_kelas)) {
        $redirect_url .= "&filter_kelas=" . urlencode($filter_kelas);
    }
    header("Location: $redirect_url");
    exit;
}

// --- PROSES: IMPORT SOAL DARI CSV ---
if (isset($_POST['import_soal'])) {
    $file = $_FILES['file_csv']['tmp_name'];

    if (!is_uploaded_file($file)) {
        $redirect_url = "?page=bank_soal&msg=file_error";
        if ($view_mapel_id) $redirect_url .= "&view_mapel=$view_mapel_id";
        if (!empty($filter_kelas)) $redirect_url .= "&filter_kelas=" . urlencode($filter_kelas);
        header("Location: $redirect_url");
        exit;
    }

    if ($id_mapel_guru == 0) {
        $redirect_url = "?page=bank_soal&msg=no_mapel";
        if ($view_mapel_id) $redirect_url .= "&view_mapel=$view_mapel_id";
        if (!empty($filter_kelas)) $redirect_url .= "&filter_kelas=" . urlencode($filter_kelas);
        header("Location: $redirect_url");
        exit;
    }

    $handle = fopen($file, "r");
    $row = 0;
    $success = 0;
    $skip = 0;

    mysqli_begin_transaction($conn);
    try {
        while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
            $row++;
            if ($row == 1) continue;

            $pertanyaan = mysqli_real_escape_string($conn, trim($data[0] ?? ''));
            $opsi_a = mysqli_real_escape_string($conn, trim($data[1] ?? ''));
            $opsi_b = mysqli_real_escape_string($conn, trim($data[2] ?? ''));
            $opsi_c = mysqli_real_escape_string($conn, trim($data[3] ?? ''));
            $opsi_d = mysqli_real_escape_string($conn, trim($data[4] ?? ''));
            $opsi_e = mysqli_real_escape_string($conn, trim($data[5] ?? ''));
            $kunci = strtoupper(trim($data[6] ?? ''));
            $kelas_csv = trim($data[7] ?? '');

            if (empty($pertanyaan) || empty($kunci)) {
                $skip++;
                continue;
            }

            $id_kelas_valid = null;
            if (!empty($kelas_csv)) {
                $q_cari_kelas = mysqli_query($conn, "SELECT id_kelas FROM kelas WHERE nama_kelas = '$kelas_csv' LIMIT 1");
                $k = mysqli_fetch_assoc($q_cari_kelas);
                $id_kelas_valid = $k['id_kelas'] ?? null;
            }

            $kelas_value = is_numeric($id_kelas_valid) ? $id_kelas_valid : 'NULL';

            mysqli_query($conn, "INSERT INTO bank_soal (id_user, id_mapel, id_kelas, angkatan, pertanyaan, tipe_soal, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban, status) 
                              VALUES ('$id_user', '$id_mapel_guru', $kelas_value, NULL, '$pertanyaan', 'pilihan_ganda', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$kunci', 'aktif')");
            $success++;
        }
        mysqli_commit($conn);
        fclose($handle);
        $redirect_url = "?page=bank_soal&msg=import_sukses&jml=$success&skip=$skip";
        if ($view_mapel_id) $redirect_url .= "&view_mapel=$view_mapel_id";
        if (!empty($filter_kelas)) $redirect_url .= "&filter_kelas=" . urlencode($filter_kelas);
        header("Location: $redirect_url");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        fclose($handle);
        $redirect_url = "?page=bank_soal&msg=import_gagal";
        if ($view_mapel_id) $redirect_url .= "&view_mapel=$view_mapel_id";
        if (!empty($filter_kelas)) $redirect_url .= "&filter_kelas=" . urlencode($filter_kelas);
        header("Location: $redirect_url");
        exit;
    }
}

// --- PROSES: TAMBAH SOAL MANUAL ---
if (isset($_POST['tambah_soal'])) {
    if ($id_mapel_guru == 0) {
        header("Location: ?page=bank_soal&msg=no_mapel");
        exit;
    }

    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan'] ?? '');
    $opsi_a = mysqli_real_escape_string($conn, $_POST['opsi_a'] ?? '');
    $opsi_b = mysqli_real_escape_string($conn, $_POST['opsi_b'] ?? '');
    $opsi_c = mysqli_real_escape_string($conn, $_POST['opsi_c'] ?? '');
    $opsi_d = mysqli_real_escape_string($conn, $_POST['opsi_d'] ?? '');
    $opsi_e = mysqli_real_escape_string($conn, $_POST['opsi_e'] ?? '');
    $kunci = strtoupper(mysqli_real_escape_string($conn, $_POST['kunci_jawaban'] ?? ''));
    $id_kelas_input = $_POST['id_kelas'] ?? '';
    $status = 'aktif';

    // Validasi kelas
    $id_kelas = 'NULL';
    $angkatan_value = 'NULL';

    if (!empty($id_kelas_input)) {
        if (strpos($id_kelas_input, 'seluruh_') === 0) {
            $tingkat = str_replace('seluruh_', '', $id_kelas_input);
            $angkatan_value = "'$tingkat'";
        } else {
            $id_kelas = (int)$id_kelas_input;
        }
    }

    try {
        $insert = mysqli_query($conn, "INSERT INTO bank_soal (id_user, id_mapel, id_kelas, angkatan, pertanyaan, tipe_soal, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban, status) 
                              VALUES ('$id_user', '$id_mapel_guru', $id_kelas, $angkatan_value, '$pertanyaan', 'pilihan_ganda', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$kunci', '$status')");
        
        if ($insert && mysqli_affected_rows($conn) > 0) {
            $redirect_url = "?page=bank_soal&msg=disimpan";
            if ($view_mapel_id) $redirect_url .= "&view_mapel=$view_mapel_id";
            if (!empty($filter_kelas)) $redirect_url .= "&filter_kelas=" . urlencode($filter_kelas);
            header("Location: $redirect_url");
        } else {
            header("Location: ?page=bank_soal&msg=gagal");
        }
    } catch (Exception $e) {
        $redirect_url = "?page=bank_soal&msg=gagal";
        if ($view_mapel_id) $redirect_url .= "&view_mapel=$view_mapel_id";
        if (!empty($filter_kelas)) $redirect_url .= "&filter_kelas=" . urlencode($filter_kelas);
        header("Location: $redirect_url");
    }
    exit;
}

// --- PROSES: EDIT SOAL ---
if (isset($_POST['edit_soal'])) {
    $id_soal = (int)$_POST['id_soal'];
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan'] ?? '');
    $opsi_a = mysqli_real_escape_string($conn, $_POST['opsi_a'] ?? '');
    $opsi_b = mysqli_real_escape_string($conn, $_POST['opsi_b'] ?? '');
    $opsi_c = mysqli_real_escape_string($conn, $_POST['opsi_c'] ?? '');
    $opsi_d = mysqli_real_escape_string($conn, $_POST['opsi_d'] ?? '');
    $opsi_e = mysqli_real_escape_string($conn, $_POST['opsi_e'] ?? '');
    $kunci = strtoupper(mysqli_real_escape_string($conn, $_POST['kunci_jawaban'] ?? ''));
    $id_kelas_input = $_POST['id_kelas'] ?? '';

    $id_kelas = 'NULL';
    $angkatan_value = 'NULL';

    if (!empty($id_kelas_input)) {
        if (strpos($id_kelas_input, 'seluruh_') === 0) {
            $tingkat = str_replace('seluruh_', '', $id_kelas_input);
            $angkatan_value = "'$tingkat'";
        } else {
            $id_kelas = (int)$id_kelas_input;
        }
    }

    try {
        mysqli_query($conn, "UPDATE bank_soal SET 
                              pertanyaan = '$pertanyaan', tipe_soal = 'pilihan_ganda',
                              opsi_a = '$opsi_a', opsi_b = '$opsi_b', opsi_c = '$opsi_c',
                              opsi_d = '$opsi_d', opsi_e = '$opsi_e', kunci_jawaban = '$kunci',
                              id_kelas = $id_kelas, angkatan = $angkatan_value
                              WHERE id_soal = '$id_soal' AND id_mapel = '$id_mapel_guru'");
        $redirect_url = "?page=bank_soal&msg=diubah";
        if ($view_mapel_id) $redirect_url .= "&view_mapel=$view_mapel_id";
        if (!empty($filter_kelas)) $redirect_url .= "&filter_kelas=" . urlencode($filter_kelas);
        header("Location: $redirect_url");
    } catch (Exception $e) {
        $redirect_url = "?page=bank_soal&msg=gagal";
        if ($view_mapel_id) $redirect_url .= "&view_mapel=$view_mapel_id";
        if (!empty($filter_kelas)) $redirect_url .= "&filter_kelas=" . urlencode($filter_kelas);
        header("Location: $redirect_url");
    }
    exit;
}

// --- PAGINATION & VIEW LOGIC ---
 $view_mapel_id = isset($_GET['view_mapel']) ? (int)$_GET['view_mapel'] : 0;
 $filter_kelas = isset($_GET['filter_kelas']) ? $_GET['filter_kelas'] : '';

 $batas = 10;
 $halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
 $halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

// Jika view mapel tertentu
if ($view_mapel_id && $view_mapel_id == $id_mapel_guru) {
    $where_clause = "WHERE bs.id_mapel = '$id_mapel_guru'";
    if (!empty($filter_kelas)) {
        if (strpos($filter_kelas, 'seluruh_') === 0) {
            $tingkat = str_replace('seluruh_', '', $filter_kelas);
            $where_clause .= " AND (bs.angkatan = '$tingkat' OR k.nama_kelas LIKE '$tingkat %')";
        } else {
            $id_kelas_filter = (int)$filter_kelas;
            $where_clause .= " AND bs.id_kelas = '$id_kelas_filter'";
        }
    }
    
    $q_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM bank_soal bs LEFT JOIN kelas k ON bs.id_kelas = k.id_kelas $where_clause");
    $jumlah_data = mysqli_fetch_assoc($q_count)['total'];
    $total_halaman = ceil($jumlah_data / $batas);
    
    $q_soal = mysqli_query($conn, "SELECT bs.*, k.nama_kelas, k.tingkat, k.id_jurusan,
                                   CASE 
                                       WHEN bs.angkatan IS NOT NULL THEN CONCAT('Seluruh Kelas ', bs.angkatan)
                                       WHEN bs.id_kelas IS NULL THEN '-'
                                       ELSE k.nama_kelas 
                                   END AS display_kelas
                                   FROM bank_soal bs
                                   LEFT JOIN kelas k ON bs.id_kelas = k.id_kelas
                                   $where_clause 
                                   ORDER BY bs.id_soal DESC LIMIT $halaman_awal, $batas");
    $current_mapel_name = $guru_mapel['nama_mapel'];
} else {
    $q_mapel = mysqli_query($conn, "SELECT m.id_mapel, m.nama_mapel, COUNT(bs.id_soal) as total_soal
                                    FROM mapel m
                                    LEFT JOIN bank_soal bs ON m.id_mapel = bs.id_mapel
                                    WHERE m.id_mapel = '$id_mapel_guru'
                                    GROUP BY m.id_mapel, m.nama_mapel");
    $jumlah_data = mysqli_num_rows($q_mapel);
    $total_halaman = ceil($jumlah_data / $batas);
    $q_soal = $q_mapel;
}

 $nomor = $halaman_awal + 1;

// ============================================================
// OPSI KELAS UNTUK DROPDOWN (filter berdasarkan jenis mapel)
// ============================================================
 $kelas_options = [];

if ($jenis_mapel_guru === 'umum') {
    // MAPEL UMUM: tampilkan SEMUA kelas, dikelompokkan per tingkat
    $kelas_options[] = ['id' => 'seluruh_X', 'nama' => 'Seluruh Kelas X'];
    $kelas_options[] = ['id' => 'seluruh_XI', 'nama' => 'Seluruh Kelas XI'];
    $kelas_options[] = ['id' => 'seluruh_XII', 'nama' => 'Seluruh Kelas XII'];
    
    $query_kelas_list = mysqli_query($conn, "SELECT k.id_kelas, k.nama_kelas, k.tingkat, j.nama_program
                                             FROM kelas k
                                             LEFT JOIN jurusan j ON k.id_jurusan = j.id_jurusan
                                             ORDER BY k.tingkat ASC, j.nama_program ASC, k.nama_kelas ASC");
    while ($k = mysqli_fetch_assoc($query_kelas_list)) {
        $kelas_options[] = ['id' => $k['id_kelas'], 'nama' => $k['nama_kelas'], 'jurusan' => $k['nama_program']];
    }
} else {
    // MAPEL KEJURUAN: HANYA tampilkan kelas yang id_jurusan-nya cocok
    $kelas_options[] = ['id' => 'seluruh_X', 'nama' => 'Seluruh Kelas X (' . $guru_mapel['kode_jurusan'] . ')'];
    $kelas_options[] = ['id' => 'seluruh_XI', 'nama' => 'Seluruh Kelas XI (' . $guru_mapel['kode_jurusan'] . ')'];
    $kelas_options[] = ['id' => 'seluruh_XII', 'nama' => 'Seluruh Kelas XII (' . $guru_mapel['kode_jurusan'] . ')'];
    
    $query_kelas_list = mysqli_query($conn, "SELECT k.id_kelas, k.nama_kelas, k.tingkat
                                             FROM kelas k
                                             WHERE k.id_jurusan = '$id_jurusan_mapel'
                                             ORDER BY k.tingkat ASC, k.nama_kelas ASC");
    while ($k = mysqli_fetch_assoc($query_kelas_list)) {
        $kelas_options[] = ['id' => $k['id_kelas'], 'nama' => $k['nama_kelas']];
    }
}

function generatePagination($halaman, $total_halaman, $link_search) {
    $pages = []; $maxVisible = 5;
    if ($total_halaman <= $maxVisible) { for ($i = 1; $i <= $total_halaman; $i++) $pages[] = $i; }
    else { $pages[] = 1; $rangeStart = max(2, $halaman - 1); $rangeEnd = min($total_halaman - 1, $halaman + 1);
        if ($halaman <= 3) { $rangeStart = 2; $rangeEnd = min($maxVisible - 1, $total_halaman - 1); }
        elseif ($halaman >= $total_halaman - 2) { $rangeStart = max(2, $total_halaman - $maxVisible + 2); $rangeEnd = $total_halaman - 1; }
        if ($rangeStart > 2) $pages[] = '...';
        for ($i = $rangeStart; $i <= $rangeEnd; $i++) $pages[] = $i;
        if ($rangeEnd < $total_halaman - 1) $pages[] = '...'; $pages[] = $total_halaman; }
    foreach ($pages as $p) { if ($p === '...') { echo '<li class="page-item disabled"><span class="page-link px-1">...</span></li>'; }
        else { $active = ($halaman == $p) ? 'active' : ''; echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=bank_soal&pagi=' . $p . $link_search . '">' . $p . '</a></li>'; } }
}
?>

<!-- ALERT MESSAGE -->
<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-<?= (in_array($_GET['msg'], ['import_gagal','gagal','file_error','no_mapel'])) ? 'danger' : 'success'; ?> border-0 shadow-sm mb-4 d-flex align-items-center">
    <i class="bi bi-<?= (in_array($_GET['msg'], ['import_gagal','gagal','file_error','no_mapel'])) ? 'exclamation-triangle-fill' : 'check-circle-fill'; ?> me-2"></i>
    <?php 
    $msgs = [
        'disimpan' => 'Soal berhasil ditambahkan!',
        'diubah' => 'Soal berhasil diperbarui!',
        'dihapus' => 'Soal berhasil dihapus!',
        'import_sukses' => 'Berhasil mengimpor <b>'.($_GET['jml'] ?? 0).'</b> soal.' . (($_GET['skip'] ?? 0) > 0 ? ' (' . $_GET['skip'] . ' baris dilewati)' : ''),
        'import_gagal' => 'Gagal mengimpor soal. Pastikan format CSV benar.',
        'file_error' => 'File tidak ditemukan. Silakan coba lagi.',
        'no_mapel' => 'Anda belum memiliki mata pelajaran. Hubungi administrator.',
        'gagal' => 'Gagal menyimpan soal. Periksa kembali data yang diisi.',
    ];
    echo $msgs[$_GET['msg']] ?? 'Operasi berhasil.';
    ?>
</div>
<?php endif; ?>

<!-- WARNING: Belum punya mapel -->
<?php if ($id_mapel_guru == 0): ?>
<div class="alert alert-warning border-0 shadow-sm mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Perhatian:</strong> Akun Anda belum dihubungkan dengan mata pelajaran. Hubungi administrator agar bisa membuat soal.
</div>
<?php endif; ?>

<!-- INFO JENIS MAPEL & JURUSAN -->
<?php if ($id_mapel_guru > 0): ?>
<div class="alert alert-<?= ($jenis_mapel_guru === 'umum') ? 'info' : 'primary'; ?> border-0 shadow-sm mb-3 py-2 d-flex gap-2 align-items-start small">
    <i class="bi bi-<?= ($jenis_mapel_guru === 'umum') ? 'globe2' : 'mortarboard'; ?> flex-shrink-0"></i>
    <div>
        <?php if ($jenis_mapel_guru === 'umum'): ?>
            <strong>Mapel Umum </strong> &mdash; Soal berlaku untuk <strong> semua jurusan </strong> dan semua tingkat kelas (X, XI, XII).
        <?php else: ?>
            <div><strong>Mapel Kejuruan</strong> &mdash; Soal hanya untuk jurusan <strong><?= htmlspecialchars($nama_program); ?></strong> (<?= $guru_mapel['kode_jurusan']; ?>).</div>
            <div style="margin-top: 0.35rem; font-size: 0.9em; opacity: 0.95;">Kelas X menggunakan Program Keahlian, Kelas XI-XII menggunakan Konsentrasi Keahlian.</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold text-dark mb-0">Bank Soal Saya</h5>
        <p class="text-muted small mb-0">
            Mapel: <strong><?= $guru_mapel['nama_mapel'] ?? '-'; ?></strong>
            &bull; Total: <strong><?= $jumlah_data; ?></strong> soal
            <?php if ($jenis_mapel_guru === 'kejuruan' && $nama_program): ?>
                &bull; <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-pill" style="font-size:0.7rem;"><?= htmlspecialchars($nama_program); ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImport" <?= ($id_mapel_guru == 0) ? 'disabled' : ''; ?>>
            <i class="bi bi-file-earmark-excel me-1"></i> Import
        </button>
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah" <?= ($id_mapel_guru == 0) ? 'disabled' : ''; ?>>
            <i class="bi bi-plus-lg me-1"></i> Tambah Soal
        </button>
    </div>
</div>

<!-- TABEL SOAL -->
<div class="card card-custom overflow-hidden">
    <?php if ($view_mapel_id): ?>
    <!-- Filter Toolbar untuk View Soal -->
    <div class="p-3 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <form method="GET" class="d-flex align-items-center gap-2 w-100" style="min-width: 0;">
            <input type="hidden" name="page" value="bank_soal">
            <input type="hidden" name="view_mapel" value="<?= $view_mapel_id; ?>">
            <label class="small fw-bold text-muted mb-0">Filter Kelas:</label>
            <select name="filter_kelas" class="form-select form-select-sm w-auto" style="min-width: 150px;" onchange="this.form.submit()">
                <option value="">-- Semua Kelas --</option>
                <?php foreach($kelas_options as $opt): ?>
                    <option value="<?= $opt['id']; ?>" <?= ($filter_kelas == $opt['id']) ? 'selected' : ''; ?>><?= $opt['nama']; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 w-100 w-md-auto justify-content-end">
            <a href="?page=bank_soal<?= (!empty($filter_kelas)) ? '&filter_kelas=' . urlencode($filter_kelas) : ''; ?>" class="btn btn-sm btn-outline-secondary">Kembali ke Mapel</a>
            <small class="text-muted">Soal: <strong><?= htmlspecialchars($current_mapel_name); ?></strong></small>
        </div>
    </div>
    <?php endif; ?>

    <div class="card-header bg-white border-bottom-0 pt-3 px-4">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-table me-2"></i>
                <?php if ($view_mapel_id): ?>
                    Daftar Soal: <?= htmlspecialchars($current_mapel_name); ?>
                <?php else: ?>
                    Daftar Mapel
                <?php endif; ?>
            </h6>
            <span class="badge bg-light text-muted border px-3 py-2" style="font-size:0.78rem;">
                <?php if ($view_mapel_id): ?>
                    <?= $jumlah_data; ?> soal
                <?php else: ?>
                    <?= $jumlah_data; ?> mapel
                <?php endif; ?>
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <?php if ($view_mapel_id): ?>
            <thead class="bg-light">
                <tr>
                    <th class="px-3 py-3 text-center" width="5%">No</th>
                    <th width="45%">Pertanyaan</th>
                    <th class="text-center" width="8%">Kunci</th>
                    <th class="text-center d-none d-md-table-cell" width="15%">Kelas</th>
                    <th class="text-center" width="12%">Aksi</th>
                </tr>
            </thead>
            <?php else: ?>
            <thead class="bg-light">
                <tr>
                    <th class="px-3 py-3 text-center" width="5%">No</th>
                    <th width="50%">Mapel</th>
                    <th class="text-center" width="15%">Jumlah Soal</th>
                    <th class="text-center" width="20%">Aksi</th>
                </tr>
            </thead>
            <?php endif; ?>
            <tbody>
                <?php if ($jumlah_data > 0):
                    $n = $nomor;
                    while ($s = mysqli_fetch_assoc($q_soal)):
                        if ($view_mapel_id):
                ?>
                <tr>
                    <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                    <td>
                        <div class="fw-medium text-dark" style="font-size:0.85rem; max-width:400px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($s['pertanyaan']); ?>">
                            <?= htmlspecialchars(mb_substr($s['pertanyaan'], 0, 80)) . (mb_strlen($s['pertanyaan']) > 80 ? '...' : ''); ?>
                        </div>
                    </td>
                    <td class="text-center">
                        <code class="bg-primary bg-opacity-10 text-primary px-2 py-1 rounded fw-bold" style="font-size:0.85rem;"><?= strtoupper($s['kunci_jawaban']); ?></code>
                    </td>
                    <td class="text-center d-none d-md-table-cell">
                        <span class="badge bg-secondary text-white rounded-pill" style="font-size:0.72rem;"><?= htmlspecialchars($s['display_kelas'] ?? '-'); ?></span>
                    </td>
                    <td class="text-center" width="12%">
                        <button class="btn btn-sm btn-light text-primary edit-btn"  
                                data-id="<?= $s['id_soal']; ?>"
                                data-pertanyaan="<?= htmlspecialchars($s['pertanyaan']); ?>"
                                data-kelas="<?= $s['id_kelas'] ?? ''; ?>"
                                data-angkatan="<?= $s['angkatan'] ?? ''; ?>"
                                data-a="<?= htmlspecialchars($s['opsi_a']); ?>"
                                data-b="<?= htmlspecialchars($s['opsi_b']); ?>"
                                data-c="<?= htmlspecialchars($s['opsi_c']); ?>"
                                data-d="<?= htmlspecialchars($s['opsi_d']); ?>"
                                data-e="<?= htmlspecialchars($s['opsi_e']); ?>"
                                data-kunci="<?= $s['kunci_jawaban']; ?>"
                                data-bs-toggle="modal" data-bs-target="#modalEdit">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <a href="?page=bank_soal&view_mapel=<?= $view_mapel_id; ?>&hapus=<?= $s['id_soal']; ?><?= (!empty($filter_kelas)) ? '&filter_kelas=' . urlencode($filter_kelas) : ''; ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Hapus soal ini?')">
                            <i class="bi bi-trash3"></i>
                        </a>
                    </td>
                </tr>
                <?php else: ?>
                <tr>
                    <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                    <td>
                        <div class="fw-bold text-dark" style="font-size:0.85rem;">
                            <?= htmlspecialchars($s['nama_mapel']); ?>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary text-white rounded-pill px-3 py-2" style="font-size:0.85rem;"><?= $s['total_soal']; ?> soal</span>
                    </td>
                    <td class="text-center">
                        <a href="?page=bank_soal&view_mapel=<?= $s['id_mapel']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="bi bi-eye me-1"></i> Lihat Soal
                        </a>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="<?= $view_mapel_id ? '5' : '4'; ?>" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                        <strong>
                            <?php if ($view_mapel_id): ?>
                                Belum ada soal di mapel ini
                            <?php else: ?>
                                Belum ada mapel
                            <?php endif; ?>
                        </strong><br>
                        <small>
                            <?php if ($view_mapel_id): ?>
                                Klik "Tambah Soal" atau "Import" untuk menambahkan soal baru.
                            <?php else: ?>
                                Hubungi administrator untuk menugaskan mata pelajaran.
                            <?php endif; ?>
                        </small>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total_halaman > 1): ?>
    <div class="card-footer bg-white border-0 py-3">
        <nav>
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php 
                $sebelum = $halaman - 1;
                $sesudah = $halaman + 1;
                ?>
                <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill px-3 me-2" href="?page=bank_soal<?= $view_mapel_id ? "&view_mapel=$view_mapel_id" : ''; ?>&pagi=<?= $sebelum; ?>"><i class="bi bi-chevron-left"></i></a>
                </li>
                <?php 
                $start_page = max(1, $halaman - 2);
                $end_page = min($total_halaman, $halaman + 2);
                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link rounded-pill mx-1" href="?page=bank_soal'.($view_mapel_id ? "&view_mapel=$view_mapel_id" : '').'&pagi=1">1</a></li>';
                    if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                for ($x=$start_page; $x<=$end_page; $x++):
                ?>
                    <li class="page-item <?= ($halaman == $x) ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill mx-1" href="?page=bank_soal<?= $view_mapel_id ? "&view_mapel=$view_mapel_id" : ''; ?>&pagi=<?= $x; ?>"><?= $x; ?></a>
                    </li>
                <?php endfor; 
                if ($end_page < $total_halaman) {
                    if ($end_page < $total_halaman - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    echo '<li class="page-item"><a class="page-link rounded-pill mx-1" href="?page=bank_soal'.($view_mapel_id ? "&view_mapel=$view_mapel_id" : '').'&pagi='.$total_halaman.'">'.$total_halaman.'</a></li>';
                }
                ?>
                <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill px-3 ms-2" href="?page=bank_soal<?= $view_mapel_id ? "&view_mapel=$view_mapel_id" : ''; ?>&pagi=<?= $sesudah; ?>"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
        <div class="text-center mt-2"><small class="text-muted">Halaman <b><?= $halaman; ?></b> dari <b><?= $total_halaman; ?></b></small></div>
    </div>
    <?php endif; ?>
</div>

<!-- ========== MODAL TAMBAH SOAL ========== -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="?page=bank_soal" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Soal Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">PERTANYAAN</label>
                    <textarea name="pertanyaan" class="form-control" rows="4" placeholder="Tulis pertanyaan di sini..." required></textarea>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-12">
                        <label class="small fw-bold text-muted mb-1">KELAS</label>
                        <select name="id_kelas" class="form-select">
                            <option value="">-- Semua Kelas --</option>
                            <?php foreach($kelas_options as $opt): ?>
                                <option value="<?= $opt['id']; ?>"><?= $opt['nama']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI A</label>
                        <input type="text" name="opsi_a" class="form-control form-control-sm" placeholder="Jawaban opsi A">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI B</label>
                        <input type="text" name="opsi_b" class="form-control form-control-sm" placeholder="Jawaban opsi B">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI C</label>
                        <input type="text" name="opsi_c" class="form-control form-control-sm" placeholder="Jawaban opsi C">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI D</label>
                        <input type="text" name="opsi_d" class="form-control form-control-sm" placeholder="Jawaban opsi D">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI E (Opsional)</label>
                        <input type="text" name="opsi_e" class="form-control form-control-sm" placeholder="Jawaban opsi E (kosongkan jika tidak perlu)">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">KUNCI JAWABAN</label>
                        <select name="kunci_jawaban" class="form-select form-select-sm">
                            <option value="">-- Pilih --</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                </div>
                <div class="alert alert-info small py-2">
                    <i class="bi bi-info-circle me-1"></i> Mapel otomatis: <strong><?= $guru_mapel['nama_mapel'] ?? '-'; ?></strong>. Soal hanya untuk mata pelajaran Anda.<br>
                    <?php if ($jenis_mapel_guru === 'umum'): ?>
                        <small><i class="bi bi-globe2 me-1"></i> <strong>Mapel Umum</strong> — soal berlaku untuk semua kelas di semua jurusan.</small>
                    <?php else: ?>
                        <small><i class="bi bi-mortarboard me-1"></i> <strong>Mapel Kejuruan</strong> — soal hanya untuk kelas jurusan <strong><?= htmlspecialchars($nama_program); ?></strong> (Kelas X: Program Keahlian, Kelas XI-XII: Konsentrasi Keahlian).</small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_soal" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-save me-1"></i> Simpan Soal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL EDIT SOAL ========== -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="?page=bank_soal" method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="id_soal" id="edit_id">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Soal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">PERTANYAAN</label>
                    <textarea name="pertanyaan" id="edit_pertanyaan" class="form-control" rows="4" required></textarea>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-12">
                        <label class="small fw-bold text-muted mb-1">KELAS</label>
                        <select name="id_kelas" id="edit_kelas" class="form-select">
                            <option value="">-- Semua Kelas --</option>
                            <?php foreach($kelas_options as $opt): ?>
                                <option value="<?= $opt['id']; ?>"><?= $opt['nama']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI A</label>
                        <input type="text" name="opsi_a" id="edit_a" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI B</label>
                        <input type="text" name="opsi_b" id="edit_b" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI C</label>
                        <input type="text" name="opsi_c" id="edit_c" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI D</label>
                        <input type="text" name="opsi_d" id="edit_d" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">OPSI E</label>
                        <input type="text" name="opsi_e" id="edit_e" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">KUNCI JAWABAN</label>
                        <select name="kunci_jawaban" id="edit_kunci" class="form-select form-select-sm">
                            <option value="">-- Pilih --</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit_soal" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL IMPORT CSV ========== -->
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="?page=bank_soal" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title"><i class="bi bi-file-earmark-excel me-2"></i>Import Soal dari CSV</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning small py-2 mb-3">
                    <strong>Format CSV (8 kolom, pisah koma):</strong>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-bordered mb-0 small">
                            <thead class="bg-light">
                                <tr>
                                    <th>Pertanyaan</th>
                                    <th>Opsi A</th>
                                    <th>Opsi B</th>
                                    <th>Opsi C</th>
                                    <th>Opsi D</th>
                                    <th>Opsi E</th>
                                    <th>Kunci</th>
                                    <th>Kelas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <small>Baris pertama = Header (akan dilewati)</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">
                        <strong>Kunci:</strong> A/B/C/D/E<br>
                        <?php if ($jenis_mapel_guru === 'umum'): ?>
                            <strong>Kelas:</strong> Nama kelas spesifik (contoh: "X AK") atau kosongkan untuk semua kelas. Semua kelas tersedia karena ini <strong>mapel umum</strong>.<br>
                        <?php else: ?>
                            <strong>Kelas:</strong> Nama kelas spesifik dari jurusan <strong><?= htmlspecialchars($nama_program); ?></strong> saja (contoh: "X RPL", "XI RPL").<br>
                        <?php endif; ?>
                        <strong>Catatan:</strong> Untuk membuat soal "Seluruh Kelas X/XI/XII", gunakan form manual atau edit soal<br>
                        <strong>Mapel otomatis:</strong> <?= $guru_mapel['nama_mapel'] ?? '-'; ?>
                    </small>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">Pilih File CSV</label>
                    <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="import_soal" class="btn btn-success rounded-pill px-4">
                    <i class="bi bi-upload me-1"></i> Upload & Proses
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPT -->
<script>
    // Isi Modal Edit
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_pertanyaan').value = this.dataset.pertanyaan;
            document.getElementById('edit_kelas').value = this.dataset.kelas || (this.dataset.angkatan ? 'seluruh_' + this.dataset.angkatan : '');
            document.getElementById('edit_a').value = this.dataset.a;
            document.getElementById('edit_b').value = this.dataset.b;
            document.getElementById('edit_c').value = this.dataset.c;
            document.getElementById('edit_d').value = this.dataset.d;
            document.getElementById('edit_e').value = this.dataset.e;
            document.getElementById('edit_kunci').value = this.dataset.kunci;
        });
    });
</script>