<?php
// === BANK SOAL GURU ===
// File ini di-include oleh dashboard.php (page=bank_soal)
// Guru hanya bisa melihat & mengelola soal miliknya sendiri

$id_user = (int)$_SESSION['id_user'];

// Ambil data guru untuk dapatkan id_mapel
$q_guru_mapel = mysqli_query($conn, "SELECT guru.id_mapel, mapel.nama_mapel 
                                      FROM guru 
                                      LEFT JOIN mapel ON guru.id_mapel = mapel.id_mapel 
                                      WHERE guru.id_user = '$id_user'");
$guru_mapel = mysqli_fetch_assoc($q_guru_mapel);
$id_mapel_guru = (int)($guru_mapel['id_mapel'] ?? 0);

// --- PROSES: HAPUS SOAL ---
if (isset($_GET['hapus'])) {
    $id_soal = (int)$_GET['hapus'];
    // Hanya bisa hapus soal milik sendiri
    mysqli_query($conn, "DELETE FROM bank_soal WHERE id_soal = '$id_soal' AND id_user = '$id_user'");
    header("Location: ?page=bank_soal&msg=dihapus");
    exit;
}

// --- PROSES: IMPORT SOAL DARI CSV ---
if (isset($_POST['import_soal'])) {
    $file = $_FILES['file_csv']['tmp_name'];

    if (!is_uploaded_file($file)) {
        header("Location: ?page=bank_soal&msg=file_error");
        exit;
    }

    if ($id_mapel_guru == 0) {
        header("Location: ?page=bank_soal&msg=no_mapel");
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
            // Lewati baris pertama (header)
            if ($row == 1) continue;

            // Kolom CSV yang diharapkan (sesuai urutan):
            // 0=pertanyaan, 1=opsi_a, 2=opsi_b, 3=opsi_c, 4=opsi_d, 5=opsi_e, 6=kunci_jawaban, 7=tipe_soal
            $pertanyaan = mysqli_real_escape_string($conn, trim($data[0] ?? ''));
            $opsi_a = mysqli_real_escape_string($conn, trim($data[1] ?? ''));
            $opsi_b = mysqli_real_escape_string($conn, trim($data[2] ?? ''));
            $opsi_c = mysqli_real_escape_string($conn, trim($data[3] ?? ''));
            $opsi_d = mysqli_real_escape_string($conn, trim($data[4] ?? ''));
            $opsi_e = mysqli_real_escape_string($conn, trim($data[5] ?? ''));
            $kunci = strtoupper(trim($data[6] ?? ''));
            $tipe = strtolower(trim($data[7] ?? 'pilihan_ganda'));

            // Validasi
            if (empty($pertanyaan) || empty($kunci)) {
                $skip++;
                continue;
            }

            // Default tipe_soal
            if (!in_array($tipe, ['pilihan_ganda', 'essay'])) {
                $tipe = 'pilihan_ganda';
            }

            mysqli_query($conn, "INSERT INTO bank_soal (id_user, id_mapel, pertanyaan, tipe_soal, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban, status) 
                              VALUES ('$id_user', '$id_mapel_guru', '$pertanyaan', '$tipe', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$kunci', 'aktif')");
            $success++;
        }
        mysqli_commit($conn);
        fclose($handle);
        header("Location: ?page=bank_soal&msg=import_sukses&jml=$success&skip=$skip");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        fclose($handle);
        header("Location: ?page=bank_soal&msg=import_gagal");
        exit;
    }
}

// --- PROSES: TAMBAH SOAL MANUAL ---
if (isset($_POST['tambah_soal'])) {
    if ($id_mapel_guru == 0) {
        header("Location: ?page=bank_soal&msg=no_mapel");
        exit;
    }

    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $tipe_soal = mysqli_real_escape_string($conn, $_POST['tipe_soal']);
    $opsi_a = mysqli_real_escape_string($conn, $_POST['opsi_a']);
    $opsi_b = mysqli_real_escape_string($conn, $_POST['opsi_b']);
    $opsi_c = mysqli_real_escape_string($conn, $_POST['opsi_c']);
    $opsi_d = mysqli_real_escape_string($conn, $_POST['opsi_d']);
    $opsi_e = mysqli_real_escape_string($conn, $_POST['opsi_e']);
    $kunci = strtoupper(mysqli_real_escape_string($conn, $_POST['kunci_jawaban']));
    $status = 'aktif';

    mysqli_query($conn, "INSERT INTO bank_soal (id_user, id_mapel, pertanyaan, tipe_soal, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban, status) 
                      VALUES ('$id_user', '$id_mapel_guru', '$pertanyaan', '$tipe_soal', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$kunci', '$status')");
    
    if (mysqli_affected_rows($conn) > 0) {
        header("Location: ?page=bank_soal&msg=disimpan");
    } else {
        header("Location: ?page=bank_soal&msg=gagal");
    }
    exit;
}

// --- PROSES: EDIT SOAL ---
if (isset($_POST['edit_soal'])) {
    $id_soal = (int)$_POST['id_soal'];
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $tipe_soal = mysqli_real_escape_string($conn, $_POST['tipe_soal']);
    $opsi_a = mysqli_real_escape_string($conn, $_POST['opsi_a']);
    $opsi_b = mysqli_real_escape_string($conn, $_POST['opsi_b']);
    $opsi_c = mysqli_real_escape_string($conn, $_POST['opsi_c']);
    $opsi_d = mysqli_real_escape_string($conn, $_POST['opsi_d']);
    $opsi_e = mysqli_real_escape_string($conn, $_POST['opsi_e']);
    $kunci = strtoupper(mysqli_real_escape_string($conn, $_POST['kunci_jawaban']));

    // Hanya bisa edit soal milik sendiri
    mysqli_query($conn, "UPDATE bank_soal SET 
                          pertanyaan = '$pertanyaan', tipe_soal = '$tipe_soal',
                          opsi_a = '$opsi_a', opsi_b = '$opsi_b', opsi_c = '$opsi_c',
                          opsi_d = '$opsi_d', opsi_e = '$opsi_e', kunci_jawaban = '$kunci'
                          WHERE id_soal = '$id_soal' AND id_user = '$id_user'");
    header("Location: ?page=bank_soal&msg=diubah");
    exit;
}

// --- PAGINATION ---
$batas = 10;
$halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;
$sebelum = $halaman - 1;
$sesudah = $halaman + 1;

// Query: HANYA soal milik guru ini
$q_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM bank_soal WHERE id_user = '$id_user'");
$jumlah_data = mysqli_fetch_assoc($q_count)['total'];
$total_halaman = ceil($jumlah_data / $batas);

$q_soal = mysqli_query($conn, "SELECT * FROM bank_soal WHERE id_user = '$id_user' ORDER BY id_soal DESC LIMIT $halaman_awal, $batas");
$nomor = $halaman_awal + 1;
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
        'gagal' => 'Gagal menyimpan soal.',
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

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold text-dark mb-0">Bank Soal Saya</h5>
        <p class="text-muted small mb-0">Mapel: <strong><?= $guru_mapel['nama_mapel'] ?? '-'; ?></strong> &bull; Total: <strong><?= $jumlah_data; ?></strong> soal</p>
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
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="px-3 py-3 text-center" width="5%">No</th>
                    <th width="40%">Pertanyaan</th>
                    <th class="text-center d-none d-md-table-cell" width="8%">Tipe</th>
                    <th class="text-center" width="8%">Kunci</th>
                    <th class="text-center d-none d-md-table-cell" width="8%">Status</th>
                    <th class="text-center" width="12%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($jumlah_data > 0):
                    $n = $nomor;
                    while ($s = mysqli_fetch_assoc($q_soal)):
                        $badge_status = ($s['status'] == 'aktif') ? 'bg-success' : 'bg-secondary';
                ?>
                <tr>
                    <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                    <td>
                        <div class="fw-medium text-dark" style="font-size:0.85rem; max-width:400px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($s['pertanyaan']); ?>">
                            <?= htmlspecialchars(mb_substr($s['pertanyaan'], 0, 80)) . (mb_strlen($s['pertanyaan']) > 80 ? '...' : ''); ?>
                        </div>
                    </td>
                    <td class="text-center d-none d-md-table-cell">
                        <span class="badge bg-light text-dark border" style="font-size:0.72rem;">
                            <?= ($s['tipe_soal'] == 'essay') ? 'Essay' : 'PG'; ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <code class="bg-primary bg-opacity-10 text-primary px-2 py-1 rounded fw-bold" style="font-size:0.85rem;"><?= strtoupper($s['kunci_jawaban']); ?></code>
                    </td>
                    <td class="text-center d-none d-md-table-cell">
                        <span class="badge <?= $badge_status; ?> px-2 py-1 rounded-pill" style="font-size:0.72rem;"><?= ucfirst($s['status']); ?></span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-light text-primary edit-btn" 
                                data-id="<?= $s['id_soal']; ?>"
                                data-pertanyaan="<?= htmlspecialchars($s['pertanyaan']); ?>"
                                data-tipe="<?= $s['tipe_soal']; ?>"
                                data-a="<?= htmlspecialchars($s['opsi_a']); ?>"
                                data-b="<?= htmlspecialchars($s['opsi_b']); ?>"
                                data-c="<?= htmlspecialchars($s['opsi_c']); ?>"
                                data-d="<?= htmlspecialchars($s['opsi_d']); ?>"
                                data-e="<?= htmlspecialchars($s['opsi_e']); ?>"
                                data-kunci="<?= $s['kunci_jawaban']; ?>"
                                data-bs-toggle="modal" data-bs-target="#modalEdit">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <a href="?page=bank_soal&hapus=<?= $s['id_soal']; ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Hapus soal ini?')">
                            <i class="bi bi-trash3"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                        <strong>Belum ada soal</strong><br>
                        <small>Klik "Tambah Soal" atau "Import" untuk menambahkan.</small>
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
                <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill px-3 me-2" href="?page=bank_soal&pagi=<?= $sebelum; ?>"><i class="bi bi-chevron-left"></i></a>
                </li>
                <?php for ($x=1; $x<=$total_halaman; $x++): ?>
                    <li class="page-item <?= ($halaman == $x) ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill mx-1" href="?page=bank_soal&pagi=<?= $x; ?>"><?= $x; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill px-3 ms-2" href="?page=bank_soal&pagi=<?= $sesudah; ?>"><i class="bi bi-chevron-right"></i></a>
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
        <form action="" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Soal Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">TIPE SOAL</label>
                    <select name="tipe_soal" class="form-select" id="tipe_soal_tambah">
                        <option value="pilihan_ganda">Pilihan Ganda</option>
                        <option value="essay">Essay</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">PERTANYAAN</label>
                    <textarea name="pertanyaan" class="form-control" rows="4" placeholder="Tulis pertanyaan di sini..." required></textarea>
                </div>
                <div class="opsi-container" id="opsiTambah">
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
                </div>
                <div class="alert alert-info small py-2">
                    <i class="bi bi-info-circle me-1"></i> Mapel otomatis: <strong><?= $guru_mapel['nama_mapel'] ?? '-'; ?></strong>. Soal hanya untuk mata pelajaran Anda.
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
        <form action="" method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="id_soal" id="edit_id">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Soal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">TIPE SOAL</label>
                    <select name="tipe_soal" class="form-select" id="edit_tipe">
                        <option value="pilihan_ganda">Pilihan Ganda</option>
                        <option value="essay">Essay</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">PERTANYAAN</label>
                    <textarea name="pertanyaan" id="edit_pertanyaan" class="form-control" rows="4" required></textarea>
                </div>
                <div class="opsi-container" id="opsiEdit">
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
        <form action="" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title"><i class="bi bi-file-earmark-excel me-2"></i>Import Soal dari CSV</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning small py-2 mb-3">
                    <strong>Format CSV (8 kolom, pisah koma):</strong>
                    <table class="table table-sm table-bordered mb-0 mt-2 small">
                        <thead class="bg-light">
                            <tr>
                                <th>Pertanyaan</th>
                                <th>Opsi A</th>
                                <th>Opsi B</th>
                                <th>Opsi C</th>
                                <th>Opsi D</th>
                                <th>Opsi E</th>
                                <th>Kunci</th>
                                <th>Tipe</th>
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
                    <small class="text-muted">
                        <strong>Kunci:</strong> A/B/C/D/E &bull; 
                        <strong>Tipe:</strong> pilihan_ganda / essay<br>
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
            document.getElementById('edit_tipe').value = this.dataset.tipe;
            document.getElementById('edit_a').value = this.dataset.a;
            document.getElementById('edit_b').value = this.dataset.b;
            document.getElementById('edit_c').value = this.dataset.c;
            document.getElementById('edit_d').value = this.dataset.d;
            document.getElementById('edit_e').value = this.dataset.e;
            document.getElementById('edit_kunci').value = this.dataset.kunci;
        });
    });

    // Toggle opsi jika pilih Essay
    document.getElementById('tipe_soal_tambah').addEventListener('change', function() {
        document.getElementById('opsiTambah').style.display = (this.value === 'essay') ? 'none' : 'block';
    });
    document.getElementById('edit_tipe').addEventListener('change', function() {
        document.getElementById('opsiEdit').style.display = (this.value === 'essay') ? 'none' : 'block';
    });
</script>