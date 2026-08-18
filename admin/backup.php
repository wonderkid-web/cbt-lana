<?php
require_once '../config/check_admin.php';
require_once '../config/database.php';

// =====================================================
// DAFTAR SEMUA TABEL CBT (sesuai database)
// =====================================================
$all_tables = [
    'absensi_hari_ini',
    'bank_soal',
    'guru',
    'hasil_ujian',
    'jawaban_siswa',
    'jurusan',
    'kartu_rfid',
    'kelas',
    'konsentrasi',
    'log_rfid',
    'mapel',
    'nilai',
    'ruang_ujian',
    'sesi_ujian',
    'sesi_ujian_kelas',
    'siswa',
    'ujian',
    'users',
];

// =====================================================
// HELPER: Label & Ikon Tabel
// =====================================================
function getTableLabel($table) {
    $labels = [
        'absensi_hari_ini'  => 'Absensi Hari Ini',
        'bank_soal'         => 'Bank Soal',
        'guru'              => 'Data Guru',
        'hasil_ujian'       => 'Hasil Ujian',
        'jawaban_siswa'     => 'Jawaban Siswa',
        'jurusan'           => 'Jurusan',
        'kartu_rfid'        => 'Kartu RFID',
        'kelas'             => 'Data Kelas',
        'konsentrasi'       => 'Konsentrasi',
        'log_rfid'          => 'Log RFID',
        'mapel'             => 'Mata Pelajaran',
        'nilai'             => 'Nilai',
        'ruang_ujian'       => 'Ruang Ujian',
        'sesi_ujian'        => 'Sesi Ujian',
        'sesi_ujian_kelas'  => 'Sesi Ujian - Kelas',
        'siswa'             => 'Data Siswa',
        'ujian'             => 'Ujian',
        'users'             => 'Users',
    ];
    return isset($labels[$table]) ? $labels[$table] : ucfirst(str_replace('_', ' ', $table));
}

function getTableIcon($table) {
    $icons = [
        'absensi_hari_ini'  => 'bi-calendar-check',
        'bank_soal'         => 'bi-file-earmark-text',
        'guru'              => 'bi-person-badge',
        'hasil_ujian'       => 'bi-journal-check',
        'jawaban_siswa'     => 'bi-pencil-square',
        'jurusan'           => 'bi-mortarboard',
        'kartu_rfid'        => 'bi-cpu',
        'kelas'             => 'bi-house-door',
        'konsentrasi'       => 'bi-diagram-3',
        'log_rfid'          => 'bi-journal-text',
        'mapel'             => 'bi-book',
        'nilai'             => 'bi-star',
        'ruang_ujian'       => 'bi-door-open',
        'sesi_ujian'        => 'bi-clipboard-check',
        'sesi_ujian_kelas'  => 'bi-clipboard-data',
        'siswa'             => 'bi-people',
        'ujian'             => 'bi-journal-text',
        'users'             => 'bi-person-gear',
    ];
    return isset($icons[$table]) ? $icons[$table] : 'bi-table';
}

// =====================================================
// KELAS XLSX WRITER (pakai ZipArchive)
// Pastikan extension zip aktif di php.ini:
//   extension=zip
// =====================================================
class SimpleXLSXWriter {
    private $sheets = [];

    public function addSheet($name, $headers, $rows) {
        $name = substr($name, 0, 31);
        $name = str_replace([':', '\\', '/', '?', '*', '[', ']'], '', $name);
        $this->sheets[] = ['name' => $name, 'headers' => $headers, 'rows' => $rows];
    }

    private function xmlEscape($str) {
        if ($str === null) return '';
        return htmlspecialchars((string)$str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function colLetter($col) {
        $letter = '';
        while ($col >= 0) {
            $letter = chr(65 + ($col % 26)) . $letter;
            $col = (int)($col / 26) - 1;
        }
        return $letter;
    }

    public function download($filename) {
        $tmpdir = sys_get_temp_dir() . '/xlsx_' . uniqid();
        mkdir($tmpdir, 0755, true);

        // --- [Content_Types].xml ---
        $ct  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $ct .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $ct .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $ct .= '<Default Extension="xml" ContentType="application/xml"/>';
        $ct .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $ct .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 0; $i < count($this->sheets); $i++) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . ($i+1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $ct .= '</Types>';
        file_put_contents($tmpdir . '/[Content_Types].xml', $ct);

        // --- _rels/.rels ---
        $rels  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $rels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $rels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $rels .= '</Relationships>';
        mkdir($tmpdir . '/_rels', 0755, true);
        file_put_contents($tmpdir . '/_rels/.rels', $rels);

        // --- xl/workbook.xml ---
        $wb  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $wb .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $wb .= '<sheets>';
        for ($i = 0; $i < count($this->sheets); $i++) {
            $wb .= '<sheet name="' . $this->xmlEscape($this->sheets[$i]['name']) . '" sheetId="' . ($i+1) . '" r:id="rId' . ($i+2) . '"/>';
        }
        $wb .= '</sheets></workbook>';
        mkdir($tmpdir . '/xl', 0755, true);
        file_put_contents($tmpdir . '/xl/workbook.xml', $wb);

        // --- xl/_rels/workbook.xml.rels ---
        $wbrels  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $wbrels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $wbrels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        for ($i = 0; $i < count($this->sheets); $i++) {
            $wbrels .= '<Relationship Id="rId' . ($i+2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i+1) . '.xml"/>';
        }
        $wbrels .= '</Relationships>';
        mkdir($tmpdir . '/xl/_rels', 0755, true);
        file_put_contents($tmpdir . '/xl/_rels/workbook.xml.rels', $wbrels);

        // --- xl/styles.xml ---
        $styles  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $styles .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $styles .= '<numFmts count="0"/>';
        $styles .= '<fonts count="2">';
        $styles .= '<font><sz val="11"/><name val="Calibri"/></font>';
        $styles .= '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>';
        $styles .= '</fonts>';
        $styles .= '<fills count="3">';
        $styles .= '<fill><patternFill patternType="none"/></fill>';
        $styles .= '<fill><patternFill patternType="gray125"/></fill>';
        $styles .= '<fill><patternFill patternType="solid"><fgColor rgb="FF2563EB"/></patternFill></fill>';
        $styles .= '</fills>';
        $styles .= '<borders count="2">';
        $styles .= '<border><left/><right/><top/><bottom/><diagonal/></border>';
        $styles .= '<border><left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right><top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom><diagonal/></border>';
        $styles .= '</borders>';
        $styles .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';
        $styles .= '<cellXfs count="3">';
        $styles .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyBorder="1"/>';
        $styles .= '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>';
        $styles .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>';
        $styles .= '</cellXfs>';
        $styles .= '</styleSheet>';
        file_put_contents($tmpdir . '/xl/styles.xml', $styles);

        // --- xl/worksheets/sheetN.xml ---
        mkdir($tmpdir . '/xl/worksheets', 0755, true);

        foreach ($this->sheets as $i => $sheet) {
            $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
            $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

            $numCols = count($sheet['headers']);
            if ($numCols > 0) {
                $xml .= '<cols>';
                for ($c = 0; $c < $numCols; $c++) {
                    $xml .= '<col min="' . ($c+1) . '" max="' . ($c+1) . '" width="18" customWidth="1"/>';
                }
                $xml .= '</cols>';
            }

            $xml .= '<sheetData>';

            if ($numCols > 0) {
                $xml .= '<row r="1">';
                for ($c = 0; $c < $numCols; $c++) {
                    $colL = $this->colLetter($c);
                    $xml .= '<c r="' . $colL . '1" s="1" t="inlineStr"><is><t>' . $this->xmlEscape($sheet['headers'][$c]) . '</t></is></c>';
                }
                $xml .= '</row>';
            }

            $rowNum = 2;
            foreach ($sheet['rows'] as $row) {
                $xml .= '<row r="' . $rowNum . '">';
                for ($c = 0; $c < $numCols; $c++) {
                    $colL = $this->colLetter($c);
                    $val = isset($row[$c]) ? $row[$c] : '';

                    if ($val !== '' && $val !== null && is_numeric($val) && strlen((string)$val) < 16) {
                        $xml .= '<c r="' . $colL . $rowNum . '" s="2"><v>' . $val . '</v></c>';
                    } else {
                        $xml .= '<c r="' . $colL . $rowNum . '" s="2" t="inlineStr"><is><t>' . $this->xmlEscape($val) . '</t></is></c>';
                    }
                }
                $xml .= '</row>';
                $rowNum++;
            }

            $xml .= '</sheetData></worksheet>';
            file_put_contents($tmpdir . '/xl/worksheets/sheet' . ($i+1) . '.xml', $xml);
        }

        // --- ZIP jadi XLSX ---
        $xlsx_path = sys_get_temp_dir() . '/' . $filename;
        $zip = new ZipArchive();
        $zip->open($xlsx_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpdir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($tmpdir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        // Hapus folder tmp
        $this->rmdirRecursive($tmpdir);

        // Download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($xlsx_path));
        header('Cache-Control: max-age=0');
        readfile($xlsx_path);
        unlink($xlsx_path);
        exit;
    }

    private function rmdirRecursive($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $obj) {
                if ($obj !== '.' && $obj !== '..') {
                    $path = $dir . '/' . $obj;
                    is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
                }
            }
            rmdir($dir);
        }
    }
}

// =====================================================
// PROSES: BACKUP EXCEL (download .xlsx)
// =====================================================
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    $tanggal = date('Y-m-d_His');
    $filename = "backup_cbt_{$tanggal}.xlsx";

    $xlsx = new SimpleXLSXWriter();

    foreach ($all_tables as $table) {
        $cek = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
        if (mysqli_num_rows($cek) == 0) continue;

        $data_res = mysqli_query($conn, "SELECT * FROM `{$table}`");

        // Ambil nama kolom
        $headers = [];
        while ($field = mysqli_fetch_field($data_res)) {
            $headers[] = $field->name;
        }

        // Ambil data baris
        $rows = [];
        while ($row = mysqli_fetch_assoc($data_res)) {
            $row_data = [];
            foreach ($headers as $col) {
                $row_data[] = $row[$col];
            }
            $rows[] = $row_data;
        }

        // Nama sheet = label tabel
        $sheet_name = getTableLabel($table);
        $xlsx->addSheet($sheet_name, $headers, $rows);
    }

    // Log backup
    $backup_dir = '../backup_logs/';
    if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);
    file_put_contents($backup_dir . 'last_backup.txt', date('Y-m-d H:i:s'));

    $xlsx->download($filename);
}

// =====================================================
// PROSES: BERSIHKAN DATA (AJAX)
// =====================================================
if (isset($_POST['action']) && $_POST['action'] === 'clean') {
    $kode_konfirmasi = isset($_POST['kode']) ? trim($_POST['kode']) : '';

    if ($kode_konfirmasi !== 'BERSIHKAN') {
        echo json_encode(['status' => 'error', 'message' => 'Kode konfirmasi salah!']);
        exit;
    }

    // Cek apakah backup sudah pernah dilakukan hari ini
    $backup_today = false;
    $backup_dir = '../backup_logs/';
    if (file_exists($backup_dir . 'last_backup.txt')) {
        $last_backup = file_get_contents($backup_dir . 'last_backup.txt');
        if (strpos($last_backup, date('Y-m-d')) !== false) {
            $backup_today = true;
        }
    }

    $results = [];
    $error_count = 0;

    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");

    foreach ($all_tables as $table) {
        $cek = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
        if (mysqli_num_rows($cek) == 0) {
            $results[] = ['table' => $table, 'label' => getTableLabel($table), 'status' => 'skip', 'message' => 'Tabel tidak ada'];
            continue;
        }

        $del = mysqli_query($conn, "TRUNCATE TABLE `{$table}`");
        if ($del) {
            $results[] = ['table' => $table, 'label' => getTableLabel($table), 'status' => 'ok', 'message' => 'Dibersihkan'];
        } else {
            $results[] = ['table' => $table, 'label' => getTableLabel($table), 'status' => 'error', 'message' => mysqli_error($conn)];
            $error_count++;
        }
    }

    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");

    if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);
    file_put_contents($backup_dir . 'last_clean.txt', date('Y-m-d H:i:s'));

    if ($error_count === 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Semua data berhasil dibersihkan! Database siap untuk periode ujian baru.',
            'results' => $results,
            'backup_warning' => !$backup_today
        ]);
    } else {
        echo json_encode([
            'status' => 'partial',
            'message' => "Sebagian data dibersihkan, {$error_count} tabel gagal.",
            'results' => $results
        ]);
    }
    exit;
}

// =====================================================
// AMBIL INFO TABEL UNTUK TAMPILAN
// =====================================================
$table_info = [];
$total_rows = 0;
$empty_tables = 0;
$tables_with_data = 0;

foreach ($all_tables as $table) {
    $cek = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
    if (mysqli_num_rows($cek) == 0) {
        $table_info[] = [
            'name' => $table,
            'rows' => 0,
            'exists' => false,
            'size' => '-',
            'label' => getTableLabel($table)
        ];
        continue;
    }

    $count_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `{$table}`");
    $count_row = mysqli_fetch_assoc($count_res);
    $rows = (int)$count_row['cnt'];
    $total_rows += $rows;
    if ($rows === 0) $empty_tables++;
    else $tables_with_data++;

    $size_res = mysqli_query($conn, "
        SELECT ROUND((data_length + index_length) / 1024, 2) AS size_kb
        FROM information_schema.TABLES
        WHERE table_schema = DATABASE() AND table_name = '{$table}'
    ");
    $size_row = mysqli_fetch_assoc($size_res);
    $size_kb = $size_row ? $size_row['size_kb'] : 0;
    $size_display = $size_kb >= 1024 ? round($size_kb / 1024, 2) . ' MB' : $size_kb . ' KB';

    $table_info[] = [
        'name' => $table,
        'rows' => $rows,
        'exists' => true,
        'size' => $size_display,
        'label' => getTableLabel($table)
    ];
}

$last_backup_info = 'Belum pernah backup';
$backup_dir = '../backup_logs/';
if (file_exists($backup_dir . 'last_backup.txt')) {
    $raw = file_get_contents($backup_dir . 'last_backup.txt');
    $last_backup_info = date('d-m-Y H:i', strtotime($raw));
}

$last_clean_info = 'Belum pernah dibersihkan';
if (file_exists($backup_dir . 'last_clean.txt')) {
    $raw = file_get_contents($backup_dir . 'last_clean.txt');
    $last_clean_info = date('d-m-Y H:i', strtotime($raw));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Reset Data | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-bg: #0f172a;
            --accent: #3b82f6;
            --sidebar-w: 260px;
        }
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        /* ===== SIDEBAR ===== */
        #sidebar {
            width: var(--sidebar-w);
            min-width: var(--sidebar-w);
            max-width: var(--sidebar-w);
            background: var(--sidebar-bg);
            color: #fff;
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            overflow-y: auto;
            overflow-x: hidden;
            transition: margin-left 0.3s ease;
            z-index: 1050;
        }
        #sidebar::-webkit-scrollbar { width: 4px; }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
        #sidebar.collapsed { margin-left: calc(var(--sidebar-w) * -1); }

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

        /* ===== CONTENT ===== */
        .content-area {
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w));
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        .content-area.expanded { margin-left: 0; width: 100%; }

        .top-nav {
            background: #fff;
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        #sidebarOverlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
        }
        #sidebarOverlay.active { display: block; }
        body.mobile-sidebar-open { overflow: hidden !important; }

        @media (max-width: 768px) {
            #sidebar { margin-left: calc(var(--sidebar-w) * -1); }
            #sidebar.active { margin-left: 0; }
            .content-area { margin-left: 0 !important; width: 100% !important; }
            #sidebar.collapsed { margin-left: calc(var(--sidebar-w) * -1); }
        }

        /* ===== CARDS ===== */
        .info-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            transition: 0.3s;
            background: #fff;
        }
        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        .table-row-card {
            background: #fff;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
            transition: 0.2s;
        }
        .table-row-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 10px rgba(59,130,246,0.1);
        }

        .btn-backup {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            color: #fff;
            padding: 14px 30px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-backup:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37,99,235,0.3);
        }

        .btn-clean {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
            color: #fff;
            padding: 14px 30px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-clean:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220,38,38,0.3);
        }
        .btn-clean:disabled {
            opacity: 0.5;
            transform: none;
            box-shadow: none;
        }

        .badge-rows {
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 8px;
        }

        .empty-badge {
            background: #f1f5f9;
            color: #94a3b8;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        /* Action cards */
        .action-card {
            background: #fff;
            border-radius: 16px;
            padding: 35px 30px;
            text-align: center;
            border: 2px solid #e2e8f0;
            position: relative;
            height: 100%;
        }
        .action-card .action-icon {
            width: 70px; height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 20px;
        }
        .action-card.backup-card .action-icon { background: #dbeafe; color: #2563eb; }
        .action-card.clean-card .action-icon { background: #fee2e2; color: #dc2626; }

        .action-card h5 { font-weight: 700; margin-bottom: 8px; }

        /* Arrow between cards */
        .step-arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 1.5rem;
            padding-top: 40px;
        }

        /* Progress bar in clean modal */
        .clean-progress {
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
        }
        .clean-progress .bar {
            height: 100%;
            background: #3b82f6;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        /* Format badge */
        .format-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #ecfdf5;
            color: #059669;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
    <!-- Sidebar: grouping menu + mode mini -->
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=4">
    <script src="../assets/js/sidebar.js?v=4"></script>
</head>
<body>

<!-- Overlay -->
<div id="sidebarOverlay"></div>

<!-- Sidebar -->
<?php include __DIR__ . '/_sidebar.php'; ?>

<!-- Konten Utama -->
<div class="content-area" id="contentArea">
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
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold text-dark mb-1"><i class="bi bi-database-down me-2 text-primary"></i>Backup & Reset Data</h4>
                <p class="text-muted small mb-0">Download semua data dalam format Excel, lalu bersihkan database untuk periode ujian baru.</p>
            </div>
            <span class="format-badge"><i class="bi bi-file-earmark-spreadsheet"></i> Format: .xlsx</span>
        </div>

        <!-- 2 Aksi Utama -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="action-card backup-card">
                    <div class="action-icon">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                    </div>
                    <h5>Backup Data</h5>
                    <p class="text-muted small mb-1">Download seluruh data database dalam 1 file Excel.</p>
                    <p class="text-muted small mb-3">Setiap tabel menjadi 1 sheet terpisah, lengkap dengan header kolom.</p>
                    <button class="btn btn-backup w-100" onclick="doBackup()">
                        <i class="bi bi-download me-2"></i>Backup Sekarang
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="action-card clean-card">
                    <div class="action-icon">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <h5>Bersihkan Data</h5>
                    <p class="text-muted small mb-1">Hapus seluruh data dari semua tabel setelah backup.</p>
                    <p class="text-muted small mb-3">Database kembali kosong, siap diisi data periode ujian berikutnya.</p>
                    <button class="btn btn-clean w-100" onclick="showCleanModal()" <?= $total_rows == 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-trash3 me-2"></i>Bersihkan Data
                    </button>
                    <?php if ($total_rows == 0): ?>
                    <small class="text-muted d-block mt-2"><i class="bi bi-check-circle me-1"></i>Database sudah bersih</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Info Ringkas -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card info-card p-3 text-center">
                    <div class="text-primary"><i class="bi bi-table fs-2"></i></div>
                    <h3 class="fw-bold mt-2 mb-0"><?= count($all_tables) ?></h3>
                    <small class="text-muted">Total Tabel</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card info-card p-3 text-center">
                    <div class="text-success"><i class="bi bi-database fs-2"></i></div>
                    <h3 class="fw-bold mt-2 mb-0"><?= number_format($total_rows) ?></h3>
                    <small class="text-muted">Total Baris Data</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card info-card p-3 text-center">
                    <div class="text-warning"><i class="bi bi-database-exclamation fs-2"></i></div>
                    <h3 class="fw-bold mt-2 mb-0"><?= $empty_tables ?></h3>
                    <small class="text-muted">Tabel Kosong</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card info-card p-3 text-center">
                    <div class="text-info"><i class="bi bi-clock-history fs-2"></i></div>
                    <h6 class="fw-bold mt-2 mb-0" style="font-size:0.85rem;"><?= $last_backup_info ?></h6>
                    <small class="text-muted">Backup Terakhir</small>
                </div>
            </div>
        </div>

        <!-- Daftar Tabel Detail -->
        <div class="card info-card mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-2"></i>Detail Data Per Tabel</h6>
                    <div class="d-flex gap-2">
                        <span class="badge bg-success bg-opacity-10 text-success"><?= $tables_with_data ?> tabel berisi data</span>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $empty_tables ?> tabel kosong</span>
                    </div>
                </div>

                <?php foreach ($table_info as $t): ?>
                <div class="table-row-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi <?= getTableIcon($t['name']) ?> fs-4 text-secondary"></i>
                        </div>
                        <div>
                            <div class="fw-bold"><?= $t['label'] ?></div>
                            <small class="text-muted font-monospace"><?= $t['name'] ?></small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <small class="text-muted d-none d-md-block"><?= $t['size'] ?></small>
                        <?php if ($t['rows'] > 0): ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary badge-rows">
                                <?= number_format($t['rows']) ?> data
                            </span>
                        <?php else: ?>
                            <span class="empty-badge">Kosong</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($total_rows == 0): ?>
                <div class="text-center py-4">
                    <i class="bi bi-check-circle text-success" style="font-size:3rem;"></i>
                    <h6 class="fw-bold mt-2">Database Sudah Bersih</h6>
                    <p class="text-muted small mb-0">Semua tabel kosong. Siap untuk mengisi data periode ujian baru.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Riwayat -->
        <div class="card info-card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Riwayat</h6>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-download text-primary me-2 fs-5"></i>
                            <div>
                                <small class="text-muted d-block">Backup Terakhir</small>
                                <strong><?= $last_backup_info ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-trash3 text-danger me-2 fs-5"></i>
                            <div>
                                <small class="text-muted d-block">Pembersihan Terakhir</small>
                                <strong><?= $last_clean_info ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Bersihkan -->
<div class="modal fade" id="cleanModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; border:none;">
            <!-- Step 1: Peringatan -->
            <div id="cleanStep1">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Peringatan!</h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger border-0" style="border-radius:12px;">
                        <strong>Semua data akan dihapus permanen!</strong>
                        <p class="mb-0 mt-1 small">Pastikan Anda sudah <strong>membackup data</strong> sebelum melanjutkan. Data yang sudah dibersihkan tidak bisa dikembalikan.</p>
                    </div>
                    <p class="small text-muted mb-1">Data yang akan dihapus meliputi:</p>
                    <div style="max-height:180px; overflow-y:auto;" class="mb-3">
                        <ul class="small text-muted mb-0">
                            <?php foreach ($table_info as $t): ?>
                                <?php if ($t['rows'] > 0): ?>
                                <li><strong><?= $t['label'] ?></strong> (<?= $t['name'] ?>) — <?= number_format($t['rows']) ?> data</li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <hr>
                    <p class="mb-2 fw-bold">Ketik <code class="text-danger">BERSIHKAN</code> untuk melanjutkan:</p>
                    <input type="text" id="confirmCode" class="form-control form-control-lg" 
                           placeholder="Ketik BERSIHKAN di sini" autocomplete="off"
                           style="border-radius:10px; text-align:center; font-weight:700; letter-spacing:2px;">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:10px;">Batal</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmClean" onclick="doClean()" disabled style="border-radius:10px;">
                        <i class="bi bi-trash3 me-1"></i> Bersihkan Semua Data
                    </button>
                </div>
            </div>

            <!-- Step 2: Sedang membersihkan -->
            <div id="cleanStep2" style="display:none;">
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width:3rem; height:3rem;"></div>
                    <h5 class="fw-bold">Sedang Membersihkan Data...</h5>
                    <p class="text-muted small">Mohon tunggu, sedang menghapus data dari database.</p>
                    <div class="clean-progress bg-light mt-3">
                        <div class="bar" id="cleanProgress" style="width:0%"></div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Selesai -->
            <div id="cleanStep3" style="display:none;">
                <div class="modal-body text-center py-5">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:4rem;"></i>
                    <h5 class="fw-bold mt-3">Data Berhasil Dibersihkan!</h5>
                    <p class="text-muted small">Semua data telah dihapus. Database siap untuk periode ujian baru.</p>
                    <div id="cleanResults" class="text-start mt-3"></div>
                    <button type="button" class="btn btn-primary mt-3" onclick="location.reload()" style="border-radius:10px;">
                        <i class="bi bi-arrow-repeat me-1"></i> Refresh Halaman
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ============================
    // SIDEBAR TOGGLE
    // ============================
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const content = document.getElementById('contentArea');
    const toggle  = document.getElementById('toggleBtn');
    const body    = document.body;

    function isMobile() { return window.innerWidth <= 768; }

    function desktopToggle() {
        const isOpen = !sidebar.classList.contains('collapsed');
        if (isOpen) { sidebar.classList.add('collapsed'); content.classList.add('expanded'); }
        else { sidebar.classList.remove('collapsed'); content.classList.remove('expanded'); }
    }

    function mobileOpen()  { sidebar.classList.add('active'); overlay.classList.add('active'); body.classList.add('mobile-sidebar-open'); }
    function mobileClose() { sidebar.classList.remove('active'); overlay.classList.remove('active'); body.classList.remove('mobile-sidebar-open'); }

    toggle.addEventListener('click', function () {
        if (isMobile()) { sidebar.classList.contains('active') ? mobileClose() : mobileOpen(); }
        else { desktopToggle(); }
    });

    overlay.addEventListener('click', mobileClose);
    document.querySelectorAll('#sidebar .nav-link').forEach(function (link) {
        link.addEventListener('click', function () { if (isMobile()) mobileClose(); });
    });
    window.addEventListener('resize', function () { if (!isMobile()) mobileClose(); });

    // ============================
    // BACKUP (Download Excel)
    // ============================
    function doBackup() {
        window.location.href = '?action=backup';
    }

    // ============================
    // BERSIHKAN DATA
    // ============================
    const confirmInput = document.getElementById('confirmCode');
    const btnConfirm   = document.getElementById('btnConfirmClean');
    const cleanModal   = document.getElementById('cleanModal');

    confirmInput.addEventListener('input', function () {
        const val = this.value.trim().toUpperCase();
        btnConfirm.disabled = (val !== 'BERSIHKAN');
    });

    cleanModal.addEventListener('hidden.bs.modal', function () {
        confirmInput.value = '';
        btnConfirm.disabled = true;
        document.getElementById('cleanStep1').style.display = '';
        document.getElementById('cleanStep2').style.display = 'none';
        document.getElementById('cleanStep3').style.display = 'none';
    });

    function showCleanModal() {
        new bootstrap.Modal(cleanModal).show();
    }

    function doClean() {
        const kode = confirmInput.value.trim().toUpperCase();
        if (kode !== 'BERSIHKAN') return;

        document.getElementById('cleanStep1').style.display = 'none';
        document.getElementById('cleanStep2').style.display = '';

        let progress = 0;
        const progressBar = document.getElementById('cleanProgress');
        const progressInterval = setInterval(() => {
            if (progress < 90) {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }
        }, 300);

        const formData = new FormData();
        formData.append('action', 'clean');
        formData.append('kode', kode);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            clearInterval(progressInterval);
            progressBar.style.width = '100%';

            setTimeout(() => {
                document.getElementById('cleanStep2').style.display = 'none';
                document.getElementById('cleanStep3').style.display = '';

                let html = '<div class="p-3 bg-light rounded-3" style="max-height:200px; overflow-y:auto;">';
                data.results.forEach(r => {
                    const icon = r.status === 'ok' ? '<i class="bi bi-check-circle text-success me-1"></i>'
                               : r.status === 'skip' ? '<i class="bi bi-dash-circle text-secondary me-1"></i>'
                               : '<i class="bi bi-x-circle text-danger me-1"></i>';
                    html += '<div class="small">' + icon + ' <strong>' + r.label + '</strong> (' + r.table + ') &mdash; ' + r.message + '</div>';
                });
                html += '</div>';

                if (data.backup_warning) {
                    html += '<div class="alert alert-warning mt-3 mb-0 small border-0" style="border-radius:10px;">';
                    html += '<i class="bi bi-exclamation-triangle me-1"></i> ';
                    html += '<strong>Perhatian:</strong> Anda belum melakukan backup hari ini. Data yang sudah dihapus tidak bisa dikembalikan.';
                    html += '</div>';
                }

                document.getElementById('cleanResults').innerHTML = html;
            }, 500);
        })
        .catch(err => {
            clearInterval(progressInterval);
            document.getElementById('cleanStep2').style.display = 'none';
            document.getElementById('cleanStep3').style.display = '';
            document.getElementById('cleanResults').innerHTML =
                '<div class="alert alert-danger small border-0" style="border-radius:10px;">' +
                '<i class="bi bi-x-circle me-1"></i> Terjadi kesalahan: ' + err.message + '</div>';
        });
    }
</script>
</body>
</html>
